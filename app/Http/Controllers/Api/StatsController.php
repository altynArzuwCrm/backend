<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('stats_main', 10, function () {
            $users = User::count();
            $orders = Order::count();
            $newClients = Client::where('created_at', '>=', Carbon::now()->subDays(30))->count();
            return [
                'users' => $users,
                'orders' => $orders,
                'newClients' => $newClients,
            ];
        });
        return response()->json($stats);
    }

    /**
     * Получить выручку по месяцам
     * ВАЖНО: Теперь используется общая сумма проектов и заказов, а не оплаченная сумма
     */
    public function revenueByMonth(Request $request)
    {
        $user = $request->user();

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

        // Проверяем права доступа
        if (!$user->is_active) {
            return response()->json(['message' => 'Ваш аккаунт деактивирован'], 403);
        }

        // Если пользователь сотрудник, показываем только его данные
        if ($user->isStaff()) {
            return $this->getEmployeeDashboard($user);
        }

        // Для админов и менеджеров показываем полную статистику
        return $this->getFullDashboard();
    }

    private function getEmployeeDashboard($user)
    {
        // Статистика только для сотрудника
        $userAssignments = \App\Models\OrderAssignment::where('user_id', $user->id)
            ->with(['order.product', 'order.stage'])
            ->get();

        $totalAssignments = $userAssignments->count();
        $completedAssignments = $userAssignments->where('status', 'completed')->count();
        $pendingAssignments = $userAssignments->where('status', 'pending')->count();
        $inProgressAssignments = $userAssignments->where('status', 'in_progress')->count();

        $recentAssignments = $userAssignments
            ->sortByDesc('created_at')
            ->take(10)
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->order_id,
                    'product_name' => $assignment->order?->product?->name,
                    'stage' => $assignment->order?->stage?->name,
                    'status' => $assignment->status,
                    'deadline' => $assignment->order?->deadline,
                ];
            });

        $delayedAssignments = $userAssignments
            ->where('order.deadline', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
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
        $ordersByStage = Order::with('stage')
            ->get()
            ->groupBy('stage.name')
            ->map(function ($orders) {
                return $orders->count();
            });

        $ordersByUser = DB::table('order_assignments')->select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->get()
            ->map(function ($row) {
                $user = \App\Models\User::find($row->user_id);
                $orders = \App\Models\OrderAssignment::where('user_id', $row->user_id)
                    ->with(['order.product', 'order.stage'])
                    ->limit(10)
                    ->get()
                    ->map(function ($a) {
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
                ];
            });

        $closedCount = Order::whereHas('stage', function ($query) {
            $query->where('name', 'completed');
        })
            ->where('archived_at', '>=', now()->subDays(30))
            ->count();

        $delayedAssignmentsQuery = \App\Models\OrderAssignment::whereHas('order', function ($query) {
            $query->where('deadline', '<', now());
        });
        $delayedAssignments = $delayedAssignmentsQuery->count();

        $delayedAssignmentsList = $delayedAssignmentsQuery
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

        $totalOrders = \App\Models\Order::count();
        $completedOrders = \App\Models\Order::whereHas('stage', function ($query) {
            $query->where('name', 'completed');
        })->count();
        $cancelledOrders = \App\Models\Order::whereHas('stage', function ($query) {
            $query->where('name', 'cancelled');
        })->count();
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
