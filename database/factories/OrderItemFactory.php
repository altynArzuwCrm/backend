<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
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
        $managers = User::where('role', 'manager')->get();
        return [
            'order_id' => Order::inRandomOrder()->value('id') ?? 1,
            'product_id' => Product::inRandomOrder()->value('id') ?? 1,
            'quantity' => $this->faker->numberBetween(1, 50),
            'manager_id' => $managers->isNotEmpty() ? $managers->random()->id : null,
            'price' => fake()->optional()->randomFloat(2, 10, 1000),
            'deadline' => $this->faker->optional()->dateTimeBetween('now', '+7 days'),
            'stage' => $this->faker->randomElement(['draft', 'design', 'print', 'workshop', 'final', 'archived', 'completed', 'cancelled']),
        ];
    }
}
