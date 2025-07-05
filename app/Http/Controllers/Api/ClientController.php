<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('viewAny', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        $query = Client::with('contacts');

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSorts = ['id', 'name', 'company_name', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            if (mb_strlen($search) >= 3) {
                $query->where('name', 'like', "%{$search}%");
            }
        }

        $clients = $query->paginate(10);

        return response()->json($clients, 200);
    }

    public function allClients()
    {
        if (Gate::denies('allClients', Client::class)) {
            abort(403, 'Доступ запрещён');
        }

        return Client::with('contacts')->orderBy('id')->get();
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

       return response()->json($client, 201);
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

       return response()->json(['message' => 'Клиент удалён']);
   }
}

