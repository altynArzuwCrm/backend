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
            $order = Order::findOrFail($orderId);
            if (Gate::denies('view', $order)) {
                return response()->json(['error' => 'Доступ запрещён'], 403);
            }
            $comments = $order->comments()->with('user.roles')->get();
        } elseif ($projectId) {
            $project = Project::findOrFail($projectId);
            if (Gate::denies('view', $project)) {
                return response()->json(['error' => 'Доступ запрещён'], 403);
            }
            $comments = $project->comments()->with('user.roles')->get();
        } else {
            return response()->json(['error' => 'order_id или project_id обязателен'], 402);
        }

        // Преобразуем комментарии с использованием UserResource для пользователей
        $commentsData = $comments->map(function ($comment) {
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

            if (!empty($data['order_id'])) {
                $order = Order::findOrFail($data['order_id']);
                if (Gate::denies('view', $order)) {
                    return response()->json(['error' => 'Доступ запрещён'], 403);
                }
            } else {
                $project = Project::findOrFail($data['project_id']);
                if (Gate::denies('view', $project)) {
                    return response()->json(['error' => 'Доступ запрещён'], 403);
                }
            }

            $comment = Comment::create([
                'user_id' => Auth::id(),
                'order_id' => $data['order_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'text' => $data['text'],
            ]);

            if (!empty($data['order_id'])) {
                $order = Order::findOrFail($data['order_id']);
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
                    $assignedUsers = $order->assignments()
                        ->whereHas('user.roles', function ($q) use ($roleType) {
                            $q->where('name', $roleType);
                        })
                        ->where('status', '!=', 'cancelled')
                        ->with('user')
                        ->get()
                        ->pluck('user')
                        ->filter();
                    foreach ($assignedUsers as $user) {
                        if ($user && $user->id !== Auth::id()) {
                            $user->notify(new \App\Notifications\OrderCommented($order, $comment, Auth::user()));
                            $notifiedUserIds[] = $user->id;
                        }
                    }
                }
                $adminsAndManagers = \App\Models\User::whereHas('roles', function ($q) {
                    $q->whereIn('name', ['admin', 'manager']);
                })->get();
                foreach ($adminsAndManagers as $admin) {
                    if ($admin->id !== Auth::id() && !in_array($admin->id, $notifiedUserIds)) {
                        $admin->notify(new \App\Notifications\OrderCommented($order, $comment, Auth::user()));
                    }
                }
            }

            return response()->json($comment, 201);
        } catch (\Exception $e) {
            Log::error('Error in CommentController@store: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка добавления комментария'], 500);
        }
    }

    public function show(Comment $comment)
    {
        $this->authorize('view', $comment);
        return response()->json($comment->load('user.roles'));
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();

        return response()->json(null, 204);
    }
}
