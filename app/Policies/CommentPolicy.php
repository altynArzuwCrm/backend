<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    use HandlesAuthorization;

    public function before($user, $ability)
    {
        if ($user->hasElevatedPermissions()) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Comment $comment)
    {
        if ($user->id === $comment->user_id) {
            return true;
        }

        if ($comment->order_id) {
            $order = \App\Models\Order::find($comment->order_id);
            if ($order) {
                return $user->can('view', $order);
            }
        }

        if ($comment->project_id) {
            $project = \App\Models\Project::find($comment->project_id);
            if ($project) {
                return $user->can('view', $project);
            }
        }

        return false;
    }

    public function create(User $user)
    {
        return $user !== null;
    }

    public function delete(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id || $user->hasElevatedPermissions();
    }
}
