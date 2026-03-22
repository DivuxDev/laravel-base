<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Mail\ResetPasswordEmail;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function __construct(private MailService $mail) {}

    /**
     * Send a password reset link to the given email address.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->email;
        $user  = User::where('email', $email)->first();

        // Return success regardless of whether the email exists to prevent enumeration
        if (! $user) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'message' => 'If that email is registered, a password reset link has been sent.',
            ]);
        }

        $token    = Str::random(64);
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        try {
            $this->mail->send(new ResetPasswordEmail($user, $resetUrl), $user->email);
        } catch (\Throwable) {
            // Mail unavailable — the token is still stored so the request is not lost
        }

        AuditService::log('user.password_reset_requested', [
            'user_id'        => $user->id,
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'If that email is registered, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset the user password using a valid token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'This password reset link is invalid.',
            ], 422);
        }

        if (! Carbon::parse($record->created_at)->addMinutes(60)->isFuture()) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'This password reset link has expired. Please request a new one.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'No account was found for this email address.',
            ], 422);
        }

        DB::transaction(function () use ($user, $request) {
            $user->update(['password' => Hash::make($request->password)]);
            $user->tokens()->delete();
        });

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        AuditService::log('user.password_reset', [
            'user_id'        => $user->id,
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Your password has been reset successfully. You can now log in with your new password.',
        ]);
    }
}
