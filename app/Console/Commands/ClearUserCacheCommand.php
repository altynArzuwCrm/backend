<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class ClearUserCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all user-related caches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing user-related caches...');

        // Очищаем кэш пользователей по ролям стадий
        CacheService::invalidateUsersByStageRolesCache();

        // Очищаем специфичный кэш для getAllUsersByStageRoles
        Cache::forget('stages_users_by_roles_all');

        // Очищаем кэш пользователей
        CacheService::invalidateByTags([CacheService::TAG_USERS]);

        // Очищаем кэш ролей
        CacheService::invalidateByTags([CacheService::TAG_ROLES]);

        // Очищаем кэш стадий
        CacheService::invalidateByTags([CacheService::TAG_STAGES]);

        $this->info('User caches cleared successfully!');
    }
}
