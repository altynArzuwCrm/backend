<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    use HandlesAuthorization;

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
            return $user->can('view', $comment->order);
        }

        if ($comment->order_item_id) {
            return $user->can('view', $comment->orderItem);
        }

        return false;
    }

    public function create(User $user)
    {
        return $user !== null;
    }

    public function delete(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id || $user->hasRole('admin');
    }
}
