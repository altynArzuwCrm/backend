<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Order;
use App\Models\OrderAssignment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Project::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = auth()->user();

        $query = Project::with(['orders']);

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSorts = ['id', 'title', 'deadline', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')
                    ->orWhereHas('orders.client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!$user->hasAnyRole(['admin', 'manager'])) {
            $assignedOrderIds = OrderAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('order_id');

            $projectIds = Order::whereIn('id', $assignedOrderIds)
                ->pluck('project_id');

            $query->whereIn('id', $projectIds);
        }

        $allowedPerPage = [10, 20, 50, 100, 200, 500];
        $perPage = (int) $request->get('per_page', 30);
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 30;
        }
        $projects = $query->paginate($perPage);

        return response()->json($projects);
    }

    public function show(Project $project)
    {
        if (Gate::denies('view', $project)) {
            abort(403, 'Доступ запрещён');
        }

        $project->load(['orders.product']);

        return response()->json($project);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Project::class)) {
            abort(403, 'Доступ запрещён');
        }

        if ($request->has('orders') && is_array($request->orders) && count($request->orders) > 1) {
            $request->validate([
                'title' => 'required|string|max:255',
                'deadline' => 'nullable|date',
                'total_price' => 'nullable|numeric|min:0',
                'payment_amount' => 'nullable|numeric|min:0',
                'orders' => 'required|array|min:2',
                'orders.*.product_id' => 'required|exists:products,id',
                'orders.*.quantity' => 'sometimes|integer|min:1',
                'orders.*.deadline' => 'nullable|date',
                'orders.*.price' => 'nullable|numeric',
                'orders.*.client_id' => 'required|exists:clients,id',
                'orders.*.stages' => 'sometimes|array',
                'orders.*.stages.*' => 'string|exists:stages,name',
            ]);

            $project = Project::create([
                'title' => $request->title,
                'deadline' => $request->deadline,
                'total_price' => $request->total_price,
                'payment_amount' => $request->payment_amount ?? 0,
            ]);

            $orders = [];
            foreach ($request->orders as $orderData) {
                $order = Order::create([
                    'client_id' => $orderData['client_id'],
                    'project_id' => $project->id,
                    'product_id' => $orderData['product_id'],
                    'quantity' => $orderData['quantity'] ?? 1,
                    'deadline' => $orderData['deadline'] ?? null,
                    'price' => $orderData['price'] ?? null,
                    // Stages will be assigned automatically based on product configuration
                ]);
                $orders[] = $order;
            }

            return response()->json($project->load('orders'), 201);
        } else {
            $request->validate([
                'title' => 'required|string|max:255',
                'deadline' => 'nullable|date',
                'total_price' => 'nullable|numeric|min:0',
                'payment_amount' => 'nullable|numeric|min:0',
            ]);

            $project = Project::create([
                'title' => $request->title,
                'deadline' => $request->deadline,
                'total_price' => $request->total_price,
                'payment_amount' => $request->payment_amount ?? 0,
            ]);

            return response()->json($project, 201);
        }
    }

    public function allProjects()
    {
        if (Gate::denies('allProjects', Project::class)) {
            abort(403, 'Доступ запрещён');
        }

        $projects = Cache::remember('all_projects', 60, function () {
            return Project::orderBy('id')->get();
        });
        return $projects;
    }

    public function update(Request $request, Project $project)
    {
        if (Gate::denies('update', $project)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'deadline' => 'nullable|date',
            'total_price' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
        ]);

        $project->update($data);

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        if (Gate::denies('delete', $project)) {
            abort(403, 'Доступ запрещён');
        }

        $project->delete();

        return response()->json(['message' => 'Проект удалён']);
    }
}
