<?php

use Illuminate\Support\Facades\Route;

// No closure routes here — route:cache requires all routes to use controllers.
// The API is the primary entrypoint; web routes are intentionally minimal.
Route::get('/', fn () => response()->json(['status' => 'ok']));
