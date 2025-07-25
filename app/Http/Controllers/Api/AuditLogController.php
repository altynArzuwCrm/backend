<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{

    public function index(Request $request): JsonResponse
    {

        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::with(['user', 'auditable' => function ($query) {
            if ($query->getModel() instanceof \App\Models\Order) {
                $query->with(['product', 'client']);
            }
        }]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }


        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $request->auditable_id);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 30));

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($logs),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'filters' => [
                'actions' => [
                    'created' => 'Создан',
                    'updated' => 'Обновлен',
                    'deleted' => 'Удален',
                    'restored' => 'Восстановлен',
                    'force_deleted' => 'Полностью удален',
                ],
                'models' => [
                    'Order' => 'Заказ',
                    'Product' => 'Продукт',
                    'Project' => 'Проект',
                    'User' => 'Пользователь',
                    'Client' => 'Клиент',
                    'ClientContact' => 'Контакт клиента',
                    'Comment' => 'Комментарий',
                    'OrderAssignment' => 'Назначение заказа',
                ]
            ]
        ]);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        $this->authorize('view', $auditLog);

        $auditLog->load(['user', 'auditable' => function ($query) {
            if ($query->getModel() instanceof \App\Models\Order) {
                $query->with(['product', 'client']);
            }
        }]);

        return response()->json([
            'success' => true,
            'data' => new AuditLogResource($auditLog)
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewStats', AuditLog::class);

        $baseQuery = AuditLog::query();

        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $stats = [
            'total_actions' => $baseQuery->count(),
            'actions_by_type' => (clone $baseQuery)->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action'),
            'actions_by_model' => (clone $baseQuery)->selectRaw('auditable_type, COUNT(*) as count')
                ->groupBy('auditable_type')
                ->pluck('count', 'auditable_type'),
            'most_active_users' => (clone $baseQuery)->selectRaw('user_id, COUNT(*) as count')
                ->with('user:id,name,username')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'recent_activity' => AuditLogResource::collection(
                (clone $baseQuery)->with(['user:id,name,username', 'auditable' => function ($query) {
                    // Загружаем product и client только для заказов
                    if ($query->getModel() instanceof \App\Models\Order) {
                        $query->with(['product', 'client']);
                    }
                }])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
            ),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function entityLogs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $request->validate([
            'auditable_type' => 'required|string',
            'auditable_id' => 'required|integer'
        ]);

        $logs = AuditLog::where('auditable_type', $request->auditable_type)
            ->where('auditable_id', $request->auditable_id)
            ->with(['user:id,name,username', 'auditable' => function ($query) {
                if ($query->getModel() instanceof \App\Models\Order) {
                    $query->with(['product', 'client']);
                }
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 30));

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($logs),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ]);
    }
}
