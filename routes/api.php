<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ClientContactController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderAssignmentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('stats', [StatsController::class, 'index']);

    Route::get('activity', [ActivityController::class, 'index']);
    Route::get('recent-activity', [ActivityController::class, 'recent']);

    Route::get('orders/{order}/status-logs', [OrderController::class, 'statusLogs']);
    Route::put('orders/{order}/stage', [OrderController::class, 'updateStage']);
    Route::apiResource('comments', CommentController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('products', ProductController::class);
    Route::prefix('orders/{order}')->group(function () {
        Route::post('assign', [OrderAssignmentController::class, 'assign']);
    });

    Route::prefix('assignments')->group(function () {
        Route::get('/', [OrderAssignmentController::class, 'index']);
        Route::get('{assignment}', [OrderAssignmentController::class, 'show']);
        Route::put('{assignment}/status', [OrderAssignmentController::class, 'updateStatus']);
        Route::delete('{assignment}', [OrderAssignmentController::class, 'destroy']);
    });

    Route::get('clients/all', [ClientController::class, 'allClients']);
    Route::apiResource('clients', ClientController::class);
    Route::prefix('clients/{client}')->group(function () {
        Route::apiResource('contacts', ClientContactController::class)->except(['create', 'edit']);
    });

    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('users', UserController::class);
    Route::get('users/role/{role}', [UserController::class, 'getByRole']);
});
