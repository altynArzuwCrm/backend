<?php

namespace App\DTOs;

class ProjectDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $deadline,
        public ?float $total_price,
        public ?float $payment_amount,
        public ?string $created_at,
        public ?string $updated_at
    ) {}

    public static function fromModel($project): self
    {
        return new self(
            id: $project->id,
            title: $project->title,
            deadline: $project->deadline,
            total_price: $project->total_price,
            payment_amount: $project->payment_amount,
            created_at: $project->created_at ? (is_string($project->created_at) ? $project->created_at : $project->created_at->toISOString()) : null,
            updated_at: $project->updated_at ? (is_string($project->updated_at) ? $project->updated_at : $project->updated_at->toISOString()) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'deadline' => $this->deadline,
            'total_price' => $this->total_price,
            'payment_amount' => $this->payment_amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
