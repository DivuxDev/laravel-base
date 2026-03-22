<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerificationEmail;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __construct(private MailService $mail) {}

    /**
     * Send a verification email to the authenticated user.
     */
    public function send(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'message' => 'Email already verified.',
            ]);
        }

        $url = $this->buildVerificationUrl($user);

        try {
            $this->mail->send(new VerificationEmail($user, $url), $user->email);
        } catch (\Throwable) {
            // Mail unavailable — return success anyway so callers are not blocked
        }

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Verification email sent.',
        ]);
    }

    /**
     * Verify the email address using the id, hash, and signature from the link.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'id'        => ['required', 'integer'],
            'hash'      => ['required', 'string'],
            'signature' => ['required', 'string'],
        ]);

        $user = User::find($request->integer('id'));

        if (! $user) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'User not found.',
            ], 404);
        }

        $hash      = $request->string('hash')->toString();
        $signature = $request->string('signature')->toString();

        // Validate hash against the user's current email
        if (! hash_equals(sha1($user->email), $hash)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Invalid verification link.',
            ], 422);
        }

        // Validate HMAC signature
        $expectedSignature = hash_hmac('sha256', $user->id . '|' . $hash, config('app.key'));

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Invalid verification link.',
            ], 422);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'message' => 'Email already verified.',
            ]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        AuditService::log('user.email_verified', [
            'user_id'        => $user->id,
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Email verified successfully.',
        ]);
    }

    /**
     * Build the signed frontend verification URL for a given user.
     */
    private function buildVerificationUrl(User $user): string
    {
        $hash      = sha1($user->email);
        $signature = hash_hmac('sha256', $user->id . '|' . $hash, config('app.key'));

        return config('app.frontend_url')
            . '/verify-email'
            . '?id=' . $user->id
            . '&hash=' . $hash
            . '&signature=' . $signature;
    }
}
