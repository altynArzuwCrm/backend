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
                    $order = Order::select('id', 'stage_id')->with('stage:id,name')->findOrFail($data['order_id']);
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
                        // Оптимизация: используем whereExists вместо whereHas
                        $assignedUsers = $order->assignments()
                            ->whereExists(function ($subquery) use ($roleType) {
                                $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                                    ->from('user_roles')
                                    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                                    ->whereColumn('user_roles.user_id', 'order_assignments.user_id')
                                    ->where('roles.name', $roleType);
                            })
                            ->where('status', '!=', 'cancelled')
                            ->with('user:id,name,username,fcm_token')
                            ->get()
                            ->pluck('user')
                            ->filter();
                        foreach ($assignedUsers as $user) {
                            if ($user && $user->id !== Auth::id()) {
                                try {
                                    $actionUser = Auth::user();
                                    if ($actionUser) {
                                        $user->notify(new \App\Notifications\OrderCommented($order, $comment, $actionUser));
                                        $notifiedUserIds[] = $user->id;
                                    }
                                } catch (\Exception $notificationError) {
                                    // Логируем ошибку уведомления, но не прерываем выполнение
                                    Log::warning('Failed to send notification to user', [
                                        'user_id' => $user->id,
                                        'comment_id' => $comment->id,
                                        'error' => $notificationError->getMessage()
                                    ]);
                                }
                            }
                        }
                    }
                    // Оптимизация: используем whereExists вместо whereHas
                    $adminsAndManagers = \App\Models\User::whereExists(function ($subquery) {
                        $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('user_roles')
                            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                            ->whereColumn('user_roles.user_id', 'users.id')
                            ->whereIn('roles.name', ['admin', 'manager']);
                    })->select('id', 'name', 'username', 'fcm_token')->get();
                    foreach ($adminsAndManagers as $admin) {
                        if ($admin->id !== Auth::id() && !in_array($admin->id, $notifiedUserIds)) {
                            try {
                                $actionUser = Auth::user();
                                if ($actionUser) {
                                    $admin->notify(new \App\Notifications\OrderCommented($order, $comment, $actionUser));
                                }
                            } catch (\Exception $notificationError) {
                                // Логируем ошибку уведомления, но не прерываем выполнение
                                Log::warning('Failed to send notification to admin/manager', [
                                    'user_id' => $admin->id,
                                    'comment_id' => $comment->id,
                                    'error' => $notificationError->getMessage()
                                ]);
                            }
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
