<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\Recurrency;
use App\Models\Room;
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

        $eventStart = new Carbon($data['start']);
        $eventEnd = new Carbon($data['end']);

        $roomId = $data['room_id'] ?? null;

        if ($roomId) {
            $this->checkRoomAvailability($eventStart, $eventEnd, $data['room_id']);
        }

        list($recurrencyType, $recurrencyEnd) = $this->getRecurrencyFromData($data);

        if ($recurrencyType) {
            $this->setRecurrency($data, $eventStart, $eventEnd, $recurrencyType, $recurrencyEnd, $roomId);
        }

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

    private function getRecurrencyFromData(array &$data): array
    {
        $recurrencyType = $data['recurrency_type'] ?? null;
        $recurrencyEnd = $data['recurrency_end'] ? new Carbon($data['recurrency_end']) : null;

        unset($data['recurrency_type']);
        unset($data['recurrency_end']);

        return [$recurrencyType, $recurrencyEnd];
    }

    private function setRecurrency(
        array &$data,
        Carbon $eventStart,
        Carbon $eventEnd,
        int $recurrencyType,
        Carbon  $recurrencyEnd,
        ?int $roomId): void
    {
        if ($roomId) {
            $this->checkRoomAvailabilityForRecurrency($eventStart, $eventEnd, $recurrencyType, $recurrencyEnd, $roomId);
        }

        $recurrency = Recurrency::create([
            'type' => $recurrencyType,
            'end' => $recurrencyEnd,
        ]);

        $data['recurrency_id'] = $recurrency->id;
    }

    private function checkRoomAvailability(Carbon $eventStart, Carbon $eventEnd, ?int $roomId): void
    {
        $room = Room::isAvailable()->where('id', $roomId)->whereDoesntHave('events', function ($query) use ($eventStart, $eventEnd) {
            $query->where('start', '<', $eventEnd)
                ->where('end', '>', $eventStart);
        })->first();

        if (!$room) {
            abort(409, 'Room is not available for the specified time.');
        }
    }

    private function checkRoomAvailabilityForRecurrency(
        Carbon $eventStart,
        Carbon $eventEnd,
        int $recurrencyType,
        ?Carbon $recurrencyEnd,
        int $roomId
    ): void
    {
        // TODO: check room availability for each recurrency event in the next Recurrency::DAYS_GENERATE days
        abort(409, 'Room is not available for the specified recurrency.');
    }
}
