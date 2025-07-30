<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = ['name', 'phone', 'username', 'password', 'is_active', 'image'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            // Очищаем телефон от пробелов и дефисов для уникальности
            if ($user->phone) {
                $user->phone = trim($user->phone);
            }
        });
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasAnyRole($roleNames)
    {
        if (is_string($roleNames)) {
            $roleNames = [$roleNames];
        }
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    public function hasAllRoles($roleNames)
    {
        if (is_string($roleNames)) {
            $roleNames = [$roleNames];
        }
        $userRoleCount = $this->roles()->whereIn('name', $roleNames)->count();
        return $userRoleCount === count($roleNames);
    }

    public function assignedOrders()
    {
        return $this->hasMany(OrderAssignment::class, 'user_id');
    }

    public function assignments()
    {
        return $this->hasMany(OrderAssignment::class, 'user_id');
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
