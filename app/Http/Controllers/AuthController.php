<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\SetPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = $this->auth->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function setPassword(SetPasswordRequest $request)
    {
        $request->validated();

        /** @var User */
        $user = $this->auth->user();

        $user->update([
            'password' => Hash::make($request->string('password')),
        ]);

        return response()->json(['message' => 'Password set successfully']);
    }

    public function me()
    {
        return response()->json($this->auth->user());
    }

    public function logout()
    {
        $this->auth->invalidate($this->auth->getToken());
        $this->auth->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        try {
            $token = $this->auth->refresh();
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function acceptInvitation(User $user)
    {
        $user = User::findOrFail($user->id);

        if ($user->invite_accepted_at) {
            return response()->json(['message' => 'Invitation already accepted'], 401);
        }

        if (!request()->hasValidSignature(false)) {
            return response()->json(['message' => 'Invalid invitation'], 401);
        }
        
        $user->update([
            'invite_accepted_at' => now()
        ]);

        $token = $this->auth->fromUser($user);
        
        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->auth->factory()->getTTL() * 60
        ]);
    }
}