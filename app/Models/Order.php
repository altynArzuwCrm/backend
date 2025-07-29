<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'project_id',
        'product_id',
        'quantity',
        'deadline',
        'price',
        'stage',
        'work_type',
        'reason',
        'reason_status',
        'archived_at',
        'is_archived',
        'designer_id',
        'print_operator_id',
        'workshop_worker_id',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'price' => 'decimal:2',
        'archived_at' => 'datetime',
        'is_archived' => 'boolean'
    ];

    protected static function booted()
    {
        static::created(function ($order) {
            if ($order->project) {
                $order->project->recalculateTotalPrice();
            }
        });
        static::updated(function ($order) {
            if ($order->project) {
                $order->project->recalculateTotalPrice();
            }
        });
        static::deleted(function ($order) {
            if ($order->project) {
                $order->project->recalculateTotalPrice();
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withDefault([
            'name' => 'Неизвестный клиент',
            'company_name' => 'Неизвестная компания'
        ]);
    }

    public function assignments()
    {
        return $this->hasMany(OrderAssignment::class);
    }

    public function currentStage()
    {
        return $this->belongsTo(Stage::class, 'stage', 'name');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function getDisplayNameAttribute()
    {
        $product = $this->product;
        $client = $this->client;

        if ($product && $client) {
            return "Заказ #{$this->id} - {$product->name} для {$client->name}";
        } elseif ($product) {
            return "Заказ #{$this->id} - {$product->name}";
        } elseif ($client) {
            return "Заказ #{$this->id} для {$client->name}";
        } else {
            return "Заказ #{$this->id}";
        }
    }

    public function archive()
    {
        $this->is_archived = true;
        $this->archived_at = now();
        $this->save();
    }

    public function getNextStage()
    {
        $currentStage = Stage::where('name', $this->stage)->first();
        if (!$currentStage) {
            return null;
        }

        $nextStage = $currentStage->getNextStage();
        if (!$nextStage) {
            return null;
        }

        $product = $this->product;
        if (!$product) {
            return $nextStage->name;
        }

        // Keep looking for next available stage if current one is not supported by product
        while ($nextStage) {
            $stageName = $nextStage->name;

            // Check if product supports this stage using new dynamic system
            if (!$product->hasStage($stageName)) {
                $nextStage = $nextStage->getNextStage();
                continue;
            }

            return $stageName;
        }

        // If no next stage found, return completed
        $completedStage = Stage::where('name', 'completed')->first();
        return $completedStage ? $completedStage->name : 'completed';
    }

    public function isCurrentStageApproved()
    {
        $stageName = $this->stage;
        $product = $this->product;

        $currentStage = Stage::with('roles')->where('name', $stageName)->first();
        if (!$currentStage) {
            return true; // Unknown stage, consider approved
        }

        // Get required roles for this stage
        $requiredRoles = $currentStage->roles()
            ->wherePivot('is_required', true)
            ->get();

        if ($requiredRoles->isEmpty()) {
            return true; // No required roles, consider approved
        }

        // Check if product supports this stage using new dynamic system
        if (!$product || !$product->hasStage($stageName)) {
            return true; // Stage not supported by product, consider approved
        }

        // Check if all required roles have approved assignments
        foreach ($requiredRoles as $role) {
            $assignments = $this->assignments()
                ->where('role_type', $role->name)
                ->whereHas('orderStageAssignments', function ($q) use ($stageName) {
                    $q->whereHas('stage', function ($sq) use ($stageName) {
                        $sq->where('name', $stageName);
                    });
                })
                ->get();

            if ($assignments->isEmpty() || $assignments->every(fn($a) => $a->status !== 'approved')) {
                return false; // Required role not approved
            }
        }

        return true;
    }

    public function moveToNextStage()
    {
        $nextStage = $this->getNextStage();

        if ($nextStage && $nextStage !== $this->stage) {
            if ($nextStage === 'completed') {
                $currentStage = $this->stage;
                $product = $this->product;

                $roleMap = [
                    'design' => ['has_design_stage', 'designer_id', 'designer'],
                    'print' => ['has_print_stage', 'print_operator_id', 'print_operator'],
                    'engraving' => ['has_engraving_stage', null, 'engraving_operator'],
                    'workshop' => ['has_workshop_stage', 'workshop_worker_id', 'workshop_worker'],
                ];

                if (isset($roleMap[$currentStage])) {
                    [$flag, $userField, $role] = $roleMap[$currentStage];

                    if ($product->$flag) {
                        // Для engraving_operator_id проверяем только флаг, так как поля нет
                        if ($userField && $product->$userField) {
                            $assignments = $this->assignments()
                                ->whereHas('user.roles', function ($q) use ($role) {
                                    $q->where('name', $role);
                                })
                                ->get();

                            if ($assignments->isNotEmpty() && !$assignments->every(fn($a) => $a->status === 'approved')) {
                                return false;
                            }
                        } else {
                            $assignments = $this->assignments()
                                ->whereHas('user.roles', function ($q) use ($role) {
                                    $q->where('name', $role);
                                })
                                ->get();

                            if ($assignments->isNotEmpty() && !$assignments->every(fn($a) => $a->status === 'approved')) {
                                return false;
                            }
                        }
                    }
                }
            }

            $oldStage = $this->stage;
            $this->stage = $nextStage;
            $this->save();

            if ($nextStage === 'completed' || $nextStage === 'cancelled') {
                $this->archive();
            } else {
                $this->is_archived = false;
                $this->archived_at = null;
                $this->save();
            }

            OrderStatusLog::create([
                'order_id' => $this->id,
                'from_status' => $oldStage,
                'to_status' => $this->stage,
                'user_id' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                'changed_at' => now(),
            ]);

            return true;
        }

        return false;
    }
}
