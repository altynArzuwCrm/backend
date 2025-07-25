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
            abort(401, 'Unauthenticated');
        }

        $user = Auth::user();
        $allowedRoles = explode(',', $roles);

        if (!$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Access denied. Required roles: ' . implode(', ', $allowedRoles));
        }

        return $next($request);
    }
}
