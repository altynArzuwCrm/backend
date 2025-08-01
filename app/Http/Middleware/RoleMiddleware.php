<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();

        // Проверяем, активен ли пользователь
        if (!$user->is_active) {
            return response()->json(['message' => 'Ваш аккаунт деактивирован. Обратитесь к администратору.'], 403);
        }

        $allowedRoles = explode(',', $roles);

        if (!$user->hasAnyRole($allowedRoles)) {
            return response()->json(['message' => 'Access denied. Required roles: ' . implode(', ', $allowedRoles)], 403);
        }

        return $next($request);
    }
}
