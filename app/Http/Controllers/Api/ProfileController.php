<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * PUT /api/user/profile
     * Update the authenticated user's name and/or avatar.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $updates = [];

        if ($request->filled('name')) {
            $updates['name'] = $request->input('name');
        }

        if ($request->hasFile('avatar')) {
            $path            = $request->file('avatar')->store('avatars', 'public');
            $updates['avatar'] = asset('storage/' . $path);
        }

        if (count($updates) > 0) {
            $user->update($updates);
        }

        return response()->json([
            'success' => true,
            'data'    => ['user' => $user->fresh()],
            'message' => 'Profile updated successfully.',
        ]);
    }
}
