<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function unauthenticated($request, array $guards)
    {
        $redirectTo = in_array('web', $guards) ? route('login') : '';

        throw new AuthenticationException(
            'Unauthenticated.', $guards, $redirectTo
        );
    }
}

