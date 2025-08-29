<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Comment;
use App\Models\OrderAssignment;
use App\Models\ProductAssignment;
use App\Models\Stage;
use App\Models\Role;
use App\Observers\AuditLogObserver;
use App\Observers\CacheObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }


    public function boot(): void
    {
        // Audit Log Observers
        Order::observe(AuditLogObserver::class);
        Product::observe(AuditLogObserver::class);
        Project::observe(AuditLogObserver::class);
        User::observe(AuditLogObserver::class);
        Client::observe(AuditLogObserver::class);
        ClientContact::observe(AuditLogObserver::class);
        Comment::observe(AuditLogObserver::class);
        OrderAssignment::observe(AuditLogObserver::class);

        // Cache Observers - автоматическая инвалидация кэша
        Order::observe(CacheObserver::class);
        Product::observe(CacheObserver::class);
        Project::observe(CacheObserver::class);
        User::observe(CacheObserver::class);
        Client::observe(CacheObserver::class);
        OrderAssignment::observe(CacheObserver::class);
        ProductAssignment::observe(CacheObserver::class);
        Stage::observe(CacheObserver::class);
        Role::observe(CacheObserver::class);
    }
}
