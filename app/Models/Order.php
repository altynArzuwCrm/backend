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
        'stage_id',
        'quantity',
        'deadline',
        'price',
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
        static::creating(function ($order) {
            // Если stage_id не установлен, устанавливаем первую РАБОЧУЮ стадию
            if (!$order->stage_id) {
                $firstWorkingStage = \App\Models\Stage::ordered()
                    ->whereHas('roles')
                    ->first();
                if ($firstWorkingStage) {
                    $order->stage_id = $firstWorkingStage->id;
                } else {
                    // Fallback к первой стадии, если нет рабочих стадий
                    $firstStage = \App\Models\Stage::where('order', 1)->first();
                    if ($firstStage) {
                        $order->stage_id = $firstStage->id;
                    }
                }
            }
        });

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

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function currentStage()
    {
        return $this->belongsTo(Stage::class);
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
        $stageName = is_string($this->stage) ? $this->stage : $this->stage->name ?? null;
        if (!$stageName) {
            return null;
        }

        $currentStage = Stage::where('name', $stageName)->first();
        if (!$currentStage) {
            return null;
        }

        $product = $this->product;

        // Ищем следующую стадию с неодобренными назначениями
        $nextStage = $currentStage->getNextStage();
        while ($nextStage) {
            $stageName = $nextStage->name;

            // Проверяем, поддерживает ли продукт эту стадию
            if ($product && !$product->hasStage($stageName)) {
                $nextStage = $nextStage->getNextStage();
                continue;
            }

            // Проверяем, есть ли назначения для этой стадии
            $stageAssignments = $this->assignments()
                ->whereHas('orderStageAssignments', function ($q) use ($stageName) {
                    $q->whereHas('stage', function ($sq) use ($stageName) {
                        $sq->where('name', $stageName);
                    });
                })
                ->get();

            // Если есть назначения и хотя бы одно не одобрено - возвращаем эту стадию
            if ($stageAssignments->isNotEmpty()) {
                foreach ($stageAssignments as $assignment) {
                    if ($assignment->status !== 'approved') {
                        return $stageName; // Нашли стадию с неодобренными назначениями
                    }
                }
            }

            // Если нет назначений или все одобрены - ищем дальше
            $nextStage = $nextStage->getNextStage();
        }

        // Если не нашли стадию с неодобренными назначениями - возвращаем completed
        $completedStage = Stage::where('name', 'completed')->first();
        return $completedStage ? $completedStage->name : 'completed';
    }

    public function isCurrentStageApproved()
    {
        $stageName = is_string($this->stage) ? $this->stage : $this->stage->name ?? null;
        $product = $this->product;

        if (!$stageName) {
            return true; // No stage, consider approved
        }

        $currentStage = Stage::with('roles')->where('name', $stageName)->first();
        if (!$currentStage) {
            return true; // Unknown stage, consider approved
        }

        // Check if product supports this stage using new dynamic system
        if (!$product || !$product->hasStage($stageName)) {
            return true; // Stage not supported by product, consider approved
        }

        // Get ALL assignments for the current stage (not just required roles)
        $stageAssignments = $this->assignments()
            ->whereHas('orderStageAssignments', function ($q) use ($stageName) {
                $q->whereHas('stage', function ($sq) use ($stageName) {
                    $sq->where('name', $stageName);
                });
            })
            ->get();

        // If no assignments for this stage, consider it approved
        if ($stageAssignments->isEmpty()) {
            return true;
        }

        // Check if ALL assignments for this stage are approved
        foreach ($stageAssignments as $assignment) {
            if ($assignment->status !== 'approved') {
                return false; // At least one assignment is not approved
            }
        }

        return true; // All assignments are approved
    }

    public function moveToNextStage()
    {
        $nextStage = $this->getNextStage();
        $currentStageName = is_string($this->stage) ? $this->stage : $this->stage->name ?? null;

        if ($nextStage && $nextStage !== $currentStageName) {
            if ($nextStage === 'completed') {
                $product = $this->product;

                // Динамическая проверка ролей для текущей стадии
                $currentStageModel = Stage::where('name', $currentStageName)->first();
                if ($currentStageModel && $product) {
                    $stageRoles = $currentStageModel->roles;

                    foreach ($stageRoles as $role) {
                        $assignments = $this->assignments()
                            ->whereHas('user.roles', function ($q) use ($role) {
                                $q->where('name', $role->name);
                            })
                            ->get();

                        if ($assignments->isNotEmpty() && !$assignments->every(fn($a) => $a->status === 'approved')) {
                            return false;
                        }
                    }
                }
            }

            $oldStageName = is_string($this->stage) ? $this->stage : $this->stage->name ?? null;

            // Находим ID следующей стадии
            $nextStageModel = Stage::where('name', $nextStage)->first();
            if ($nextStageModel) {
                $this->stage_id = $nextStageModel->id;
                $this->save();
            }

            if ($nextStage === 'completed' || $nextStage === 'cancelled') {
                $this->archive();
            } else {
                $this->is_archived = false;
                $this->archived_at = null;
                $this->save();
            }

            OrderStatusLog::create([
                'order_id' => $this->id,
                'from_status' => $oldStageName,
                'to_status' => $nextStage,
                'user_id' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                'changed_at' => now(),
            ]);

            return true;
        }

        return false;
    }
}
