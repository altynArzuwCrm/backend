<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientContactController extends Controller
{
    public function store(Request $request, Client $client)
    {
        if (Gate::denies('create', Client::class)) {
            abort(403, 'Доступ запрещён');
        }
        $data = $request->validate([
           'type' => 'required|in:phone,email,telegram,whatsapp,instagram,other',
           'value' => 'required|string|max:255',
        ]);

        $contact = $client->contacts()->create($data);

        return response()->json($contact, 201);
    }

    public function update(Client $client, ClientContact $contact, Request $request)
    {

        if (Gate::denies('update', $client)) {
            abort(403, 'Доступ запрещён');
        }
        $data = $request->validate([
            'type' => 'required|in:phone,email,telegram,whatsapp,instagram,other',
            'value' => 'required|string|max:255',
        ]);

        $contact->update($data);

        return response()->json($contact);
    }

    public function destroy(Client $client, ClientContact $contact)
    {
        if (Gate::denies('delete', $client)) {
            abort(403, 'Доступ запрещён');
        }
        $contact->delete();

        return response()->json(['message' => 'Контакт удалён']);
    }
}
