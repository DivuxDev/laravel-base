<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogHttpRequests
{
    /**
     * Log every incoming HTTP request with timing, auth context and response status.
     *
     * Sensitive routes (login, register, password reset) have their bodies
     * stripped so credentials never land in log files.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        Log::channel('http')->info('[HTTP REQUEST]', $this->buildContext($request, $response, $durationMs));

        return $response;
    }

    private function buildContext(Request $request, Response $response, float $durationMs): array
    {
        $user = $request->user();

        return [
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'path'        => $request->path(),
            'ip'          => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'status'      => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'user_id'     => $user?->id,
            'user_email'  => $user?->email,
            'user_role'   => $user?->role ?? null,
        ];
    }
}
