<?php

namespace App\DTOs;

class ClientDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $company_name,
        public ?string $created_at,
        public ?string $updated_at,
        public array $contacts = []
    ) {}

    public static function fromModel($client): self
    {
        return new self(
            id: $client->id,
            name: $client->name,
            company_name: $client->company_name,
            created_at: $client->created_at ? (is_string($client->created_at) ? $client->created_at : $client->created_at->toISOString()) : null,
            updated_at: $client->updated_at ? (is_string($client->updated_at) ? $client->updated_at : $client->updated_at->toISOString()) : null,
            contacts: $client->contacts ? array_map([ClientContactDTO::class, 'fromModel'], $client->contacts->all()) : []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'contacts' => array_map(fn($contact) => $contact->toArray(), $this->contacts)
        ];
    }
}
