<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $dateStart = $request->get('start') ? new Carbon($request->get('start')) : now()->startOfMonth();
        $dateEnd = $request->get('end') ? new Carbon($request->get('end')) : now()->addMonth()->startOfMonth();

        //if dateEnd > dateStart + 2 months, set dateEnd to dateStart + 2 months
        if ($dateEnd->diffInMonths($dateStart, true) > 2) {
            $dateEnd = $dateStart->copy()->addMonths(2);
        }

        $events = Event::where('start', '>=', $dateStart)
            ->where('end', '<', $dateEnd);

        if ($this->user->role_id === 1) {
            $events->where(function ($query) {
                $query->where('author_id', $this->user->id)->orWhereHas('members', function ($query) {
                    $query->where('user_id', $this->user->id);
                });
            });
        }

        return response()->json($events->get(), 200);
    }

    public function store(EventRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['author_id'] = $this->user->id;

        $members = $this->getMembersFromData($data);

        $event = Event::create($data);

        $event = $this->attachMembers($event, $members);

        return response()->json($event, 201);
    }

    public function show(Event $event)
    {
        $event->load('members', 'room', 'author', 'author.organization');
        return response()->json($event);
    }

    public function update(EventRequest $request, Event $event)
    {
        Gate::authorize('update', $event);

        $data = $request->validated();

        $members = $this->getMembersFromData($data);

        $event->update($data);

        $event = $this->attachMembers($event, $members);

        return response()->json($event);
    }
    
    public function destroy(Event $event)
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully'], 200);
    }

    private function getMembersFromData(array &$data): array
    {
        $members = $data['members'] ?? [];
        unset($data['members']);

        return $members;
    }

    private function attachMembers(Event $event, array $members): Event
    {
        $event->members()->sync($members);
        $event->load('members');

        return $event;
    }
}
