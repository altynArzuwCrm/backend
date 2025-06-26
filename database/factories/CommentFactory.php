<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id'),
            'order_id' => fake()->optional()->randomElement(Order::pluck('id')->toArray()),
            'order_item_id' => fake()->optional()->randomElement(OrderItem::pluck('id')->toArray()),
            'text' => fake()->realTextBetween(30, 150),
        ];
    }
}
