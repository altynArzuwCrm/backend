<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->get('limit', 20);
        
        // Получаем последние действия из разных источников
        $activities = collect();
        
        // Новые пользователи
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
        
        // Новые заказы
        $newOrders = Order::where('created_at', '>=', Carbon::now()->subDays(7))
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'order_' . $order->id,
                    'type' => 'order_created',
                    'title' => 'Новый заказ',
                    'description' => "Заказ '{$order->title}' от клиента {$order->project->client->name}",
                    'user' => $order->project->client->name,
                    'timestamp' => $order->created_at,
                    'icon' => 'shopping-cart',
                    'color' => 'green',
                    'amount' => $order->project->total_price
                ];
            });
        
        // Новые клиенты
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
        
        // Изменения статусов заказов
        $statusChanges = AuditLog::where('change_type', 'status_change')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->with(['user', 'order'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => 'status_' . $log->id,
                    'type' => 'status_change',
                    'title' => 'Изменение статуса',
                    'description' => "Статус заказа '{$log->order->title}' изменен с '{$log->old_value}' на '{$log->new_value}'",
                    'user' => $log->user->name ?? 'Система',
                    'timestamp' => $log->created_at,
                    'icon' => 'refresh-cw',
                    'color' => 'orange'
                ];
            });
        
        // Объединяем все активности и сортируем по времени
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
        
        // Упрощенная версия для быстрого отображения
        $recentActivities = collect();
        
        // Последние 5 новых пользователей
        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => 'user_' . $user->id,
                    'title' => "Новый пользователь: {$user->name}",
                    'time' => $user->created_at->diffForHumans(),
                    'icon' => 'UserIcon',
                    'iconBg' => 'bg-blue-500 bg-opacity-20'
                ];
            });
        
        // Последние 5 новых заказов
        $recentOrders = Order::with('project')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'order_' . $order->id,
                    'title' => "Новый заказ: {$order->title}",
                    'time' => $order->created_at->diffForHumans(),
                    'icon' => 'ShoppingCartIcon',
                    'iconBg' => 'bg-green-500 bg-opacity-20'
                ];
            });
        
        // Последние 5 новых клиентов
        $recentClients = Client::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($client) {
                return [
                    'id' => 'client_' . $client->id,
                    'title' => "Новый клиент: {$client->name}",
                    'time' => $client->created_at->diffForHumans(),
                    'icon' => 'UserGroupIcon',
                    'iconBg' => 'bg-purple-500 bg-opacity-20'
                ];
            });
        
        // События из audit_logs
        $auditEvents = AuditLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                $title = '';
                $icon = 'DocumentIcon';
                $iconBg = 'bg-orange-500 bg-opacity-20';
                
                switch ($log->change_type) {
                    case 'status_change':
                        $title = "Статус заказа #{$log->order_id} изменён с {$log->old_value} на {$log->new_value}";
                        $icon = 'RefreshIcon';
                        $iconBg = 'bg-yellow-500 bg-opacity-20';
                        break;
                    case 'price_change':
                        $title = "Цена заказа #{$log->order_id} изменена с {$log->old_value} на {$log->new_value}";
                        $icon = 'CurrencyDollarIcon';
                        $iconBg = 'bg-green-500 bg-opacity-20';
                        break;
                    default:
                        $title = "Изменение в заказе #{$log->order_id}: {$log->field_name}";
                        break;
                }
                
                return [
                    'id' => 'audit_' . $log->id,
                    'title' => $title,
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
            ->sortByDesc('time')
            ->take($limit)
            ->values();
        
        return response()->json($recentActivities);
    }
} 