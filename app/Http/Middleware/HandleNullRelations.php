<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ErrorException;
use Symfony\Component\HttpFoundation\Response;

class HandleNullRelations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ErrorException $e) {
            if (
                str_contains($e->getMessage(), 'Attempt to read property') ||
                str_contains($e->getMessage(), 'Trying to get property') ||
                str_contains($e->getMessage(), 'Call to a member function')
            ) {

                Log::error('Null relation error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => $request->user()?->id
                ]);

                return response()->json([
                    'error' => 'Ошибка данных',
                    'message' => 'Некоторые связанные данные отсутствуют или повреждены',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            // Если это не ошибка null объекта, пробрасываем дальше
            throw $e;
        }
    }
}
