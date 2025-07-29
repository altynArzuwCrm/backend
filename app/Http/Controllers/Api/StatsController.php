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
            $revenue = Project::sum('total_price') ?? 0;
            $newClients = Client::where('created_at', '>=', Carbon::now()->subDays(30))->count();
            return [
                'users' => $users,
                'orders' => $orders,
                'revenue' => $revenue,
                'newClients' => $newClients,
            ];
        });
        return response()->json($stats);
    }

    public function dashboard()
    {   
        $ordersByStage = DB::table('orders')->select('stage', DB::raw('count(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage');
        $ordersByUser = DB::table('order_assignments')->select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->get()
            ->map(function ($row) {
                $user = \App\Models\User::find($row->user_id);
                $orders = \App\Models\OrderAssignment::where('user_id', $row->user_id)
                    ->with('order.product')
                    ->limit(10)
                    ->get()
                    ->map(function ($a) {
                        return [
                            'id' => $a->order_id,
                            'product_name' => $a->order?->product?->name,
                            'stage' => $a->order?->stage,
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

        $closedCount = Order::where('stage', 'completed')
            ->where('archived_at', '>=', now()->subDays(30))
            ->count();

        $delayedAssignmentsQuery = \App\Models\OrderAssignment::whereHas('order', function ($query) {
            $query->where('deadline', '<', now());
        });
        $delayedAssignments = $delayedAssignmentsQuery->count();

        $delayedAssignmentsList = $delayedAssignmentsQuery
            ->with(['user', 'order'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'user_name' => $assignment->user?->name,
                    'order_id' => $assignment->order_id,
                    'order_stage' => $assignment->order?->stage,
                    'status' => $assignment->status,
                ];
            });

        $totalOrders = \App\Models\Order::count();
        $completedOrders = \App\Models\Order::where('stage', 'completed')->count();
        $cancelledOrders = \App\Models\Order::where('stage', 'cancelled')->count();
        $completedPercent = $totalOrders ? round($completedOrders / $totalOrders * 100, 2) : 0;
        $cancelledPercent = $totalOrders ? round($cancelledOrders / $totalOrders * 100, 2) : 0;

        return response()->json([
            'orders_by_stage' => $ordersByStage,
            'orders_by_user' => $ordersByUser,
            'closed_last_30_days' => $closedCount,
            'delayed_assignments' => $delayedAssignments,
            'delayed_assignments_list' => $delayedAssignmentsList,
            'completed_percent' => $completedPercent,
            'cancelled_percent' => $cancelledPercent,
        ]);
    }
}
