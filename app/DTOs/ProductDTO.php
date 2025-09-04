<?php

namespace App\DTOs;

use App\Models\Product;
use App\Models\ProductAssignment;
use App\Models\Stage;
use Illuminate\Support\Collection;

class ProductDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?bool $is_active,
        public ?string $created_at,
        public ?string $updated_at,
        public array $assignments = [],
        public array $available_stages = [],
        public array $categories = []
    ) {}

    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            description: $product->description,
            is_active: $product->is_active ?? false,
            created_at: $product->created_at ? (is_string($product->created_at) ? $product->created_at : $product->created_at->toISOString()) : null,
            updated_at: $product->updated_at ? (is_string($product->updated_at) ? $product->updated_at : $product->updated_at->toISOString()) : null,
            assignments: $product->assignments ? $product->assignments->map(fn($assignment) => ProductAssignmentDTO::fromModel($assignment))->toArray() : [],
            available_stages: $product->availableStages ? $product->availableStages->map(fn($stage) => StageDTO::fromModel($stage))->toArray() : [],
            categories: $product->categories ? $product->categories->map(fn($category) => ['id' => $category->id, 'name' => $category->name])->toArray() : []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'assignments' => array_map(fn($assignment) => $assignment->toArray(), $this->assignments),
            'available_stages' => array_map(fn($stage) => $stage->toArray(), $this->available_stages),
            'categories' => $this->categories
        ];
    }

    public static function validate(array $data): array
    {
        $errors = [];

        // Обязательные поля
        if (empty($data['name'])) {
            $errors['name'] = 'Название продукта обязательно';
        }

        if (strlen($data['name']) > 255) {
            $errors['name'] = 'Название продукта не может быть длиннее 255 символов';
        }

        // Валидация описания
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'Описание не может быть длиннее 1000 символов';
        }

        return $errors;
    }
}
