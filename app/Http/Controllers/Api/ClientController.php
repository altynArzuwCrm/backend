<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = $request->user();
        $query = Client::with('contacts');

        if (!$user->hasAnyRole(['admin', 'manager'])) {
            $assignedClientIds = \App\Models\OrderAssignment::query()
                ->where('user_id', $user->id)
                ->join('orders', 'order_assignments.order_id', '=', 'orders.id')
                ->pluck('orders.client_id')
                ->unique();

            $query->whereIn('id', $assignedClientIds);
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSorts = ['id', 'name', 'company_name', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('company_name', 'like', '%' . $search . '%');
            });
        }

        $perPage = $request->get('per_page', 30);
        $clients = $query->paginate($perPage);

        return response()->json($clients, 200);
    }

    public function allClients()
    {
        if (Gate::denies('allClients', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = request()->user();
        $cacheKey = 'clients_for_user_' . $user->id . '_roles_' . $user->roles->pluck('name')->implode('-');
        $clients = Cache::remember($cacheKey, 60, function () use ($user) {
            $query = Client::with('contacts');
            if (!$user->hasAnyRole(['admin', 'manager'])) {
                $assignedClientIds = \App\Models\OrderAssignment::query()
                    ->where('user_id', $user->id)
                    ->join('orders', 'order_assignments.order_id', '=', 'orders.id')
                    ->pluck('orders.client_id')
                    ->unique();
                $query->whereIn('id', $assignedClientIds);
            }
            return $query->orderBy('id')->get();
        });
        return $clients;
    }

    public function show(Client $client)
    {
        if (Gate::denies('view', $client)) {
            abort(403, 'Доступ запрещён');
        }
        return response()->json($client);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data =  $request->validate([
            'name' =>  'required|string|max:255',
            'company_name' => 'nullable|string|max:225',
        ]);

        $client = Client::create($data);

        return response()->json(['data' => $client], 201);
    }

    public function update(Request $request, Client $client)
    {
        if (Gate::denies('update', $client)) {
            abort(403, 'Доступ запрещён');
        }
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'company_name' => 'nullable|string|max:255',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(Client $client)
    {
        if (Gate::denies('delete', $client)) {
            abort(403, 'Доступ запрещён');
        }
        $client->delete();

        // Сбросить кэш клиентов для всех пользователей (или только нужные ключи)
        \Cache::flush();

        return response()->json(['message' => 'Клиент удалён']);
    }
}
