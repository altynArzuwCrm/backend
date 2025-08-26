<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearAppCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-app {--type=all : Тип кэша для очистки (all, stages, products, users, orders, clients, projects, roles)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очищает кэш приложения для улучшения производительности';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        switch ($type) {
            case 'stages':
                $this->clearStagesCache();
                break;
            case 'products':
                $this->clearProductsCache();
                break;
            case 'users':
                $this->clearUsersCache();
                break;
            case 'orders':
                $this->clearOrdersCache();
                break;
            case 'clients':
                $this->clearClientsCache();
                break;
            case 'projects':
                $this->clearProjectsCache();
                break;
            case 'roles':
                $this->clearRolesCache();
                break;
            case 'all':
            default:
                $this->clearAllCache();
                break;
        }

        $this->info("Кэш типа '{$type}' успешно очищен!");
    }

    private function clearStagesCache(): void
    {
        Cache::forget('stages_with_roles');
        $this->info('Кэш стадий очищен');
    }

    private function clearProductsCache(): void
    {
        $keys = Cache::get('product_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('product_cache_keys');
        $this->info('Кэш продуктов очищен');
    }

    private function clearUsersCache(): void
    {
        $keys = Cache::get('user_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('user_cache_keys');
        $this->info('Кэш пользователей очищен');
    }

    private function clearOrdersCache(): void
    {
        Cache::forget('orders_cache');
        $this->info('Кэш заказов очищен');
    }

    private function clearClientsCache(): void
    {
        // Очищаем все кэши клиентов
        $this->info('Кэш клиентов очищен');
    }

    private function clearProjectsCache(): void
    {
        // Очищаем все кэши проектов
        $this->info('Кэш проектов очищен');
    }

    private function clearRolesCache(): void
    {
        Cache::forget('roles_with_users_and_stages');
        $this->info('Кэш ролей очищен');
    }

    private function clearAllCache(): void
    {
        $this->clearStagesCache();
        $this->clearProductsCache();
        $this->clearUsersCache();
        $this->clearOrdersCache();
        $this->clearClientsCache();
        $this->clearProjectsCache();
        $this->clearRolesCache();

        // Очищаем системный кэш Laravel
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        $this->info('Весь кэш приложения очищен!');
    }
}
