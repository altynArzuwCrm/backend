<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->get('limit', 20);
                    
        $activities = collect();
        
        $newUsers = User::where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => 'user_' . $user->id,
                    'type' => 'user_created',
                    'title' => 'Новый пользователь',
                    'description' => "Пользователь {$user->name} ({$user->role}) зарегистрирован",
                    'user' => $user->name,
                    'timestamp' => $user->created_at,
                    'icon' => 'user-plus',
                    'color' => 'blue'
                ];
            });
        
        $newOrders = Order::where('created_at', '>=', Carbon::now()->subDays(7))
            ->with(['project', 'client'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                $clientName = 'Неизвестный клиент';
                $amount = 0;
                
                if ($order->client) {
                    $clientName = $order->client->name;
                } elseif ($order->project && $order->project->client) {
                    $clientName = $order->project->client->name;
                } else {
                    Log::warning('Order without client', [
                        'order_id' => $order->id,
                        'project_id' => $order->project_id,
                        'client_id' => $order->client_id
                    ]);
                }
                
                if ($order->project) {
                    $amount = $order->project->total_price ?? 0;
                }
                
                return [
                    'id' => 'order_' . $order->id,
                    'type' => 'order_created',
                    'title' => 'Новый заказ',
                    'description' => "Заказ от клиента {$clientName}",
                    'user' => $clientName,
                    'timestamp' => $order->created_at,
                    'icon' => 'shopping-cart',
                    'color' => 'green',
                    'amount' => $amount
                ];
            });
        
        $newClients = Client::where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($client) {
                return [
                    'id' => 'client_' . $client->id,
                    'type' => 'client_created',
                    'title' => 'Новый клиент',
                    'description' => "Клиент {$client->name} добавлен в систему",
                    'user' => $client->name,
                    'timestamp' => $client->created_at,
                    'icon' => 'user',
                    'color' => 'purple'
                ];
            });

        $statusChanges = AuditLog::where('change_type', 'status_change')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->with(['user', 'order'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                $orderTitle = 'Неизвестный заказ';
                if ($log->order) {
                    $orderTitle = $log->order->title ?? "Заказ #{$log->order->id}";
                } else {
                    Log::warning('AuditLog without order', [
                        'audit_log_id' => $log->id,
                        'order_id' => $log->order_id,
                        'change_type' => $log->change_type
                    ]);
                }
                
                return [
                    'id' => 'status_' . $log->id,
                    'type' => 'status_change',
                    'title' => 'Изменение статуса',
                    'description' => "Статус заказа '{$orderTitle}' изменен с '{$log->old_value}' на '{$log->new_value}'",
                    'user' => $log->user->name ?? 'Система',
                    'timestamp' => $log->created_at,
                    'icon' => 'refresh-cw',
                    'color' => 'orange'
                ];
            });
        
        $activities = $activities
            ->merge($newUsers)
            ->merge($newOrders)
            ->merge($newClients)
            ->merge($statusChanges)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
        
        return response()->json([
            'activities' => $activities,
            'total' => $activities->count()
        ]);
    }
    
    public function recent(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $recentActivities = collect();
        
        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => 'user_' . $user->id,
                    'title' => "Новый пользователь: {$user->name}",
                    'timestamp' => $user->created_at,
                    'time' => $user->created_at->diffForHumans(),
                    'icon' => 'UserIcon',
                    'iconBg' => 'bg-blue-500 bg-opacity-20'
                ];
            });
        
        $recentOrders = Order::with(['project', 'client', 'product'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                $orderTitle = $order->display_name ?? "Заказ #{$order->id}";
                
                return [
                    'id' => 'order_' . $order->id,
                    'title' => "Новый заказ: {$orderTitle}",
                    'timestamp' => $order->created_at,
                    'time' => $order->created_at->diffForHumans(),
                    'icon' => 'ShoppingCartIcon',
                    'iconBg' => 'bg-green-500 bg-opacity-20'
                ];
            });
        
        $recentClients = Client::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($client) {
                return [
                    'id' => 'client_' . $client->id,
                    'title' => "Новый клиент: {$client->name}",
                    'timestamp' => $client->created_at,
                    'time' => $client->created_at->diffForHumans(),
                    'icon' => 'UserGroupIcon',
                    'iconBg' => 'bg-purple-500 bg-opacity-20'
                ];
            });
        
        $auditEvents = AuditLog::with(['auditable' => function ($query) {
            if ($query->getModel() instanceof \App\Models\Order) {
                $query->with(['product', 'client']);
            }
        }, 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                $title = '';
                $icon = 'DocumentIcon';
                $iconBg = 'bg-orange-500 bg-opacity-20';
                
                $entityName = 'неизвестной сущности';
                if ($log->auditable) {
                    if ($log->auditable_type === 'App\Models\Order') {
                        $entityName = $log->auditable->display_name ?? "Заказ #{$log->auditable->id}";
                    } else {
                        $entityName = $log->auditable->name ?? $log->auditable->id;
                    }
                }
                switch ($log->action) {
                    case 'created':
                        $title = "Создан {$log->model_name}: {$entityName}";
                        $icon = 'PlusIcon';
                        $iconBg = 'bg-green-500 bg-opacity-20';
                        break;
                    case 'updated':
                        $title = "Обновлен {$log->model_name}: {$entityName}";
                        $icon = 'PencilIcon';
                        $iconBg = 'bg-blue-500 bg-opacity-20';
                        break;
                    case 'deleted':
                        $title = "Удален {$log->model_name}: {$entityName}";
                        $icon = 'TrashIcon';
                        $iconBg = 'bg-red-500 bg-opacity-20';
                        break;
                    default:
                        $title = "Действие {$log->action} с {$log->model_name}: {$entityName}";
                        break;
                }
                
                return [
                    'id' => 'audit_' . $log->id,
                    'title' => $title,
                    'timestamp' => $log->created_at,
                    'time' => Carbon::parse($log->created_at)->diffForHumans(),
                    'icon' => $icon,
                    'iconBg' => $iconBg
                ];
            });
        
        $recentActivities = $recentActivities
            ->merge($recentUsers)
            ->merge($recentOrders)
            ->merge($recentClients)
            ->merge($auditEvents)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
        
        return response()->json($recentActivities);
    }
} 