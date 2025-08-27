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
        $method = $request->method();

        // Очищаем кэш стадий только при операциях изменения (не при GET запросах)
        if (str_contains($path, 'stages') !== false && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            Cache::forget('stages_with_roles');
            Cache::forget('stages_cache');
        }

        // Очищаем кэш продуктов только при операциях изменения
        if (str_contains($path, 'products') !== false && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->clearProductCache();
        }

        // Очищаем кэш пользователей только при операциях изменения
        if (str_contains($path, 'users') !== false && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->clearUserCache();
        }

        // Очищаем кэш заказов только при операциях изменения
        if (str_contains($path, 'orders') !== false && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->clearOrderCache();
        }
    }

    private function clearProductCache(): void
    {
        // Очищаем конкретные кэши продуктов
        Cache::forget('all_products');
        Cache::forget('products_cache');

        // Очищаем кэши поиска продуктов
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
