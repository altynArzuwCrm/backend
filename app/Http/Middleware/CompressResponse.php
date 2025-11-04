<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Сжимаем только JSON ответы для API
        if ($request->expectsJson() || $request->is('api/*')) {
            // Проверяем Content-Type - сжимаем только JSON
            $contentType = $response->headers->get('Content-Type', '');
            if (!str_contains($contentType, 'application/json')) {
                return $response;
            }

            // Проверяем, не сжат ли уже ответ
            if ($response->headers->has('Content-Encoding')) {
                return $response;
            }

            $content = $response->getContent();
            
            // Не сжимаем пустые ответы или слишком маленькие (меньше 1KB)
            if (empty($content) || strlen($content) < 1024) {
                return $response;
            }
            
            // Проверяем, поддерживает ли клиент сжатие
            $acceptEncoding = $request->header('Accept-Encoding', '');
            
            if (str_contains($acceptEncoding, 'gzip') && function_exists('gzencode')) {
                $compressed = gzencode($content, 6); // Уровень сжатия 6 (баланс скорости и размера)
                
                if ($compressed !== false && strlen($compressed) < strlen($content)) {
                    $response->setContent($compressed);
                    $response->headers->set('Content-Encoding', 'gzip');
                    $response->headers->set('Content-Length', strlen($compressed));
                    $response->headers->set('Vary', 'Accept-Encoding');
                    // Удаляем Content-Type из заголовков, чтобы браузер правильно обработал
                    $response->headers->remove('Content-Type');
                    $response->headers->set('Content-Type', 'application/json');
                }
            } elseif (str_contains($acceptEncoding, 'deflate') && function_exists('gzdeflate')) {
                $compressed = gzdeflate($content, 6);
                
                if ($compressed !== false && strlen($compressed) < strlen($content)) {
                    $response->setContent($compressed);
                    $response->headers->set('Content-Encoding', 'deflate');
                    $response->headers->set('Content-Length', strlen($compressed));
                    $response->headers->set('Vary', 'Accept-Encoding');
                    $response->headers->remove('Content-Type');
                    $response->headers->set('Content-Type', 'application/json');
                }
            }
        }

        return $response;
    }
}

