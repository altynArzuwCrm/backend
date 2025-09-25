<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Stage;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class DiagnoseUserCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:diagnose-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose user cache issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Diagnosing user cache issues...');

        // Проверяем активных пользователей
        $activeUsers = User::where('is_active', true)->with('roles')->get();
        $this->info("Active users: {$activeUsers->count()}");

        foreach ($activeUsers as $user) {
            $this->line("- {$user->name} ({$user->username}) - Roles: " . $user->roles->pluck('name')->join(', '));
        }

        // Проверяем стадии с ролями
        $stages = Stage::with('roles')->get();
        $this->info("\nStages with roles:");

        foreach ($stages as $stage) {
            $this->line("- {$stage->name} - Roles: " . $stage->roles->pluck('name')->join(', '));
        }

        // Проверяем кэш
        $cacheKey = 'stages_users_by_roles_all';
        $cached = Cache::get($cacheKey);

        if ($cached) {
            $this->info("\nCache '{$cacheKey}' exists with " . count($cached) . " entries");
        } else {
            $this->info("\nCache '{$cacheKey}' is empty");
        }

        // Проверяем теги кэша
        $this->info("\nCache tags status:");
        $tags = [CacheService::TAG_USERS, CacheService::TAG_STAGES, CacheService::TAG_ROLES];
        foreach ($tags as $tag) {
            $trackingKey = "cache_keys_{$tag}";
            $keys = Cache::get($trackingKey, []);
            $this->line("- {$tag}: " . count($keys) . " keys");
        }

        $this->info("\nDiagnosis complete!");
    }
}
