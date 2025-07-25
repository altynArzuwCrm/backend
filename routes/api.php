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
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum', 'handle.null.relations'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('stats', [StatsController::class, 'index']);
    Route::get('stats/dashboard', [\App\Http\Controllers\Api\StatsController::class, 'dashboard']);

    Route::get('activity', [ActivityController::class, 'index']);
    Route::get('recent-activity', [ActivityController::class, 'recent']);

    Route::get('orders/{order}/status-logs', [OrderController::class, 'statusLogs']);
    Route::put('orders/{order}/stage', [OrderController::class, 'updateStage']);
    Route::get('orders/work-types', [OrderController::class, 'getWorkTypes']);
    Route::apiResource('comments', CommentController::class);
    Route::apiResource('orders', OrderController::class);
    Route::get('products/all', [ProductController::class, 'allProducts']);
    Route::apiResource('products', ProductController::class);

    // Маршруты для управления назначениями продуктов
    Route::prefix('products/{product}')->group(function () {
        Route::get('assignments', [\App\Http\Controllers\Api\ProductAssignmentController::class, 'index']);
        Route::post('assignments', [\App\Http\Controllers\Api\ProductAssignmentController::class, 'store']);
        Route::post('assignments/bulk', [\App\Http\Controllers\Api\ProductAssignmentController::class, 'bulkAssign']);
        Route::get('assignments/available-users', [\App\Http\Controllers\Api\ProductAssignmentController::class, 'getAvailableUsers']);
        Route::put('assignments/{assignment}', [\App\Http\Controllers\Api\ProductAssignmentController::class, 'update']);
        Route::delete('assignments/{assignment}', [\App\Http\Controllers\Api\ProductAssignmentController::class, 'destroy']);
    });
    Route::prefix('orders/{order}')->group(function () {
        Route::post('assign', [OrderAssignmentController::class, 'assign']);
        Route::post('bulk-assign', [OrderAssignmentController::class, 'bulkAssign']);
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

    Route::get('projects/all', [ProjectController::class, 'allProjects']);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('users', UserController::class);
    Route::get('users/role/{role}', [UserController::class, 'getByRole']);
    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    Route::get('notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('notifications/unread', [\App\Http\Controllers\Api\NotificationController::class, 'unread']);
    Route::post('notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::get('roles', [RoleController::class, 'index']);

    // Аудит-логи (только для администраторов)
    Route::middleware('role:admin')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index']);
        Route::get('audit-logs/stats', [AuditLogController::class, 'stats']);
        Route::get('audit-logs/entity', [AuditLogController::class, 'entityLogs']);
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users/all', [\App\Http\Controllers\Api\UserController::class, 'getAllUsers']);
});
