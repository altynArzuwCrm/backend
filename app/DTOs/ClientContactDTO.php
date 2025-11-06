<?php

namespace App\DTOs;

class ClientContactDTO
{
    public function __construct(
        public int $id,
        public string $type,
        public string $value,
        public int $client_id,
        public ?string $created_at,
        public ?string $updated_at
    ) {}

    public static function fromModel($contact): self
    {
        // Проверяем, является ли $contact массивом или объектом
        if (is_array($contact)) {
            return new self(
                id: $contact['id'],
                type: $contact['type'],
                value: $contact['value'],
                client_id: $contact['client_id'],
                created_at: $contact['created_at'] ? (new \DateTime($contact['created_at']))->format('c') : null,
                updated_at: $contact['updated_at'] ? (new \DateTime($contact['updated_at']))->format('c') : null
            );
        }

        // Если это Eloquent модель
        return new self(
            id: $contact->id,
            type: $contact->type,
            value: $contact->value,
            client_id: $contact->client_id,
            created_at: $contact->created_at ? (is_string($contact->created_at) ? $contact->created_at : $contact->created_at->toIso8601String()) : null,
            updated_at: $contact->updated_at ? (is_string($contact->updated_at) ? $contact->updated_at : $contact->updated_at->toIso8601String()) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'value' => $this->value,
            'client_id' => $this->client_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
