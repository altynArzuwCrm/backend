<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Order;
use App\Models\Client;
use App\Models\Role;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityController extends Controller
{




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
                    'icon' => 'UserAddIcon',
                    'iconBg' => 'bg-blue-500 bg-opacity-20'
                ];
            });

        // Оптимизация: загружаем только необходимые поля
        $recentOrders = Order::select('id', 'client_id', 'product_id', 'project_id', 'created_at')
            ->with([
                'project' => function ($q) {
                    $q->select('id', 'title');
                },
                'client' => function ($q) {
                    $q->select('id', 'name', 'company_name');
                },
                'product' => function ($q) {
                    $q->select('id', 'name');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                $orderTitle = $order->display_name ?? "Заказ #{$order->id}";
                $clientName = $order->client ? $order->client->name : 'Неизвестный клиент';
                $productName = $order->product ? $order->product->name : 'Неизвестный продукт';

                // Убираем дублирование - если название заказа и продукта одинаковые, показываем только один раз
                $displayTitle = $orderTitle === $productName ? $orderTitle : "{$orderTitle} ({$productName})";

                return [
                    'id' => 'order_' . $order->id,
                    'title' => "Новый заказ: {$displayTitle} от {$clientName}",
                    'timestamp' => $order->created_at,
                    'time' => $order->created_at->diffForHumans(),
                    'icon' => 'DocumentIcon',
                    'iconBg' => 'bg-green-500 bg-opacity-20'
                ];
            });

        // Оптимизация: загружаем только необходимые поля
        $recentClients = Client::select('id', 'name', 'company_name', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($client) {
                $companyInfo = $client->company_name ? " ({$client->company_name})" : '';
                return [
                    'id' => 'client_' . $client->id,
                    'title' => "Новый клиент: {$client->name}{$companyInfo}",
                    'timestamp' => $client->created_at,
                    'time' => $client->created_at->diffForHumans(),
                    'icon' => 'UsersIcon',
                    'iconBg' => 'bg-purple-500 bg-opacity-20'
                ];
            });

        // Оптимизация: загружаем только необходимые поля
        $auditEvents = AuditLog::select('id', 'user_id', 'auditable_type', 'auditable_id', 'action', 'model_name', 'created_at')
            ->with([
                'user' => function ($q) {
                    $q->select('id', 'name', 'username');
                },
                'auditable' => function ($query) {
                    if ($query->getModel() instanceof \App\Models\Order) {
                        $query->select('id', 'client_id', 'product_id', 'stage_id')
                            ->with([
                                'product' => function ($q) {
                                    $q->select('id', 'name');
                                },
                                'client' => function ($q) {
                                    $q->select('id', 'name', 'company_name');
                                }
                            ]);
                    } elseif ($query->getModel() instanceof \App\Models\Client) {
                        $query->select('id', 'name', 'company_name')
                            ->with(['contacts' => function ($q) {
                                $q->select('id', 'client_id', 'type', 'value');
                            }]);
                    } elseif ($query->getModel() instanceof \App\Models\ClientContact) {
                        $query->select('id', 'client_id', 'type', 'value')
                            ->with(['client' => function ($q) {
                                $q->select('id', 'name', 'company_name');
                            }]);
                    } elseif ($query->getModel() instanceof \App\Models\OrderAssignment) {
                        $query->select('id', 'order_id', 'user_id', 'role_type')
                            ->with([
                                'user' => function ($q) {
                                    $q->select('id', 'name', 'username');
                                },
                                'order' => function ($q) {
                                    $q->select('id', 'product_id', 'client_id');
                                },
                                'order.product' => function ($q) {
                                    $q->select('id', 'name');
                                },
                                'order.client' => function ($q) {
                                    $q->select('id', 'name', 'company_name');
                                }
                            ]);
                    }
                }
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->filter(function ($log) {
                // Убираем записи без связанной сущности
                return $log->auditable !== null;
            })
            ->unique(function ($item) {
                // Убираем дублирование по типу сущности и ID
                return $item->auditable_type . '_' . $item->auditable_id . '_' . $item->action;
            })
            ->map(function ($log) {
                $title = '';
                $icon = 'DocumentIcon';
                $iconBg = 'bg-orange-500 bg-opacity-20';

                // Формируем информативное название сущности
                $entityName = $this->getEntityDisplayName($log);

                switch ($log->action) {
                    case 'created':
                        $title = $this->getCreatedMessage($log, $entityName);
                        // Пропускаем записи с удаленными сущностями
                        if ($title === null) {
                            return null; // Возвращаем null для фильтрации
                        }
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
            })
            ->filter(function ($item) {
                // Убираем записи с null (удаленные сущности)
                return $item !== null;
            });

        $recentActivities = $recentActivities
            ->merge($recentUsers)
            ->merge($recentOrders)
            // Убираем дублирование с auditEvents
            // ->merge($recentClients)
            ->merge($auditEvents)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return response()->json($recentActivities);
    }

    /**
     * Получает отображаемое имя сущности для аудит-лога
     */
    private function getEntityDisplayName($log)
    {
        if (!$log->auditable) {
            // Если auditable отсутствует, возвращаем более информативное сообщение
            return "ID: {$log->auditable_id} (сущность удалена)";
        }

        $model = $log->auditable;

        switch ($log->auditable_type) {
            case 'App\Models\Order':
                $clientName = $model->client ? $model->client->name : 'Неизвестный клиент';
                $productName = $model->product ? $model->product->name : 'Неизвестный продукт';
                return "Заказ #{$model->id} ({$productName}) от {$clientName}";

            case 'App\Models\Client':
                $companyInfo = $model->company_name ? " ({$model->company_name})" : '';
                return "{$model->name}{$companyInfo}";

            case 'App\Models\ClientContact':
                $contactType = $this->getContactTypeDisplayName($model->type ?? 'other');
                $contactValue = $model->value ?? 'неизвестное значение';
                $clientName = $model->client ? $model->client->name : 'Неизвестный клиент';
                return "{$contactType} {$contactValue} для клиента {$clientName}";

            case 'App\Models\OrderAssignment':
                $userName = $model->user ? $model->user->name : 'Неизвестный пользователь';
                $roleName = $this->getRoleDisplayName($model->role_type);
                $orderInfo = $model->order ? "заказа #{$model->order->id}" : 'неизвестного заказа';
                return "{$roleName} {$userName} для {$orderInfo}";

            case 'App\Models\ProductAssignment':
                $userName = $model->user ? $model->user->name : 'Неизвестный пользователь';
                $roleName = $this->getRoleDisplayName($model->role_type);
                $productName = $model->product ? $model->product->name : 'неизвестного продукта';
                return "{$roleName} {$userName} для {$productName}";

            default:
                return $model->name ?? $model->id ?? 'неизвестная сущность';
        }
    }

    /**
     * Формирует сообщение о создании сущности
     */
    private function getCreatedMessage($log, $entityName)
    {
        // Если сущность была удалена, не показываем сообщение о создании
        if (strpos($entityName, '(сущность удалена)') !== false) {
            return null;
        }

        switch ($log->auditable_type) {
            case 'App\Models\OrderAssignment':
                return "Создано назначение: {$entityName}";

            case 'App\Models\ProductAssignment':
                return "Создано назначение: {$entityName}";

            case 'App\Models\ClientContact':
                return "Создан контакт: {$entityName}";

            case 'App\Models\Client':
                return "Создан клиент: {$entityName}";

            case 'App\Models\Order':
                return "Создан заказ: {$entityName}";

            default:
                return "Создан {$log->model_name}: {$entityName}";
        }
    }

    /**
     * Получает отображаемое название типа контакта
     */
    private function getContactTypeDisplayName($type)
    {
        $types = [
            'phone' => 'Телефон',
            'email' => 'Email',
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
            'instagram' => 'Instagram',
            'other' => 'Контакт'
        ];

        return $types[$type] ?? 'Контакт';
    }

    /**
     * Получает отображаемое название роли
     */
    private function getRoleDisplayName($roleType)
    {
        // Оптимизация: используем кэшированный поиск роли
        // Получаем роль из базы данных (роли кэшируются в RoleController)
        $role = \App\Models\Role::select('id', 'name', 'display_name')->where('name', $roleType)->first();

        if ($role && $role->display_name) {
            return $role->display_name;
        }

        // Fallback: если роль не найдена, используем преобразование
        return ucfirst(str_replace('_', ' ', $roleType));
    }
}
