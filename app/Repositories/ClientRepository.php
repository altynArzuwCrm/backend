<?php

namespace App\Repositories;

use App\Models\Client;
use App\DTOs\ClientDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ClientRepository
{
    public function getPaginatedClients(Request $request): LengthAwarePaginator
    {
        $query = Client::with(['contacts']);

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('company_name', 'like', '%' . $search . '%')
                    ->orWhereHas('contacts', function ($contactQuery) use ($search) {
                        $contactQuery->where('value', 'like', '%' . $search . '%');
                    });
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = (int) $request->get('per_page', 30);
        return $query->paginate($perPage);
    }

    public function getClientById(int $id): ?ClientDTO
    {
        $client = Client::with(['contacts'])->find($id);

        if (!$client) {
            return null;
        }

        return ClientDTO::fromModel($client);
    }

    public function createClient(array $data): ClientDTO
    {
        $client = Client::create($data);

        // Если есть контакты, создаем их
        if (isset($data['contacts']) && is_array($data['contacts'])) {
            foreach ($data['contacts'] as $contactData) {
                $client->contacts()->create($contactData);
            }
        }

        return ClientDTO::fromModel($client->load('contacts'));
    }

    public function updateClient(Client $client, array $data): ClientDTO
    {
        $client->update($data);

        // Обновляем контакты если они переданы
        if (isset($data['contacts']) && is_array($data['contacts'])) {
            // Удаляем старые контакты
            $client->contacts()->delete();

            // Создаем новые
            foreach ($data['contacts'] as $contactData) {
                $client->contacts()->create($contactData);
            }
        }

        return ClientDTO::fromModel($client->load('contacts'));
    }

    public function deleteClient(Client $client): bool
    {
        return $client->delete();
    }

    public function getAllClients(): array
    {
        $clients = Client::with(['contacts'])->get();
        return array_map([ClientDTO::class, 'fromModel'], $clients->toArray());
    }

    public function getClientsByCompany(string $companyName): array
    {
        $clients = Client::with(['contacts'])
            ->where('company_name', 'like', '%' . $companyName . '%')
            ->get();
        return array_map([ClientDTO::class, 'fromModel'], $clients->toArray());
    }

    public function searchClients(string $searchTerm): array
    {
        $clients = Client::with(['contacts'])
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('company_name', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('contacts', function ($contactQuery) use ($searchTerm) {
                        $contactQuery->where('value', 'like', '%' . $searchTerm . '%');
                    });
            })
            ->get();
        return array_map([ClientDTO::class, 'fromModel'], $clients->toArray());
    }
}
