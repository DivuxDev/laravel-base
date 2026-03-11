<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user and return a token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => $user,
                'token' => $token,
            ],
            'message' => 'User registered successfully.',
        ], 201);
    }

    /**
     * Authenticate a user and return a token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Invalid credentials. Please check your email and password.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Revoke all previous tokens for a clean session
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => $user,
                'token' => $token,
            ],
            'message' => 'Login successful.',
        ]);
    }

    /**
     * Revoke the current user token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the authenticated user profile.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'user' => $request->user(),
            ],
            'message' => 'User profile retrieved successfully.',
        ]);
    }
}
