<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $order = Order::inRandomOrder()->first();

        return [
            'order_id' => $order->id,
            'message' => $this->faker->sentence(),
            'send_time' => $this->faker->time('H:i:s'),
            'is_delivered' => $this->faker->boolean(70),
        ];
    }
}
