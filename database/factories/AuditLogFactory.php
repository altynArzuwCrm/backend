<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'auditable_type' => 'App\Models\Order',
            'auditable_id' => 1, // Простой ID без зависимости
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted']),
            'change_type' => $this->faker->randomElement(['created', 'updated', 'deleted']),
            'old_values' => json_encode(['status' => 'draft']),
            'new_values' => json_encode(['status' => 'design']),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Создает запись для создания заказа
     */
    public function orderCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'created',
            'change_type' => 'created',
            'old_values' => json_encode([]),
            'new_values' => json_encode(['stage' => 'draft', 'quantity' => 1]),
        ]);
    }

    /**
     * Создает запись для обновления заказа
     */
    public function orderUpdated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'updated',
            'change_type' => 'updated',
            'old_values' => json_encode(['stage' => 'draft']),
            'new_values' => json_encode(['stage' => 'design']),
        ]);
    }

    /**
     * Создает запись для удаления заказа
     */
    public function orderDeleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'deleted',
            'change_type' => 'deleted',
            'old_values' => json_encode(['stage' => 'completed']),
            'new_values' => json_encode([]),
        ]);
    }
}
