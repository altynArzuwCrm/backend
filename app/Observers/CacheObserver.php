<?php

namespace App\Observers;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class CacheObserver
{
    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->invalidateRelatedCaches($model, 'created');
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->invalidateRelatedCaches($model, 'updated');
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->invalidateRelatedCaches($model, 'deleted');
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->invalidateRelatedCaches($model, 'restored');
    }

    /**
     * Handle the model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        $this->invalidateRelatedCaches($model, 'force_deleted');
    }

    /**
     * Invalidate caches related to the model
     */
    private function invalidateRelatedCaches(Model $model, string $action): void
    {
        $modelClass = get_class($model);

        switch ($modelClass) {
            case \App\Models\User::class:
                $this->handleUserCache($model, $action);
                break;

            case \App\Models\Order::class:
                $this->handleOrderCache($model, $action);
                break;

            case \App\Models\Product::class:
                $this->handleProductCache($model, $action);
                break;

            case \App\Models\Client::class:
                $this->handleClientCache($model, $action);
                break;

            case \App\Models\Project::class:
                $this->handleProjectCache($model, $action);
                break;

            case \App\Models\Stage::class:
                $this->handleStageCache($model, $action);
                break;

            case \App\Models\Role::class:
                $this->handleRoleCache($model, $action);
                break;

            case \App\Models\OrderAssignment::class:
                $this->handleOrderAssignmentCache($model, $action);
                break;

            case \App\Models\ProductAssignment::class:
                $this->handleProductAssignmentCache($model, $action);
                break;
        }
    }

    /**
     * Handle user-related cache invalidation
     */
    private function handleUserCache(Model $user, string $action): void
    {
        CacheService::invalidateUserCaches($user->id);

        // If user roles changed, also invalidate role caches
        if ($action === 'updated' && $user->isDirty(['role_id'])) {
            CacheService::invalidateRoleCaches();
        }
    }

    /**
     * Handle order-related cache invalidation
     */
    private function handleOrderCache(Model $order, string $action): void
    {
        CacheService::invalidateOrderCaches($order->id);

        // Orders affect statistics
        CacheService::invalidateStatsCaches();

        // If order stage changed, invalidate stage caches
        if ($action === 'updated' && $order->isDirty(['stage_id'])) {
            CacheService::invalidateStageCaches();
        }
    }

    /**
     * Handle product-related cache invalidation
     */
    private function handleProductCache(Model $product, string $action): void
    {
        CacheService::invalidateProductCaches($product->id);

        // Products affect statistics
        CacheService::invalidateStatsCaches();
    }

    /**
     * Handle client-related cache invalidation
     */
    private function handleClientCache(Model $client, string $action): void
    {
        CacheService::invalidateClientCaches($client->id);

        // Clients affect statistics
        CacheService::invalidateStatsCaches();
    }

    /**
     * Handle project-related cache invalidation
     */
    private function handleProjectCache(Model $project, string $action): void
    {
        CacheService::invalidateByTags([CacheService::TAG_PROJECTS, CacheService::TAG_STATS]);
    }

    /**
     * Handle stage-related cache invalidation
     */
    private function handleStageCache(Model $stage, string $action): void
    {
        CacheService::invalidateStageCaches();

        // Stages affect orders and roles
        CacheService::invalidateByTags([CacheService::TAG_ORDERS, CacheService::TAG_ROLES]);
    }

    /**
     * Handle role-related cache invalidation
     */
    private function handleRoleCache(Model $role, string $action): void
    {
        CacheService::invalidateRoleCaches();

        // Roles affect users and stages
        CacheService::invalidateByTags([CacheService::TAG_USERS, CacheService::TAG_STAGES]);
    }

    /**
     * Handle order assignment cache invalidation
     */
    private function handleOrderAssignmentCache(Model $assignment, string $action): void
    {
        // Order assignments affect order lists and user caches
        CacheService::invalidateOrderCaches();
        CacheService::invalidateUserCaches($assignment->user_id);
    }

    /**
     * Handle product assignment cache invalidation
     */
    private function handleProductAssignmentCache(Model $assignment, string $action): void
    {
        // Product assignments affect product lists and user caches
        CacheService::invalidateProductCaches();
        CacheService::invalidateUserCaches($assignment->user_id);
    }
}
