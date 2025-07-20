<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ActivityLogController;

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Student-specific routes will go here
    Route::prefix('student')->group(function () {
        Route::get('/dashboard', function (Request $request) {
            return response()->json([
                'message' => 'Student dashboard',
                'user' => $request->user()
            ]);
        });
        
        // Add more student routes here as needed
    });

    // Activity Log routes
    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::post('/', [ActivityLogController::class, 'store']);
        Route::get('/statistics', [ActivityLogController::class, 'statistics']);
        Route::get('/{activityLog}', [ActivityLogController::class, 'show']);
    });
});