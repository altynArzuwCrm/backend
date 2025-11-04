<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Client;
use App\Models\Project;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function index()
    {
        $stats = CacheService::rememberWithTags(
            CacheService::PATTERN_STATS_MAIN,
            10, // 10 секунд
            function () {
                $users = User::count();
                $orders = Order::count();
                $newClients = Client::where('created_at', '>=', Carbon::now()->subDays(30))->count();
                return [
                    'users' => $users,
                    'orders' => $orders,
                    'newClients' => $newClients,
                ];
            },
            [CacheService::TAG_STATS]
        );
        return response()->json($stats);
    }

    /**
     * Получить выручку по месяцам
     * ВАЖНО: Теперь используется общая сумма проектов и заказов, а не оплаченная сумма
     */
    public function revenueByMonth(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Проверяем права доступа
        if (!$user->is_active) {
            return response()->json(['message' => 'Ваш аккаунт деактивирован'], 403);
        }

        // Только админы и менеджеры могут видеть статистику выручки
        if (!$user->isAdminOrManager()) {
            return response()->json(['message' => 'Недостаточно прав'], 403);
        }

        $year = $request->get('year', Carbon::now()->year);

        // Кэшируем данные о выручке на 1 минуту
        // ВАЖНО: Теперь выручка считается по общей сумме, а не по оплаченной
        $revenueData = Cache::remember("revenue_by_month_{$year}", 60, function () use ($year) {
            // Получаем выручку по месяцам из проектов (используем total_price - общую сумму)
            // Это общая стоимость всех проектов, а не только оплаченных
            $monthlyRevenue = Project::selectRaw('
                MONTH(created_at) as month,
                SUM(total_price) as revenue
            ')
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            // Получаем выручку по месяцам из заказов (используем price - цену заказа)
            // Это общая стоимость всех заказов, а не только оплаченных
            $monthlyOrderRevenue = Order::selectRaw('
                MONTH(created_at) as month,
                SUM(price) as revenue
            ')
                ->whereYear('created_at', $year)
                ->whereNull('project_id')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            // Объединяем данные
            // ВАЖНО: Теперь выручка считается по общей сумме проектов и заказов, а не по оплаченным суммам
            // Это дает полную картину потенциальной выручки компании
            $result = [];
            $totalRevenue = 0;

            for ($month = 1; $month <= 12; $month++) {
                $projectRevenue = $monthlyRevenue->get($month)?->revenue ?? 0;
                $orderRevenue = $monthlyOrderRevenue->get($month)?->revenue ?? 0;
                $monthRevenue = $projectRevenue + $orderRevenue;

                $result[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($year, $month, 1)->format('M'),
                    'revenue' => $monthRevenue,
                    'revenue_formatted' => number_format($monthRevenue, 0, '.', ' ')
                ];

                $totalRevenue += $monthRevenue;
            }

            return [
                'monthly_data' => $result,
                'total_revenue' => $totalRevenue,
                'total_revenue_formatted' => number_format($totalRevenue, 0, '.', ' '),
                'year' => $year
                // ВАЖНО: total_revenue теперь содержит общую сумму всех проектов и заказов
            ];
        });

        // Возвращаем данные о выручке (общая сумма, а не оплаченная)
        return response()->json($revenueData);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Проверяем права доступа
        if (!$user->is_active) {
            return response()->json(['message' => 'Ваш аккаунт деактивирован'], 403);
        }

        // Кэшируем дашборд на 30 секунд для уменьшения нагрузки
        $cacheKey = 'dashboard_' . $user->id . '_' . ($user->isStaff() ? 'employee' : 'admin');
        return CacheService::rememberWithTags($cacheKey, 30, function () use ($user) {
            // Если пользователь сотрудник, показываем только его данные
            if ($user->isStaff()) {
                return $this->getEmployeeDashboard($user);
            }

            // Для админов и менеджеров показываем полную статистику
            return $this->getFullDashboard();
        }, [CacheService::TAG_STATS]);
    }

    private function getEmployeeDashboard($user)
    {
        // Оптимизация: используем один запрос для получения всех статистик
        $stats = \App\Models\OrderAssignment::where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total_assignments,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_assignments,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_assignments,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_assignments
            ')
            ->first();
        
        $totalAssignments = $stats->total_assignments ?? 0;
        $completedAssignments = $stats->completed_assignments ?? 0;
        $pendingAssignments = $stats->pending_assignments ?? 0;
        $inProgressAssignments = $stats->in_progress_assignments ?? 0;

        // Загружаем только для recentAssignments (максимум 20 последних)
        $userAssignments = \App\Models\OrderAssignment::where('user_id', $user->id)
            ->with(['order' => function ($q) {
                $q->select('id', 'product_id', 'stage_id', 'deadline');
            }, 'order.product:id,name', 'order.stage:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $recentAssignments = $userAssignments
            ->sortByDesc('created_at')
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->order_id,
                    'product_name' => $assignment->order?->product?->name,
                    'stage' => $assignment->order?->stage?->name,
                    'status' => $assignment->status,
                    'deadline' => $assignment->order?->deadline,
                ];
            })
            ->filter(function ($assignment) {
                // Показываем только назначения с существующими заказами
                return $assignment['product_name'] !== null;
            });

        // Оптимизация: используем join вместо whereHas
        $delayedAssignments = DB::table('order_assignments')
            ->join('orders', 'order_assignments.order_id', '=', 'orders.id')
            ->where('order_assignments.user_id', $user->id)
            ->where('orders.deadline', '<', now())
            ->whereNotIn('order_assignments.status', ['completed', 'cancelled'])
            ->count();

        return response()->json([
            'user_stats' => [
                'total_assignments' => $totalAssignments,
                'completed_assignments' => $completedAssignments,
                'pending_assignments' => $pendingAssignments,
                'in_progress_assignments' => $inProgressAssignments,
                'delayed_assignments' => $delayedAssignments,
            ],
            'recent_assignments' => $recentAssignments,
            'is_employee_view' => true,
        ]);
    }

    private function getFullDashboard()
    {
        // Оптимизация: используем группировку на уровне БД вместо загрузки всех заказов
        $ordersByStage = DB::table('orders')
            ->join('stages', 'orders.stage_id', '=', 'stages.id')
            ->select('stages.name', DB::raw('count(*) as total'))
            ->groupBy('stages.name')
            ->get()
            ->pluck('total', 'name');

        // Оптимизация: предзагружаем всех пользователей и их назначения одним запросом
        $ordersByUserData = DB::table('order_assignments')
            ->select('user_id', DB::raw('count(*) as total'))
            ->whereNull('deleted_at')
            ->groupBy('user_id')
            ->get();
        
        $userIds = $ordersByUserData->pluck('user_id')->unique();
        $usersById = \App\Models\User::with('roles')->whereIn('id', $userIds)->get()->keyBy('id');
        
        // Оптимизация: загружаем только необходимые поля и ограничиваем количество
        $allAssignments = \App\Models\OrderAssignment::whereIn('user_id', $userIds)
            ->whereNull('deleted_at')
            ->select('id', 'order_id', 'user_id', 'status')
            ->with([
                'order' => function ($q) {
                    $q->select('id', 'product_id', 'stage_id');
                },
                'order.product' => function ($q) {
                    $q->select('id', 'name');
                },
                'order.stage' => function ($q) {
                    $q->select('id', 'name');
                },
                'user' => function ($q) {
                    $q->select('id', 'name', 'username');
                }
            ])
            ->limit(1000) // Ограничиваем для производительности
            ->get()
            ->groupBy('user_id');

        $ordersByUser = $ordersByUserData->map(function ($row) use ($usersById, $allAssignments) {
            $user = $usersById->get($row->user_id);
            $userAssignments = $allAssignments->get($row->user_id, collect());
            
            $orders = $userAssignments->map(function ($a) {
                return [
                    'id' => $a->order_id,
                    'product_name' => $a->order?->product?->name,
                    'stage' => $a->order?->stage?->name,
                    'status' => $a->status,
                ];
            });
            
            return [
                'user_id' => $row->user_id,
                'user_name' => $user?->name,
                'total' => $row->total,
                'orders' => $orders,
                'roles' => $user?->roles?->map(function($role) {
                    return [
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                    ];
                }),
            ];
        })
        ->filter(function ($userData) {
            return $userData['orders'] && count($userData['orders']) > 0;
        });

        // Оптимизация: используем join вместо whereHas
        $closedCount = DB::table('orders')
            ->join('stages', 'orders.stage_id', '=', 'stages.id')
            ->where('stages.name', 'completed')
            ->where('orders.archived_at', '>=', now()->subDays(30))
            ->count();

        // Оптимизация: используем join вместо whereHas
        $delayedAssignmentsQuery = DB::table('order_assignments')
            ->join('orders', 'order_assignments.order_id', '=', 'orders.id')
            ->where('orders.deadline', '<', now())
            ->whereNull('order_assignments.deleted_at');
        
        $delayedAssignments = $delayedAssignmentsQuery->count();

        // Оптимизация: используем прямые запросы вместо whereHas
        $delayedOrderIds = DB::table('orders')
            ->where('deadline', '<', now())
            ->pluck('id');
            
        $delayedAssignmentsList = \App\Models\OrderAssignment::whereIn('order_id', $delayedOrderIds)
            ->whereNull('deleted_at')
            ->with(['user', 'order.stage'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'user_name' => $assignment->user?->name,
                    'order_id' => $assignment->order_id,
                    'order_stage' => $assignment->order?->stage?->name,
                    'status' => $assignment->status,
                ];
            });

        // Оптимизация: используем join вместо whereHas
        $totalOrders = \App\Models\Order::count();
        $completedOrders = DB::table('orders')
            ->join('stages', 'orders.stage_id', '=', 'stages.id')
            ->where('stages.name', 'completed')
            ->count();
        $cancelledOrders = DB::table('orders')
            ->join('stages', 'orders.stage_id', '=', 'stages.id')
            ->where('stages.name', 'cancelled')
            ->count();
        $completedPercent = $totalOrders ? round($completedOrders / $totalOrders * 100, 2) : 0;
        $cancelledPercent = $totalOrders ? round($cancelledOrders / $totalOrders * 100, 2) : 0;

        return response()->json([
            'orders_by_stage' => $ordersByStage,
            'orders_by_user' => $ordersByUser,
            'closed_count' => $closedCount,
            'delayed_assignments' => $delayedAssignments,
            'delayed_assignments_list' => $delayedAssignmentsList,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'percent_completed' => $completedPercent,
            'percent_cancelled' => $cancelledPercent,
            'is_employee_view' => false,
        ]);
    }
}
