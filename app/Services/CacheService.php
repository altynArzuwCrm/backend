<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache tags for different data types
     */
    const TAG_USERS = 'users';
    const TAG_ORDERS = 'orders';
    const TAG_PRODUCTS = 'products';
    const TAG_CLIENTS = 'clients';
    const TAG_PROJECTS = 'projects';
    const TAG_STAGES = 'stages';
    const TAG_ROLES = 'roles';
    const TAG_STATS = 'stats';
    const TAG_CATEGORIES = 'categories';

    /**
     * Cache key patterns
     */
    const PATTERN_USER_ROLES = 'user_roles_{id}';
    const PATTERN_ORDER_LIST = 'orders_{hash}';
    const PATTERN_PRODUCT_LIST = 'products_{hash}';
    const PATTERN_CLIENT_LIST = 'clients_{hash}';
    const PATTERN_STATS_MAIN = 'stats_main';
    const PATTERN_STAGES_WITH_ROLES = 'stages_with_roles';
    const PATTERN_ROLES_WITH_USERS = 'roles_with_users_and_stages';

    /**
     * Remember cache with automatic key tracking
     */
    public static function remember(string $key, int $ttl, callable $callback, array $tags = [])
    {
        $result = Cache::remember($key, $ttl, $callback);

        // Track cache keys for each tag
        foreach ($tags as $tag) {
            self::trackCacheKey($tag, $key);
        }

        return $result;
    }

    /**
     * Remember cache with tags (if Redis is available)
     */
    public static function rememberWithTags(string $key, int $ttl, callable $callback, array $tags = [])
    {
        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return self::remember($key, $ttl, $callback, $tags);
    }

    /**
     * Invalidate cache by tags
     */
    public static function invalidateByTags(array $tags): void
    {
        if (config('cache.default') === 'redis') {
            Cache::tags($tags)->flush();
            return;
        }

        // For file cache, clear tracked keys
        foreach ($tags as $tag) {
            self::clearTaggedKeys($tag);
        }
    }

    /**
     * Invalidate specific cache key
     */
    public static function invalidate(string $key): void
    {
        Cache::forget($key);
        Log::info("Cache invalidated: {$key}");
    }

    /**
     * Invalidate user-related caches
     */
    public static function invalidateUserCaches(int $userId): void
    {
        self::invalidate(sprintf(self::PATTERN_USER_ROLES, $userId));
        self::invalidateByTags([self::TAG_USERS, self::TAG_STATS]);
    }

    /**
     * Invalidate order-related caches
     */
    public static function invalidateOrderCaches(?int $orderId = null): void
    {
        if ($orderId) {
            // Invalidate specific order caches if needed
        }

        self::invalidateByTags([self::TAG_ORDERS, self::TAG_STATS]);
    }

    /**
     * Invalidate product-related caches
     */
    public static function invalidateProductCaches(?int $productId = null): void
    {
        if ($productId) {
            // Invalidate specific product caches if needed
        }

        self::invalidateByTags([self::TAG_PRODUCTS, self::TAG_STATS]);
    }

    /**
     * Invalidate category-related caches
     */
    public static function invalidateCategoryCaches(?int $categoryId = null): void
    {
        if ($categoryId) {
            // Invalidate specific category caches if needed
        }

        self::invalidateByTags([self::TAG_CATEGORIES, self::TAG_PRODUCTS, self::TAG_STATS]);
    }

    /**
     * Invalidate client-related caches
     */
    public static function invalidateClientCaches(?int $clientId = null): void
    {
        if ($clientId) {
            // Invalidate specific client caches if needed
        }

        self::invalidateByTags([self::TAG_CLIENTS, self::TAG_STATS]);
    }

    /**
     * Invalidate stage-related caches
     */
    public static function invalidateStageCaches(): void
    {
        self::invalidate(self::PATTERN_STAGES_WITH_ROLES);
        self::invalidateByTags([self::TAG_STAGES, self::TAG_ROLES]);
    }

    /**
     * Invalidate role-related caches
     */
    public static function invalidateRoleCaches(): void
    {
        self::invalidate(self::PATTERN_ROLES_WITH_USERS);
        self::invalidateByTags([self::TAG_ROLES, self::TAG_USERS]);
    }

    /**
     * Invalidate statistics caches
     */
    public static function invalidateStatsCaches(): void
    {
        self::invalidate(self::PATTERN_STATS_MAIN);
        self::invalidateByTags([self::TAG_STATS]);
    }

    /**
     * Track cache key for a specific tag
     */
    private static function trackCacheKey(string $tag, string $key): void
    {
        $trackingKey = "cache_keys_{$tag}";
        $keys = Cache::get($trackingKey, []);

        if (!in_array($key, $keys)) {
            $keys[] = $key;
            Cache::put($trackingKey, $keys, 86400); // Store for 24 hours
        }
    }

    /**
     * Clear all tracked keys for a tag
     */
    private static function clearTaggedKeys(string $tag): void
    {
        $trackingKey = "cache_keys_{$tag}";
        $keys = Cache::get($trackingKey, []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget($trackingKey);
        Log::info("Cleared {$tag} cache keys", ['count' => count($keys)]);
    }

    /**
     * Clear all application caches
     */
    public static function clearAll(): void
    {
        $tags = [
            self::TAG_USERS,
            self::TAG_ORDERS,
            self::TAG_PRODUCTS,
            self::TAG_CLIENTS,
            self::TAG_PROJECTS,
            self::TAG_STAGES,
            self::TAG_ROLES,
            self::TAG_STATS
        ];

        self::invalidateByTags($tags);
        Log::info('All application caches cleared');
    }

    /**
     * Invalidate users by stage roles cache
     */
    public static function invalidateUsersByStageRolesCache(): void
    {
        // Очищаем кэш пользователей по ролям стадий
        self::invalidateByTags([self::TAG_USERS, self::TAG_STAGES, self::TAG_ROLES]);

        // Также очищаем специфичные ключи для пользователей по ролям стадий
        $patterns = [
            'stages_users_by_roles_*',
            'users_by_stage_roles_*',
            'stages_with_roles_and_users_*'
        ];

        foreach ($patterns as $pattern) {
            $keys = Cache::get('cache_keys_' . str_replace('*', '', $pattern), []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }

        Log::info('Users by stage roles cache invalidated');
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        $stats = [];
        $tags = [
            self::TAG_USERS,
            self::TAG_ORDERS,
            self::TAG_PRODUCTS,
            self::TAG_CLIENTS,
            self::TAG_PROJECTS,
            self::TAG_STAGES,
            self::TAG_ROLES,
            self::TAG_STATS
        ];

        foreach ($tags as $tag) {
            $trackingKey = "cache_keys_{$tag}";
            $keys = Cache::get($trackingKey, []);
            $stats[$tag] = count($keys);
        }

        return $stats;
    }
}
