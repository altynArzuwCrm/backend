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

    public function project()
    {
        return $this->belongsTo(Project::class)->withDefault([
            'name' => 'Неизвестный проект',
            'total_price' => 0
        ]);
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
        $stages = ['draft', 'design', 'print', 'engraving', 'workshop', 'final', 'completed'];
        $currentIndex = array_search($this->stage, $stages);

        if ($currentIndex === false || $currentIndex >= count($stages) - 1) {
            return null;
        }

        $product = $this->product;

        for ($i = $currentIndex + 1; $i < count($stages); $i++) {
            $nextStage = $stages[$i];

            if ($nextStage === 'design' && !$product->has_design_stage) {
                continue;
            }
            if ($nextStage === 'print' && !$product->has_print_stage) {
                continue;
            }
            if ($nextStage === 'engraving' && !$product->has_engraving_stage) {
                continue;
            }
            if ($nextStage === 'workshop' && !$product->has_workshop_stage) {
                continue;
            }

            return $nextStage;
        }

        return 'completed';
    }

    public function isCurrentStageApproved()
    {
        $stage = $this->stage;
        $product = $this->product;

        $roleMap = [
            'design' => ['has_design_stage', 'designer', 'has_design_stage'],
            'print' => ['has_print_stage', 'print_operator', 'has_print_stage'],
            'engraving' => ['has_engraving_stage', 'engraving_operator', 'has_engraving_stage'],
            'workshop' => ['has_workshop_stage', 'workshop_worker', 'has_workshop_stage'],
        ];

        if (!isset($roleMap[$stage])) {
            return true;
        }

        [$flag, $role, $stageField] = $roleMap[$stage];

        if (!$product || !$product->$flag) {
            return true;
        }

        // Только назначения с нужной ролью и чекбоксом стадии
        $assignments = $this->assignments()
            ->where('role_type', $role)
            ->where($stageField, true)
            ->get();

        return $assignments->isNotEmpty() && $assignments->every(fn($a) => $a->status === 'approved');
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
                    'engraving' => ['has_engraving_stage', 'print_operator_id', 'print_operator'],
                    'workshop' => ['has_workshop_stage', 'workshop_worker_id', 'workshop_worker'],
                ];

                if (isset($roleMap[$currentStage])) {
                    [$flag, $userField, $role] = $roleMap[$currentStage];

                    if ($product->$flag && $product->$userField) {
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
