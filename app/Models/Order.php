<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\CacheService;

class Order extends Model
{
    use HasFactory;
    
    // Флаг для предотвращения рекурсии при переходе стадий
    protected static $isTransitioning = false;

    protected $fillable = [
        'client_id',
        'project_id',
        'product_id',
        'stage_id',
        'quantity',
        'deadline',
        'price',
        'payment_amount',
        'payment_type',
        'work_type',
        'reason',
        'reason_status',
        'archived_at',
        'is_archived',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'price' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'archived_at' => 'datetime',
        'is_archived' => 'boolean'
    ];

    protected static function booted()
    {
        static::creating(function ($order) {
            // Если stage_id не установлен, устанавливаем первую РАБОЧУЮ стадию
            if (!$order->stage_id) {
                // Оптимизация: используем whereExists вместо whereHas
                $firstWorkingStage = \App\Models\Stage::ordered()
                    ->whereExists(function ($subquery) {
                        $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('stage_roles')
                            ->whereColumn('stage_roles.stage_id', 'stages.id');
                    })
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
            
            // Устанавливаем payment_amount по умолчанию, если не установлен
            if (!isset($order->payment_amount) || $order->payment_amount === null) {
                $order->payment_amount = 0;
            }
        });

        static::created(function ($order) {
            try {
                if ($order->project) {
                    $order->project->recalculateTotalPrice();
                }
            } catch (\Exception $e) {
                \Log::error('Error recalculating project price in Order created observer', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Очищаем кэш заказов при создании нового заказа
            try {
                CacheService::invalidateOrderCaches($order->id);
            } catch (\Exception $e) {
                \Log::warning('Failed to invalidate cache', ['error' => $e->getMessage()]);
            }
        });
        static::updated(function ($order) {
            try {
                // Если проект изменился, нужно пересчитать цены обоих проектов
                if ($order->isDirty('project_id')) {
                    // Пересчитываем старый проект (если он был)
                    if ($order->getOriginal('project_id')) {
                        $oldProject = \App\Models\Project::find($order->getOriginal('project_id'));
                        if ($oldProject) {
                            $oldProject->recalculateTotalPrice();
                        }
                    }
                    
                    // Пересчитываем новый проект (если есть)
                    if ($order->project_id && $order->project) {
                        $order->project->recalculateTotalPrice();
                    }
                } elseif ($order->project) {
                    // Если проект не изменился, просто пересчитываем текущий проект
                    $order->project->recalculateTotalPrice();
                }
            } catch (\Exception $e) {
                // Логируем ошибку, но не прерываем процесс обновления
                \Log::error('Error recalculating project price in Order observer', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            // Очищаем кэш заказов при обновлении заказа
            try {
                CacheService::invalidateOrderCaches($order->id);
            } catch (\Exception $e) {
                // Игнорируем ошибки очистки кэша
                \Log::warning('Failed to invalidate cache', ['error' => $e->getMessage()]);
            }
        });
        
        static::saved(function ($order) {
            // Проверяем переход с final на completed при полной оплате
            // Используем saved событие, чтобы избежать рекурсии при обновлении
            try {
                // Пропускаем, если уже идет переход стадии (защита от рекурсии)
                if (static::$isTransitioning) {
                    return;
                }
                
                // Проверяем, что payment_amount был изменен в предыдущем обновлении
                if ($order->wasChanged('payment_amount')) {
                    // Перезагружаем отношения, чтобы получить актуальную стадию
                    $order->load('stage');
                    $currentStageName = is_string($order->stage) ? $order->stage : $order->stage->name ?? null;
                    
                    // Если заказ на стадии final и стал полностью оплачен - переходим на completed
                    if ($currentStageName === 'final' && $order->isFullyPaid() && $order->isCurrentStageApproved()) {
                        static::$isTransitioning = true;
                        try {
                            $order->moveToNextStage();
                        } finally {
                            static::$isTransitioning = false;
                        }
                    }
                }
            } catch (\Exception $e) {
                static::$isTransitioning = false;
                \Log::warning('Error checking final to completed transition', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
        static::deleted(function ($order) {
            try {
                if ($order->project) {
                    $order->project->recalculateTotalPrice();
                }
            } catch (\Exception $e) {
                \Log::error('Error recalculating project price in Order deleted observer', [
                    'order_id' => $order->id ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
            
            // Очищаем кэш заказов при удалении заказа
            try {
                $orderId = $order->id ?? null;
                CacheService::invalidateOrderCaches($orderId);
            } catch (\Exception $e) {
                \Log::warning('Failed to invalidate cache', ['error' => $e->getMessage()]);
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

        $currentStage = Stage::findByName($stageName);
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
            // Оптимизация: используем whereExists вместо whereHas
            $stageAssignments = $this->assignments()
                ->whereExists(function ($subquery) use ($stageName) {
                    $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('order_stage_assignments')
                        ->join('stages', 'order_stage_assignments.stage_id', '=', 'stages.id')
                        ->whereColumn('order_stage_assignments.order_assignment_id', 'order_assignments.id')
                        ->where('stages.name', $stageName);
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

        // Если не нашли стадию с неодобренными назначениями - проверяем оплату
        // Если все назначения одобрены и оплата полностью оплачена - возвращаем completed
        // Если все назначения одобрены, но оплата не полная - возвращаем final
        if ($this->isFullyPaid()) {
            $completedStage = Stage::findByName('completed');
            return $completedStage ? $completedStage->name : 'completed';
        } else {
            $finalStage = Stage::findByName('final');
            return $finalStage ? $finalStage->name : 'final';
        }
    }

    /**
     * Проверяет, полностью ли оплачен заказ
     */
    public function isFullyPaid()
    {
        // Если цена не указана, считаем заказ оплаченным
        if (!$this->price || $this->price <= 0) {
            return true;
        }

        // Если сумма оплаты равна или больше цены - заказ полностью оплачен
        return $this->payment_amount >= $this->price;
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
        // Оптимизация: используем whereExists вместо whereHas
        $stageAssignments = $this->assignments()
            ->whereExists(function ($subquery) use ($stageName) {
                $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('order_stage_assignments')
                    ->join('stages', 'order_stage_assignments.stage_id', '=', 'stages.id')
                    ->whereColumn('order_stage_assignments.order_assignment_id', 'order_assignments.id')
                    ->where('stages.name', $stageName);
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
            // Проверяем условия перехода на completed
            if ($nextStage === 'completed') {
                // Дополнительная проверка: заказ должен быть полностью оплачен
                if (!$this->isFullyPaid()) {
                    // Если не оплачен, переходим на final вместо completed
                    $finalStage = Stage::findByName('final');
                    if ($finalStage) {
                        $nextStage = 'final';
                    } else {
                        return false; // Не можем перейти на completed без оплаты
                    }
                } else {
                    $product = $this->product;

                    // Динамическая проверка ролей для текущей стадии
                    $currentStageModel = Stage::findByName($currentStageName);
                    if ($currentStageModel && $product) {
                        $stageRoles = $currentStageModel->roles;

                        foreach ($stageRoles as $role) {
                            // Оптимизация: используем whereExists вместо whereHas
                            $assignments = $this->assignments()
                                ->whereExists(function ($subquery) use ($role) {
                                    $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                                        ->from('user_roles')
                                        ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                                        ->whereColumn('user_roles.user_id', 'order_assignments.user_id')
                                        ->where('roles.name', $role->name);
                                })
                                ->get();

                            if ($assignments->isNotEmpty() && !$assignments->every(fn($a) => $a->status === 'approved')) {
                                return false;
                            }
                        }
                    }
                }
            }

            $oldStageName = is_string($this->stage) ? $this->stage : $this->stage->name ?? null;

            // Находим ID следующей стадии
            $nextStageModel = Stage::findByName($nextStage);
            if ($nextStageModel) {
                $this->stage_id = $nextStageModel->id;
                $this->save();
            }

            // Архивируем только при переходе на completed или cancelled
        if ($nextStage === 'completed' || $nextStage === 'cancelled') {
                $this->archive();
            } else {
                // Для всех других стадий (включая final) снимаем архив
                $this->is_archived = false;
                $this->archived_at = null;
                $this->save();
            }

            OrderStatusLog::create([
                'order_id' => $this->id,
                'from_status' => $oldStageName ?? '',
                'to_status' => $nextStage,
                'user_id' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                'changed_at' => now(),
            ]);

            return true;
        }

        return false;
    }
}
