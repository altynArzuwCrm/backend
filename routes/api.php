<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::apiResource('comments', CommentController::class);

    Route::middleware('role:Админ,Менеджер')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:Дизайнер')->group(function () {

    });

    Route::middleware('role:Оператор печати')->group(function () {

    });

    Route::middleware('role:Сотрудник цеха')->group(function() {

    });
});
