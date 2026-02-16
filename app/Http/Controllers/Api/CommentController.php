<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Comment;
use App\Models\Order;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Comment::class, 'comment');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $orderId = $request->query('order_id');
        $projectId = $request->query('project_id');

        if ($orderId) {
            try {
                $order = Order::findOrFail($orderId);
                if (Gate::denies('view', $order)) {
                    return response()->json(['error' => 'Доступ запрещён'], 403);
                }
                $comments = $order->comments()->with('user.roles')->get();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json(['error' => 'Заказ не найден'], 404);
            }
        } elseif ($projectId) {
            try {
                $project = Project::findOrFail($projectId);
                if (Gate::denies('view', $project)) {
                    return response()->json(['error' => 'Доступ запрещён'], 403);
                }
                $comments = $project->comments()->with('user.roles')->get();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json(['error' => 'Проект не найден'], 404);
            }
        } else {
            return response()->json(['error' => 'order_id или project_id обязателен'], 422);
        }

        // Преобразуем комментарии с использованием UserResource для пользователей
        $commentsData = $comments->map(function ($comment) {
            // Проверяем, что пользователь существует (на случай, если он был удален)
            if (!$comment->user) {
                Log::warning('Comment has no user', [
                    'comment_id' => $comment->id,
                    'user_id' => $comment->user_id
                ]);
                // Возвращаем комментарий с пустым пользователем вместо ошибки
                return [
                    'id' => $comment->id,
                    'text' => $comment->text,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'user' => null,
                ];
            }
            
            return [
                'id' => $comment->id,
                'text' => $comment->text,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
                'user' => new UserResource($comment->user),
            ];
        });

        return response()->json($commentsData);
    }

    public function store(Request $request)
    {
        try {
            // Проверяем авторизацию пользователя
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['error' => 'Необходима авторизация'], 401);
            }

            $data = $request->validate([
                'text' => 'required|string',
                'order_id' => 'nullable|exists:orders,id',
                'project_id' => 'nullable|exists:projects,id'
            ]);

            if (empty($data['order_id']) && empty($data['project_id'])) {
                return response()->json(['error' => 'Нужно указать либо order_id, либо project_id, но не оба'], 422);
            }

            if (!empty($data['order_id']) && !empty($data['project_id'])) {
                return response()->json(['error' => 'Нужно указать либо order_id, либо project_id, но не оба'], 422);
            }

            // Загружаем заказ/проект один раз для проверки прав
            $order = null;
            $project = null;
            if (!empty($data['order_id'])) {
                try {
                    // Загружаем заказ полностью для корректной работы OrderPolicy::view
                    // OrderPolicy использует $order->assignments(), поэтому нужны все поля
                    $order = Order::with(['stage:id,name', 'assignments' => function($q) {
                        $q->select('id', 'order_id', 'user_id', 'status');
                    }])->findOrFail($data['order_id']);
                    if (Gate::denies('view', $order)) {
                        return response()->json(['error' => 'Доступ запрещён'], 403);
                    }
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    return response()->json(['error' => 'Заказ не найден'], 404);
                }
            } else {
                try {
                    $project = Project::findOrFail($data['project_id']);
                    if (Gate::denies('view', $project)) {
                        return response()->json(['error' => 'Доступ запрещён'], 403);
                    }
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    return response()->json(['error' => 'Проект не найден'], 404);
                }
            }

            // Используем транзакцию для обеспечения целостности данных
            $comment = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $userId) {
                return Comment::create([
                    'user_id' => $userId,
                    'order_id' => $data['order_id'] ?? null,
                    'project_id' => $data['project_id'] ?? null,
                    'text' => $data['text'],
                ]);
            });

            // Отправка уведомлений изолирована от создания комментария
            // Если уведомления не отправятся, комментарий все равно будет создан
            if (!empty($data['order_id']) && $order) {
                try {
                    // Добавляем таймаут для защиты от зависаний
                    $startTime = microtime(true);
                    $maxExecutionTime = 8; // Максимум 8 секунд на отправку уведомлений
                    
                    // Используем уже загруженный заказ
                    $stage = $order->stage ? $order->stage->name : null;
                    $roleMap = [
                        'design' => 'designer',
                        'print' => 'print_operator',
                        'engraving' => 'engraving_operator',
                        'workshop' => 'workshop_worker',
                    ];
                    $notifiedUserIds = [];
                    
                    if ($stage && isset($roleMap[$stage])) {
                        $roleType = $roleMap[$stage];
                        
                        try {
                            // Оптимизированный запрос: используем прямой запрос к OrderAssignment
                            // вместо сложного whereExists с join через отношения
                            $assignedUsers = \App\Models\OrderAssignment::where('order_id', $order->id)
                                ->where('status', '!=', 'cancelled')
                                ->whereHas('user.roles', function ($query) use ($roleType) {
                                    $query->where('roles.name', $roleType);
                                })
                                ->with(['user' => function ($query) {
                                    $query->select('id', 'name', 'username', 'fcm_token');
                                }])
                                ->limit(50) // Ограничиваем количество для защиты от зависаний
                                ->get()
                                ->pluck('user')
                                ->filter();
                            
                            foreach ($assignedUsers as $user) {
                                // Проверяем таймаут перед каждой отправкой
                                if (microtime(true) - $startTime > $maxExecutionTime) {
                                    Log::warning('Notification timeout reached for assigned users', [
                                        'order_id' => $order->id,
                                        'comment_id' => $comment->id
                                    ]);
                                    break;
                                }
                                
                                if ($user && $user->id !== Auth::id()) {
                                    try {
                                        $actionUser = Auth::user();
                                        if ($actionUser) {
                                            $user->notify(new \App\Notifications\OrderCommented($order, $comment, $actionUser));
                                            $notifiedUserIds[] = $user->id;
                                        }
                                    } catch (\Exception $notificationError) {
                                        Log::warning('Failed to send notification to user', [
                                            'user_id' => $user->id,
                                            'comment_id' => $comment->id,
                                            'error' => $notificationError->getMessage()
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Error fetching assigned users for notifications', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // Кэшируем список админов/менеджеров (обновляется раз в минуту)
                    if (microtime(true) - $startTime < $maxExecutionTime) {
                        try {
                            $adminsAndManagers = \Illuminate\Support\Facades\Cache::remember(
                                'admins_and_managers_list',
                                60, // 1 минута
                                function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->whereIn('roles.name', ['admin', 'manager']);
                                    })
                                    ->select('id', 'name', 'username', 'fcm_token')
                                    ->get();
                                }
                            );
                            
                            foreach ($adminsAndManagers as $admin) {
                                // Проверяем таймаут перед каждой отправкой
                                if (microtime(true) - $startTime > $maxExecutionTime) {
                                    break;
                                }
                                
                                if ($admin->id !== Auth::id() && !in_array($admin->id, $notifiedUserIds)) {
                                    try {
                                        $actionUser = Auth::user();
                                        if ($actionUser) {
                                            $admin->notify(new \App\Notifications\OrderCommented($order, $comment, $actionUser));
                                        }
                                    } catch (\Exception $notificationError) {
                                        Log::warning('Failed to send notification to admin/manager', [
                                            'user_id' => $admin->id,
                                            'comment_id' => $comment->id,
                                            'error' => $notificationError->getMessage()
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Error fetching admins/managers for notifications', [
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } catch (\Exception $notificationError) {
                    // Логируем общую ошибку отправки уведомлений, но не прерываем выполнение
                    Log::error('Error sending notifications for comment', [
                        'comment_id' => $comment->id,
                        'order_id' => $order->id,
                        'error' => $notificationError->getMessage(),
                        'trace' => $notificationError->getTraceAsString()
                    ]);
                }
            }

            // Загружаем комментарий со связями для корректного ответа фронтенду
            $comment->load('user.roles');

            // Проверяем, что пользователь существует (на случай, если он был удален)
            if (!$comment->user) {
                Log::error('Comment created but user not found', [
                    'comment_id' => $comment->id,
                    'user_id' => $comment->user_id
                ]);
                return response()->json(['error' => 'Ошибка: пользователь комментария не найден'], 500);
            }

            // Формируем ответ в том же формате, что и в методе index
            $commentData = [
                'id' => $comment->id,
                'text' => $comment->text,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
                'user' => new UserResource($comment->user),
            ];

            return response()->json($commentData, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Валидационные ошибки возвращаем как есть
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in CommentController@store: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => 'Ошибка добавления комментария'], 500);
        }
    }

    public function show(Comment $comment)
    {
        $this->authorize('view', $comment);
        // Используем with() вместо load() для предотвращения N+1 проблемы
        $comment = Comment::with('user.roles')->find($comment->id);
        
        if (!$comment) {
            return response()->json(['error' => 'Комментарий не найден'], 404);
        }
        
        // Проверяем, что пользователь существует
        if (!$comment->user) {
            Log::warning('Comment has no user in show method', [
                'comment_id' => $comment->id,
                'user_id' => $comment->user_id
            ]);
        }
        
        return response()->json($comment);
    }

    public function destroy(Comment $comment)
    {
        try {
            $this->authorize('delete', $comment);
            $comment->delete();

            return response()->json(null, 204);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Доступ запрещён'], 403);
        } catch (\Exception $e) {
            Log::error('Error in CommentController@destroy: ' . $e->getMessage(), [
                'comment_id' => $comment->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ошибка удаления комментария'], 500);
        }
    }
}
