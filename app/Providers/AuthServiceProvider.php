<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Stage;
use App\Models\Role;
use App\Policies\AuditLogPolicy;
use App\Policies\ClientPolicy;
use App\Policies\CommentPolicy;
use App\Policies\OrderAssignmentPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\UserPolicy;
use App\Policies\StagePolicy;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Client::class => ClientPolicy::class,
        Project::class => ProjectPolicy::class,
        Order::class => OrderPolicy::class,
        Comment::class => CommentPolicy::class,
        Product::class => ProductPolicy::class,
        OrderAssignment::class => OrderAssignmentPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Stage::class => StagePolicy::class,
        Role::class => RolePolicy::class,
    ];
}
