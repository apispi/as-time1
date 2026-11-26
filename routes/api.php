<?php

use App\Http\Controllers\Api\TimeController;
use Illuminate\Support\Facades\Route;

/*
| Public REST API — the same operations as the MCP tools, for callers that
| are not MCP clients. No key required; rate-limited per IP ("api" limiter
| in AppServiceProvider). Documented at /docs.
*/
Route::prefix('v1')->middleware(['throttle:api', 'usage:api'])->group(function () {
    Route::get('/time', [TimeController::class, 'now']);
    Route::get('/convert', [TimeController::class, 'convert']);
    Route::get('/difference', [TimeController::class, 'difference']);
    Route::get('/timezones', [TimeController::class, 'timezones']);
});
