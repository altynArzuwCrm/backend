<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\HandleNullRelations;
use App\Http\Middleware\ClearCacheMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'role' => RoleMiddleware::class,
            'handle.null.relations' => HandleNullRelations::class,
            'clear.cache' => ClearCacheMiddleware::class,
        ]);

        // Добавляем CORS middleware для решения проблем с cross-origin
        $middleware->prepend(HandleCors::class);
        
        // Добавляем CORS middleware для API routes
        $middleware->api(prepend: [
            HandleCors::class,
        ]);

        // Добавляем middleware для автоматической очистки кэша
        $middleware->append(ClearCacheMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
