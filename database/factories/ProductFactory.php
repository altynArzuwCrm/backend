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
        $designers = User::where('role', 'designer')->where('is_active', true)->get();
        $printOperators = User::where('role', 'print_operator')->where('is_active', true)->get();
        $workshopWorkers = User::where('role', 'workshop_worker')->where('is_active', true)->get();
        return [
            'name' => fake()->words(2, true),
            'designer_id' => $designers->isNotEmpty() ? $designers->random()->id : null,
            'print_operator_id' => $printOperators->isNotEmpty() ? $printOperators->random()->id : null,
            'workshop_worker_id' => $workshopWorkers->isNotEmpty() ? $workshopWorkers->random()->id : null,
            'has_design_stage' => fake()->boolean(80),
            'has_print_stage' => fake()->boolean(90),
            'has_workshop_stage' => fake()->boolean(70),
        ];
    }
}
