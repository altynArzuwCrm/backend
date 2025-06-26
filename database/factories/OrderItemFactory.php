<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Reason;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::inRandomOrder()->value('id') ?? 1,
            'product_id' => Product::inRandomOrder()->value('id') ?? 1,
            'reason_id' => Reason::inRandomOrder()->value('id'),
            'quantity' => $this->faker->numberBetween(1, 50),
            'manager_id' => User::inRandomOrder()->value('id'),
            'deadline' => $this->faker->optional()->dateTimeBetween('now', '+7 days'),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled', 'under_review']),
        ];
    }
}
