<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    protected function authenticate($request, array $guards)
    {
        parent::authenticate($request, $guards);
        $user = $request->user();
        if ($user && !$user->is_active) {
            // Разлогиниваем пользователя и возвращаем ошибку
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            Auth::guard($guards[0] ?? null)->logout();
            abort(403, 'Ваш аккаунт деактивирован. Обратитесь к администратору.');
        }
    }

    public function unauthenticated($request, array $guards)
    {
        $redirectTo = in_array('web', $guards) ? route('login') : '';

        throw new AuthenticationException(
            'Unauthenticated.', $guards, $redirectTo
        );
    }
}

