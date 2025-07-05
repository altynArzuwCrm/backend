<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Order;
use App\Models\OrderAssignment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Project::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = auth()->user();

        $query = Project::with(['client', 'orders']);

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSorts = ['id', 'title', 'deadline', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        if (!in_array($user->role, ['admin', 'manager'])) {
            $assignedOrderIds = OrderAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('order_id');

            $projectIds = Order::whereIn('id', $assignedOrderIds)
                ->pluck('project_id');

            $query->whereIn('id', $projectIds);
        }

        $projects = $query->paginate(10);

        return response()->json($projects);
    }

    public function show(Project $project)
    {
        if (Gate::denies('view', $project)) {
            abort(403, 'Доступ запрещён');
        }

        $project->load('orders');

        return response()->json($project);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Project::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'deadline' => 'nullable|date',
            'total_price' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
        ]);

        $project = Project::create($data);

        return response()->json($project, 201);
    }

    public function update(Request $request, Project $project)
    {
        if (Gate::denies('update', $project)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'client_id' => 'sometimes|exists:clients,id',
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