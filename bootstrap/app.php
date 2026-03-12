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
        //
    })->create();

