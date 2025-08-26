<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\Product;
use App\Models\Project;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\ClientPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\OrderAssignmentPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PowerUserPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RolePolicy;
use App\Policies\StagePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Order::class => OrderPolicy::class,
        OrderAssignment::class => OrderAssignmentPolicy::class,
        Product::class => ProductPolicy::class,
        Project::class => ProjectPolicy::class,
        Client::class => ClientPolicy::class,
        Role::class => RolePolicy::class,
        Stage::class => StagePolicy::class,
        AuditLog::class => AuditLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Register additional policies
        Gate::policy('power-user', PowerUserPolicy::class);
        Gate::policy('dashboard', DashboardPolicy::class);
    }
}
