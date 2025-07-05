<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->title(),
            'client_id' => Client::inRandomOrder()->first()->id,
            'deadline' => fake()->dateTime(),
            'total_price' => fake()->optional()->randomFloat(2, 10, 1000),
            'payment_amount' => fake()->randomFloat(2, 0, 1000),
        ];
    }
} 