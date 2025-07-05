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
        $designers = User::where('role', 'designer')->get();
        return [
            'name' => fake()->words(2, true),
            'designer_id' => $designers->random(),
            'is_workshop_required' => fake()->boolean(30),
            'workshop_type' => fake()->optional()->randomElement(['montage', 'binding']),
        ];
    }
}
