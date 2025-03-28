<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Http\Requests\RecurrencyEndRequest;
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
            $recurrency = $this->setRecurrency($data, $eventStart, $eventEnd, $recurrencyType, $recurrencyEnd, $roomId);
        }

        $event = Event::create($data);

        $event = $this->attachMembers($event, $members);

        if (isset($recurrency)) {
            $recurrency->first_event_id = $event->id;
            $recurrency->save();
        }

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

        if ($event->recurrency_id) {
            abort(409, 'You cannot update an event that is part of a recurrency.');
        }

        $data = $request->validated();

        $members = $this->getMembersFromData($data);

        $eventStart = new Carbon($data['start']);
        $eventEnd = new Carbon($data['end']);

        $roomId = $data['room_id'] ?? null;

        if ($roomId) {
            $this->checkRoomAvailability($eventStart, $eventEnd, $data['room_id']);
        }

        list($recurrencyType, $recurrencyEnd) = $this->getRecurrencyFromData($data);

        if ($recurrencyType) {
            $recurrency = $this->setRecurrency($data, $eventStart, $eventEnd, $recurrencyType, $recurrencyEnd, $roomId);
        }

        $event->update($data);

        if (isset($recurrency)) {
            $recurrency->first_event_id = $event->id;
            $recurrency->save();
        }

        $event = $this->attachMembers($event, $members);

        return response()->json($event);
    }

    public function updateRecurrencyEnd(RecurrencyEndRequest $request, Event $event)
    {
        Gate::authorize('update', $event);

        $data = $request->validated();

        if (!$event->recurrency_id) {
            abort(409, 'You cannot update an event that is not part of a recurrency.');
        }

        $recurrencyEnd = isset($data['recurrency_end']) ? new Carbon($data['recurrency_end']) : null;
        $this->checkRoomAvailabilityForRecurrency($event->start, $event->end, $event->recurrency->type, $recurrencyEnd, $event->room_id, $event->recurrency->id);

        $event->recurrency->update([
            'end' => $data['recurrency_end'],
        ]);
        $event->recurrency->save();

        return response()->json($event);
    }
    
    public function destroy(Event $event)
    {
        Gate::authorize('delete', $event);

        if ($event->recurrency_id) {
            // this will delete all events in the recurrency because of the cascade constraint
            $event->recurrency->delete();

            return response()->json(['message' => 'All events in the recurrency deleted successfully'], 200);
        }

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
        $recurrencyEnd = isset($data['recurrency_end']) ? new Carbon($data['recurrency_end']) : null;

        unset($data['recurrency_type']);
        unset($data['recurrency_end']);

        return [$recurrencyType, $recurrencyEnd];
    }

    private function setRecurrency(
        array &$data,
        Carbon $eventStart,
        Carbon $eventEnd,
        int $recurrencyType,
        ?Carbon  $recurrencyEnd,
        ?int $roomId): Recurrency
    {
        if ($roomId) {
            // clone the recurrencyEnd to avoid modifying the original object
            $recurrencyEndCopy = $recurrencyEnd ? $recurrencyEnd->copy() : null;
            $this->checkRoomAvailabilityForRecurrency($eventStart, $eventEnd, $recurrencyType, $recurrencyEndCopy, $roomId);
        }

        $recurrency = Recurrency::create([
            'type' => $recurrencyType,
            'end' => $recurrencyEnd,
        ]);

        $data['recurrency_id'] = $recurrency->id;

        return $recurrency;
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
        int $roomId,
        ?int $excludeRecurrencyId = null
    ): void
    {
        $maxRecurrencyDate = Carbon::now()->addDays(Recurrency::DAYS_GENERATE);
        if (!$recurrencyEnd || $recurrencyEnd->isAfter($maxRecurrencyDate)) {
            $recurrencyEnd = $maxRecurrencyDate;
        }
        $recurrencyEnd->addDay();

        $addPeriodMethod = Recurrency::getCarbonMethod($recurrencyType);
        $eventStart->$addPeriodMethod();
        $eventEnd->$addPeriodMethod();

        $datesToCheck = [];
        while ($eventStart->isBefore($recurrencyEnd)) {
            $datesToCheck[] = [
                'start' => $eventStart->format('Y-m-d H:i:s'),
                'end' => $eventEnd->format('Y-m-d H:i:s'),
            ];

            $eventStart->$addPeriodMethod();
            $eventEnd->$addPeriodMethod();
        }
        $room = Room::isAvailable()->where('id', $roomId)->whereDoesntHave('events', function ($query) use ($datesToCheck, $excludeRecurrencyId) {
            if ($excludeRecurrencyId) {
                $query->where('recurrency_id', '!=', $excludeRecurrencyId);
            }
            $query->where(function ($query) use ($datesToCheck) {
                foreach ($datesToCheck as $dates) {
                    $query->orWhere(function ($query) use ($dates) {
                        $query->where('start', '<', $dates['end'])
                            ->where('end', '>', $dates['start']);
                    });
                }
            });
        })->first();

        if (!$room) {
            abort(409, 'Room is not available for some dates in the recurrency.');
        }
    }
}
