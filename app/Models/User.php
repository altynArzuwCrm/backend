<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = ['name', 'phone', 'username', 'password', 'is_active', 'image', 'fcm_token'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            // Очищаем телефон от пробелов и дефисов для уникальности
            if ($user->phone) {
                $user->phone = trim($user->phone);
            }
            
            // Обрабатываем FCM токен - пустые строки преобразуем в null
            if ($user->fcm_token === '') {
                $user->fcm_token = null;
            }
        });

        // Очищаем кэш ролей при изменении пользователя
        static::updated(function ($user) {
            if ($user->wasChanged()) {
                \Illuminate\Support\Facades\Cache::forget("user_roles_{$user->id}");
            }
        });
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles): bool
    {
        // Кэшируем роли пользователя на 2 часа
        $userRoles = \Illuminate\Support\Facades\Cache::remember(
            "user_roles_{$this->id}",
            7200,
            function () {
                return $this->roles()->pluck('name')->toArray();
            }
        );

        if (is_string($roles)) {
            return in_array($roles, $userRoles);
        }

        if (is_array($roles)) {
            return !empty(array_intersect($roles, $userRoles));
        }

        return false;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user is a power user
     */
    public function isPowerUser(): bool
    {
        // Power user теперь означает любого сотрудника с расширенными правами
        // Это может быть designer, print_operator, workshop_worker и т.д.
        return $this->hasAnyRole(['designer', 'print_operator', 'workshop_worker', 'engraving_operator', 'bukhgalter']);
    }

    /**
     * Check if user is an employee (any role except admin and manager)
     */
    public function isEmployee(): bool
    {
        // Если у пользователя есть роль admin или manager, то это не сотрудник
        if ($this->hasAnyRole(['admin', 'manager'])) {
            return false;
        }

        // Если есть любые другие роли, то это сотрудник
        return $this->roles()->exists();
    }

    /**
     * Check if user has elevated permissions (admin, manager, or any specialist role)
     */
    public function hasElevatedPermissions(): bool
    {
        return $this->hasAnyRole(['admin', 'manager', 'designer', 'print_operator', 'workshop_worker', 'engraving_operator', 'bukhgalter']);
    }

    /**
     * Check if user is staff (any role except admin and manager)
     */
    public function isStaff(): bool
    {
        return $this->isEmployee();
    }

    /**
     * Check if user is admin or manager
     */
    public function isAdminOrManager(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can access audit logs
     */
    public function canAccessAuditLogs(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can manage roles
     */
    public function canManageRoles(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can manage stages
     */
    public function canManageStages(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can perform bulk operations
     */
    public function canPerformBulkOperations(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can export data
     */
    public function canExportData(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can view analytics
     */
    public function canViewAnalytics(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can view all data
     */
    public function canViewAllData(): bool
    {
        return $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Check if user can only view their own data
     */
    public function canOnlyViewOwnData(): bool
    {
        return $this->isStaff();
    }

    public function assignedOrders()
    {
        return $this->hasMany(OrderAssignment::class, 'user_id');
    }

    public function assignments()
    {
        return $this->hasMany(OrderAssignment::class, 'user_id');
    }

    public function productAssignments()
    {
        return $this->hasMany(ProductAssignment::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
