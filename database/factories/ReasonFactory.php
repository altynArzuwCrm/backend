<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reason>
 */
class ReasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Not Responding', 'Refused', 'Defective Product'];

        return [
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement($types),
        ];
    }
}
