<?php

namespace App\Http\Controllers;

use App\Http\Requests\MassInviteRequest;
use App\Http\Requests\UserBasicDataUpdate;
use App\Http\Requests\UserRequestCreate;
use App\Http\Requests\UserRequestUpdate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller 
{
    public function index(Request $request)
    {
        $params = $request->all();

        $users = User::query();

        if (isset($params['show_deleted']) && $params['show_deleted'] == 1) {
            $users = $users->withTrashed();
        }

        $users = $users->whereNotNull('invite_accepted_at')
            ->with('organization', 'role')->orderBy('name')->get()->values();
        return response()->json($users);
    }
    
    public function massInvite(MassInviteRequest $request)
    {
        Gate::authorize('massInvite', User::class);

        $data = $request->validated();

        $emails = $data['emails'];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();

            $name = explode('@', $email)[0];

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email
                ]);
            }

            $user->sendInviteNotification();
        }

        return response()->json(['message' => 'Invitations sent successfully'], 201);
    }

    public function store(UserRequestCreate $request)
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $user = User::create($data);

        $user->sendInviteNotification();

        return response()->json([
            'user' => $user,
            'message' => 'Invitation sent successfully'
        ], 201);
    }

    public function show(User $user)
    {
        $user->load('role', 'organization');
        return response()->json($user);
    }

    public function updateBasicData(UserBasicDataUpdate $request, User $user)
    {
        Gate::authorize('update', $user);

        $data = $request->validated();
        $user->update($data);
        $user->load('role', 'organization');

        return response()->json($user, 200);
    }

    public function update(UserRequestUpdate $request, User $user)
    {
        Gate::authorize('update', $user);

        // TODO: cannot update user role if not superadmin

        $data = $request->validated();
        $user->update($data);
        $user->load('role', 'organization');
        return response()->json($user, 200);
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'User deactivated successfully'], 200);
    }

    public function restore(User $user)
    {
        Gate::authorize('restore', $user);

        $user->restore();

        return response()->json($user, 200);
    }
}
