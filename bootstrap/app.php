<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // HandleCors must be global so OPTIONS preflight requests
        // receive CORS headers before route resolution
        $middleware->prepend(HandleCors::class);

        // statefulApi() activates Sanctum's CSRF check for SPA cookie auth.
        // We use Bearer tokens, so it is intentionally omitted.
        $middleware->throttleApi();

        // Log every HTTP request (runs after CORS, before auth resolution)
        $middleware->append(\App\Http\Middleware\LogHttpRequests::class);

        // Register custom middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                return null; // let Laravel handle non-API errors normally
            }

            $debug   = config('app.debug');
            $status  = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            // ── Structured error types ────────────────────────────────────────

            // 422 Validation errors
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            // 401 Unauthenticated
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Unauthenticated. Please log in.',
                ], 401);
            }

            // 403 Authorization
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Forbidden. You do not have permission to perform this action.',
                ], 403);
            }

            // 404 Model not found
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => "{$model} not found.",
                ], 404);
            }

            // 404 Route not found
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Endpoint not found.',
                ], 404);
            }

            // 405 Method not allowed
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'HTTP method not allowed for this endpoint.',
                ], 405);
            }

            // 429 Too many requests
            if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Too many requests. Please slow down.',
                ], 429);
            }

            // ── Generic 500 / unhandled ───────────────────────────────────────
            $payload = [
                'success' => false,
                'data'    => null,
                'message' => $debug ? $e->getMessage() : 'Server error. Please try again later.',
            ];

            if ($debug) {
                $payload['debug'] = [
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'trace'     => collect($e->getTrace())->take(15)
                        ->map(fn ($f) => ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?') . ' → ' . ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? ''))
                        ->values()
                        ->toArray(),
                ];
            }

            return response()->json($payload, $status >= 400 ? $status : 500);
        });
    })->create();

