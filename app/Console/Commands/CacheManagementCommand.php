<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheManagementCommand extends Command
{
    protected $signature = 'cache:manage 
                            {action : Действие (clear, stats, invalidate)}
                            {--type= : Тип кэша для очистки (users, orders, products, clients, projects, stages, roles, stats, all)}
                            {--key= : Конкретный ключ кэша для инвалидации}';

    
    protected $description = 'Управление кэшем приложения через CacheService';

    
    public function handle()
    {
        $action = $this->argument('action');
        $type = $this->option('type');
        $key = $this->option('key');

        switch ($action) {
            case 'clear':
                $this->handleClear($type);
                break;
            case 'stats':
                $this->handleStats();
                break;
            case 'invalidate':
                $this->handleInvalidate($key);
                break;
            default:
                $this->error('Неизвестное действие. Доступные: clear, stats, invalidate');
                return 1;
        }

        return 0;
    }

    
    private function handleClear(?string $type): void
    {
        if (!$type) {
            $type = $this->choice(
                'Какой тип кэша очистить?',
                ['users', 'orders', 'products', 'clients', 'projects', 'stages', 'roles', 'stage-roles', 'stats', 'all'],
                'all'
            );
        }

        switch ($type) {
            case 'users':
                CacheService::invalidateByTags([CacheService::TAG_USERS]);
                CacheService::invalidateUsersByStageRolesCache();
                $this->info('Кэш пользователей и пользователей по ролям стадий очищен');
                break;
            case 'orders':
                CacheService::invalidateByTags([CacheService::TAG_ORDERS]);
                $this->info('Кэш заказов очищен');
                break;
            case 'products':
                CacheService::invalidateByTags([CacheService::TAG_PRODUCTS]);
                $this->info('Кэш продуктов очищен');
                break;
            case 'clients':
                CacheService::invalidateByTags([CacheService::TAG_CLIENTS]);
                $this->info('Кэш клиентов очищен');
                break;
            case 'projects':
                CacheService::invalidateByTags([CacheService::TAG_PROJECTS]);
                $this->info('Кэш проектов очищен');
                break;
            case 'stages':
                CacheService::invalidateStageCaches();
                $this->info('Кэш стадий очищен');
                break;
            case 'roles':
                CacheService::invalidateRoleCaches();
                CacheService::invalidateUsersByStageRolesCache();
                $this->info('Кэш ролей и пользователей по ролям стадий очищен');
                break;
            case 'stage-roles':
                CacheService::invalidateUsersByStageRolesCache();
                $this->info('Кэш пользователей по ролям стадий очищен');
                break;
            case 'stats':
                CacheService::invalidateStatsCaches();
                $this->info('Кэш статистики очищен');
                break;
            case 'all':
                CacheService::clearAll();
                $this->info('Весь кэш приложения очищен');
                break;
            default:
                $this->error("Неизвестный тип кэша: {$type}");
        }
    }

    
    private function handleStats(): void
    {
        $stats = CacheService::getStats();

        $this->info('Статистика кэша:');
        $this->table(
            ['Тип кэша', 'Количество ключей'],
            collect($stats)->map(function ($count, $type) {
                return [$type, $count];
            })->toArray()
        );

        $this->info('Драйвер кэша: ' . config('cache.default'));
    }

    
    private function handleInvalidate(?string $key): void
    {
        if (!$key) {
            $key = $this->ask('Введите ключ кэша для инвалидации');
        }

        if (!$key) {
            $this->error('Ключ кэша не указан');
            return;
        }

        CacheService::invalidate($key);
        $this->info("Кэш ключ '{$key}' инвалидирован");
    }
}
