<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemAssignmentController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::apiResource('comments', CommentController::class);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::patch('order-item-assignment/{assignment}/status', [OrderItemAssignmentController::class, 'updateStatus']);
    Route::apiResource('order-items', OrderItemController::class);
    Route::patch('order-items/{OrderItem}/status', [OrderItemController::class, 'updateStatus']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('clients', ClientController::class);
    Route::delete('client-contacts/{contact}', [ClientController::class, 'destroyContact']);
    Route::post('orders/{order}/status', [OrderController::class, 'changeStatus']);
    Route::prefix('order-items/{orderItem}')->group(function () {
        Route::post('assign', [OrderItemAssignmentController::class, 'assign']);
    });
    Route::prefix('orders')->group(function () {
        Route::apiResource('orders', OrderController::class);
        Route::patch('{order}/mark-cancelled', [OrderController::class, 'markAsCancelled'])->name('orders.markCancelled');
        Route::patch('{order}/mark-completed', [OrderController::class, 'markAsCompleted'])->name('orders.markCompleted');
        Route::post('{order}/cancel', [OrderController::class, 'cancelOrder'])->name('orders.cancelWithReasons');
    });
    Route::prefix('assignments/{assignment}')->group(function () {
        Route::patch('status', [OrderItemAssignmentController::class, 'updateStatus']);
        Route::post('reassign', [OrderItemAssignmentController::class, 'reassign']);
    });
    Route::middleware('role:Админ,Менеджер')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });
});
