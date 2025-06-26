<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('contacts')->paginate(20);
        return response()->json($clients);
    }

    public function show(Client $client)
    {
        $client->load('contacts');
        return response()->json($client);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contacts' => 'array',
            'contacts.*.type' => 'required|in:phone,email,telegram,whatsapp,instagram,other',
            'contacts.*.value' => 'required|string|max:255',
        ]);

        $client = Client::create([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
        ]);

        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $contact) {
                $client->contacts()->create($contact);
            }
        }

        $client->load('contacts');
        return response()->json($client, 201);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contacts' => 'array',
            'contacts.*.id' => 'sometimes|exists:client_contacts,id',
            'contacts.*.type' => 'required_with:contacts.*.value|in:phone,email,telegram,whatsapp,instagram,other',
            'contacts.*.value' => 'required_with:contacts.*.type|string|max:255',
        ]);

        if (isset($data['name'])) {
            $client->name = $data['name'];
        }
        if (array_key_exists('company_name', $data)) {
            $client->company_name = $data['company_name'];
        }
        $client->save();

        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $contactData) {
                if (isset($contactData['id'])) {
                    $contact = $client->contacts()->find($contactData['id']);
                    if ($contact) {
                        $contact->update([
                            'type' => $contactData['type'],
                            'value' => $contactData['value'],
                        ]);
                    }
                } else {
                    $client->contacts()->create([
                        'type' => $contactData['type'],
                        'value' => $contactData['value'],
                    ]);
                }
            }
        }

        $client->load('contacts');
        return response()->json($client);
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json(['message' => 'Клиент удалён']);
    }

    public function destroyContact(ClientContact $contact)
    {
        $contact->delete();

        return response()->json(['message' => 'Контакт удалён']);
    }
}

