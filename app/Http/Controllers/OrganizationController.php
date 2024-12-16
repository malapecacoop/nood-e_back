<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::all();
        $organizations->loadCount('users');
        return response()->json($organizations, 200);
    }

    public function store(OrganizationRequest $request)
    {
        Gate::authorize('create', Organization::class);

        $data = $request->validated();
        $organization = Organization::create($data);
        return response()->json($organization, 201);
    }

    public function show(Organization $organization)
    {
        $organization->load('owner', 'users');
        return response()->json($organization, 200);
    }

    public function update(OrganizationRequest $request, Organization $organization)
    {
        Gate::authorize('update', $organization);

        $data = $request->validated();
        $organization->update($data);
        return response()->json($organization, 200);
    }

    public function destroy(Organization $organization)
    {
        Gate::authorize('delete', $organization);
        
        $organization->delete();

        return response()->json(['message' => 'Organization deleted successfully'], 200);
    }

    public function userDestroy(Organization $organization, User $user)
    {
        $user = User::where('organization_id', $organization->id)->findOrFail($user->id);
        $user->update(['organization_id' => null]);

        return response()->json(['message' => 'User removed successfully'], 200);
    }
}
