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
use App\Observers\AuditLogObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Регистрируем Observer для аудит-логов
        Order::observe(AuditLogObserver::class);
        Product::observe(AuditLogObserver::class);
        Project::observe(AuditLogObserver::class);
        User::observe(AuditLogObserver::class);
        Client::observe(AuditLogObserver::class);
        ClientContact::observe(AuditLogObserver::class);
        Comment::observe(AuditLogObserver::class);
        OrderAssignment::observe(AuditLogObserver::class);
    }
}
