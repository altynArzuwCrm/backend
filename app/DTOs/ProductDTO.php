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
        public ?string $engraving_text,
        public ?string $engraving_font,
        public ?string $engraving_size,
        public ?string $engraving_color,
        public ?string $engraving_position,
        public ?bool $is_active,
        public ?string $created_at,
        public ?string $updated_at,
        public array $assignments = [],
        public array $available_stages = []
    ) {}

    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            description: $product->description,
            engraving_text: $product->engraving_text,
            engraving_font: $product->engraving_font,
            engraving_size: $product->engraving_size,
            engraving_color: $product->engraving_color,
            engraving_position: $product->engraving_position,
            is_active: $product->is_active ?? false,
            created_at: $product->created_at ? (is_string($product->created_at) ? $product->created_at : $product->created_at->toISOString()) : null,
            updated_at: $product->updated_at ? (is_string($product->updated_at) ? $product->updated_at : $product->updated_at->toISOString()) : null,
            assignments: $product->assignments ? $product->assignments->map(fn($assignment) => ProductAssignmentDTO::fromModel($assignment))->toArray() : [],
            available_stages: $product->availableStages ? $product->availableStages->map(fn($stage) => StageDTO::fromModel($stage))->toArray() : []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'engraving_text' => $this->engraving_text,
            'engraving_font' => $this->engraving_font,
            'engraving_size' => $this->engraving_size,
            'engraving_color' => $this->engraving_color,
            'engraving_position' => $this->engraving_position,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'assignments' => array_map(fn($assignment) => $assignment->toArray(), $this->assignments),
            'available_stages' => array_map(fn($stage) => $stage->toArray(), $this->available_stages)
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

        // Валидация полей гравировки
        if (isset($data['engraving_text']) && strlen($data['engraving_text']) > 500) {
            $errors['engraving_text'] = 'Текст гравировки не может быть длиннее 500 символов';
        }

        $validFonts = ['Arial', 'Times New Roman', 'Helvetica', 'Georgia', 'Verdana'];
        if (isset($data['engraving_font']) && !in_array($data['engraving_font'], $validFonts)) {
            $errors['engraving_font'] = 'Неверный шрифт';
        }

        $validSizes = ['small', 'medium', 'large'];
        if (isset($data['engraving_size']) && !in_array($data['engraving_size'], $validSizes)) {
            $errors['engraving_size'] = 'Неверный размер';
        }

        $validPositions = ['top', 'bottom', 'left', 'right', 'center'];
        if (isset($data['engraving_position']) && !in_array($data['engraving_position'], $validPositions)) {
            $errors['engraving_position'] = 'Неверная позиция';
        }

        return $errors;
    }
}
