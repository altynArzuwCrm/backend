<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $designerId = User::whereHas('role', function ($query) {
            $query->where('name', 'designer');
        })->inRandomOrder()->value('id');
        return [
            'name' => fake()->words(2, true),
            'default_designer_id' => $designerId,
            'is_workshop_required' => fake()->boolean(30),
            'stage' => fake()->optional()->randomElement(['design', 'print', 'workshop']),
            'workshop_type' => fake()->optional()->randomElement(['montage', 'binding']),
        ];
    }
}
