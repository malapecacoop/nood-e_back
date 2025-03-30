<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailableRoomRequest;
use App\Http\Requests\RoomRequest;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::query();

        // by default, show only available rooms
        if (!isset($request->show_unavailable) && $request->show_unavailable == 0) {
            $rooms = $rooms->isAvailable();
        }

        $rooms = $rooms->get();

        return response()->json($rooms, 200);
    }

    public function showFree(AvailableRoomRequest $request)
    {
        $data = $request->validated();
        $dateStart = new Carbon($data['start']);
        $dateEnd = new Carbon($data['end']);

        $rooms = Room::isAvailable()->whereDoesntHave('events', function($query) use ($dateStart, $dateEnd) {
            $query->where('start', '<', $dateEnd)
                ->where('end', '>', $dateStart);
        })->get();

        return response()->json($rooms, 200);
    }

    public function store(RoomRequest $request)
    {
        Gate::authorize('create', Room::class);

        $data = $request->validated();
        $room = Room::create($data);
        return response()->json($room, 201);
    }

    public function show(Request $request, Room $room)
    {
        return response()->json($room, 200);
    }

    public function update(RoomRequest $request, Room $room)
    {
        Gate::authorize('update', $room);

        $data = $request->validated();
        $room->update($data);
        return response()->json($room, 200);
    }

    public function destroy(Room $room)
    {
        Gate::authorize('delete', $room);

        $room->delete();

        return response()->json(['message' => 'Room deleted successfully'], 200);
    }
}
