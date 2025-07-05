<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => $this->faker->randomElement(['admin', 'manager', 'designer', 'print_operator', 'workshop_worker']),
            'phone' => fake()->phoneNumber(),
            'username' => fake()->userName(),
            'password' => Hash::make('password123'),
        ];
    }
}
