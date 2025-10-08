<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = $request->user();
        // Оптимизация: выбираем только необходимые поля
        $query = Client::select('id', 'name', 'company_name', 'created_at')
            ->with(['contacts' => function ($q) {
                $q->select('id', 'client_id', 'type', 'value');
            }]);

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

        $allowedPerPage = [10, 20, 50, 100, 200, 500];
        $perPage = (int) $request->get('per_page', 30);
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 30;
        }

        // Кэшируем результаты поиска на 15 минут для быстрых ответов
        $cacheKey = 'clients_' . $user->id . '_' . md5($request->fullUrl());
        $clients = CacheService::rememberWithTags($cacheKey, 900, function () use ($query, $perPage) {
            return $query->paginate($perPage);
        }, [CacheService::TAG_CLIENTS]);

        return response()->json($clients, 200);
    }

    public function allClients()
    {
        if (Gate::denies('allClients', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $user = request()->user();
        $cacheKey = 'clients_for_user_' . $user->id . '_roles_' . $user->roles->pluck('name')->implode('-');
        $clients = CacheService::rememberWithTags($cacheKey, 1800, function () use ($user) {
            // Оптимизация: выбираем только необходимые поля для уменьшения размера данных
            $query = Client::select('id', 'name', 'company_name', 'created_at')
                ->with(['contacts' => function ($q) {
                    $q->select('id', 'client_id', 'type', 'value');
                }]);
            if (!$user->hasAnyRole(['admin', 'manager'])) {
                $assignedClientIds = \App\Models\OrderAssignment::query()
                    ->where('user_id', $user->id)
                    ->join('orders', 'order_assignments.order_id', '=', 'orders.id')
                    ->pluck('orders.client_id')
                    ->unique();
                $query->whereIn('id', $assignedClientIds);
            }
            return $query->orderBy('id')->get();
        }, [CacheService::TAG_CLIENTS]);
        return $clients;
    }

    public function show(Client $client)
    {
        if (Gate::denies('view', $client)) {
            abort(403, 'Доступ запрещён');
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $client = Client::with('contacts')->find($client->id);
        return response()->json($client);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:225',
            'contacts' => 'nullable|array',
            'contacts.*.type' => 'required_with:contacts|in:phone,email,telegram,whatsapp,instagram,other',
            'contacts.*.value' => 'required_with:contacts|string|max:255',
        ]);

        $client = Client::create([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
        ]);

        // Создаем контакты, если они переданы
        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $contactData) {
                $client->contacts()->create([
                    'type' => $contactData['type'],
                    'value' => $contactData['value'],
                ]);
            }
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $client = Client::with('contacts')->find($client->id);
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
            'contacts' => 'nullable|array',
            'contacts.*.type' => 'required_with:contacts|in:phone,email,telegram,whatsapp,instagram,other',
            'contacts.*.value' => 'required_with:contacts|string|max:255',
        ]);

        // Обновляем основные данные клиента
        $client->update([
            'name' => $data['name'] ?? $client->name,
            'company_name' => $data['company_name'] ?? $client->company_name,
        ]);

        // Обновляем контакты, если они переданы
        if (isset($data['contacts'])) {
            // Удаляем старые контакты
            $client->contacts()->delete();

            // Создаем новые контакты
            if (!empty($data['contacts'])) {
                foreach ($data['contacts'] as $contactData) {
                    $client->contacts()->create([
                        'type' => $contactData['type'],
                        'value' => $contactData['value'],
                    ]);
                }
            }
        }

        // Используем with() вместо load() для предотвращения N+1 проблемы
        $client = Client::with('contacts')->find($client->id);
        return response()->json($client);
    }

    public function getCompanies()
    {
        if (Gate::denies('viewAny', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $companies = Client::select('company_name')
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->distinct()
            ->orderBy('company_name')
            ->pluck('company_name');

        return response()->json($companies);
    }

    public function getClientsByCompany($companyName)
    {
        if (Gate::denies('viewAny', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $clients = Client::where('company_name', $companyName)
            ->with('contacts')
            ->orderBy('name')
            ->get();

        return response()->json($clients);
    }

    public function destroy($id)
    {
        // Находим клиента вручную, чтобы избежать проблем с Route Model Binding
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'message' => 'Клиент не найден',
                'error_code' => 'CLIENT_NOT_FOUND'
            ], 404);
        }

        // Убрано подробное отладочное логирование удаления клиента

        if (Gate::denies('delete', $client)) {
            abort(403, 'Доступ запрещён');
        }



        $activeOrdersCount = $client->orders()->where('is_archived', false)->count();

        if ($activeOrdersCount > 0) {
            return response()->json([
                'message' => "Невозможно удалить клиента, у которого есть {$activeOrdersCount} активных заказов"
            ], 422);
        }

        // Удаление клиента

        $client->delete();

        CacheService::invalidateClientCaches($client->id);

        // Клиент удалён успешно

        return response()->json(['message' => 'Клиент удалён']);
    }
}
