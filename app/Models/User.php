<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = ['name', 'role_id', 'phone', 'username', 'password'];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function assignedOrderItems()
    {
        return $this->hasMany(OrderItemAssignment::class, 'user_id');
    }

    public function designedOrderItems()
    {
        return $this->belongsToMany(OrderItem::class, 'order_item_designer', 'designer_id');
    }

    public function printedOrderItems()
    {
        return $this->belongsToMany(OrderItem::class, 'order_item_printer', 'printer_id');
    }

    public function workshopOrderItems()
    {
        return $this->belongsToMany(OrderItem::class, 'order_item_workshop_worker', 'workshop_worker_id');
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
