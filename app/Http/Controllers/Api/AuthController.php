<?php

namespace App\Http\Controllers\Api;

use App\Events\UserLoggedIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\VerificationEmail;
use App\Mail\WelcomeEmail;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private MailService $mail) {}

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

        try {
            $this->mail->send(new WelcomeEmail($user), $user->email);
        } catch (\Throwable) {
            // Mail unavailable — registration proceeds normally
        }

        try {
            $hash      = sha1($user->email);
            $signature = hash_hmac('sha256', $user->id . '|' . $hash, config('app.key'));
            $verificationUrl = config('app.frontend_url')
                . '/verify-email'
                . '?id=' . $user->id
                . '&hash=' . $hash
                . '&signature=' . $signature;

            $this->mail->send(new VerificationEmail($user, $verificationUrl), $user->email);
        } catch (\Throwable) {
            // Mail unavailable — registration proceeds normally
        }

        AuditService::log('user.registered', [
            'user_id'        => $user->id,
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'new_values'     => ['name' => $user->name, 'email' => $user->email],
        ]);

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
        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        // Brute force: check lockout before attempting authentication
        if ($user && $user->isLockedOut()) {
            $seconds   = (int) now()->diffInSeconds($user->locked_until, false);
            $minutes   = (int) ceil($seconds / 60);

            return response()->json([
                'success' => false,
                'data'    => ['locked_for_seconds' => max(0, $seconds)],
                'message' => "Account locked due to too many failed attempts. Try again in {$minutes} minute(s).",
            ], 423);
        }

        if (! Auth::attempt($request->only('email', 'password'))) {
            // Increment failure counter on known accounts only
            if ($user) {
                $user->incrementFailedAttempts();
            }

            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Invalid credentials. Please check your email and password.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Clear any previous failed attempts on successful login
        $user->resetFailedAttempts();

        // Revoke all previous tokens for a clean session
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Broadcast login event — runs async via queue worker.
        // Wrapped in try/catch so login always succeeds even if Reverb is not running.
        try {
            broadcast(new UserLoggedIn($user));
        } catch (\Throwable) {
            // WebSocket server unavailable — login proceeds normally
        }

        AuditService::log('user.login', [
            'user_id'        => $user->id,
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
        ]);

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
