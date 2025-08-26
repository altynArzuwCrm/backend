<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ClearCacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Очищаем кэш после успешных операций изменения данных
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']) && $response->getStatusCode() < 400) {
            $this->clearRelevantCache($request);
        }

        return $response;
    }

    /**
     * Очищаем релевантный кэш в зависимости от маршрута
     */
    private function clearRelevantCache(Request $request): void
    {
        $path = $request->path();

        if (str_contains($path, 'stages') !== false) {
            Cache::forget('stages_with_roles');
        }

        if (str_contains($path, 'products') !== false) {
            // Очищаем все кэши продуктов
            $this->clearProductCache();
        }

        if (str_contains($path, 'users') !== false) {
            // Очищаем все кэши пользователей
            $this->clearUserCache();
        }

        if (str_contains($path, 'orders') !== false) {
            // Очищаем кэш заказов
            $this->clearOrderCache();
        }
    }

    private function clearProductCache(): void
    {
        // Очищаем все кэши продуктов (можно улучшить, если знать точные ключи)
        $keys = Cache::get('product_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('product_cache_keys');
    }

    private function clearUserCache(): void
    {
        // Очищаем все кэши пользователей
        $keys = Cache::get('user_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('user_cache_keys');
    }

    private function clearOrderCache(): void
    {
        // Очищаем кэш заказов
        Cache::forget('orders_cache');
    }
}
