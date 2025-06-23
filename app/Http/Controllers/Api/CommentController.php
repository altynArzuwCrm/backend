<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
   public function __construct()
   {
       $this->authorizeResource(Comment::class, 'comment');
   }

   public function index(Request $request)
   {
       $orderId = $request->query('order_id');
       $orderItemId = $request->query('order_item_id');

       if ($orderId) {
           $order = Order::findOrFail($orderId);
           $this->authorize('view', $order);
           $comments = $order->comments()->with('user')->get();
       }
       elseif ($orderItemId) {
           $orderItem = OrderItem::findOrFail($orderItemId);
           $this->authorize('view', $orderItem);
           $comments = $orderItem->comments()->with('user')->get();
       } else {
           return response()->json(['error' => 'order_id или order_item_id обязателен'], 402);
       }

       return response()->json($comments);
   }

   public function store(Request $request)
   {
       $data = $request->validate([
           'text' => 'required|string',
           'order_id' => 'nullable|exists:orders,id',
           'order_items_id' => 'nullable|exists:order_items,id'
       ]);

       if (empty($data['order_id']) && empty($data['order_item_id'])) {
           return response()->json(['error' => 'Нужно указать либо order_id, либо order_item_id, но не оба'], 422);
       }

       if (!empty($data['order_id']) && !empty($data['order_item_id'])) {
           return response()->json(['error' => 'Нужно указать либо order_id, либо order_item_id, но не оба'], 422);
       }

       if (!empty($data['order_id'])) {
           $order = Order::findOrFail($data['order_id']);
           $this->authorize('view', $order);
       } else {
           $orderItem = OrderItem::findOrFail($data['order_item_id']);
           $this->authorize('view', $orderItem);
       }

       $comment = Comment::create([
          'user_id' => Auth::id(),
          'order_id' => $data['order_id'] ?? null,
          'order_item_id' => $data['order_item_id'] ?? null,
          'text' => $data['text'],
       ]);

       return response()->json($comment, 201);
   }

   public function show(Comment $comment)
   {
       $this->authorize('view', $comment);
       return response()->json($comment->load('user'));
   }

   public function delete(Comment $comment)
   {
       $this->authorize('delete', $comment);
       $comment->delete();

       return response()->json(null,204);
   }
}
