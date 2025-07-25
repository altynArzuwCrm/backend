<?php

namespace Database\Factories;

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
            'title' => $this->faker->sentence(3),
            'deadline' => $this->faker->dateTimeBetween('now', '+30 days'),
            'total_price' => $this->faker->randomFloat(2, 1000, 50000),
            'payment_amount' => $this->faker->randomFloat(2, 0, 50000),
        ];
    }
} 