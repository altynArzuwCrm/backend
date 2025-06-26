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
        $roleId = Role::inRandomOrder()->first();

        return [
            'name' => fake()->name(),
            'role_id' => $roleId,
            'phone' => fake()->phoneNumber(),
            'username' => fake()->userName(),
            'password' => Hash::make('password123'),
        ];
    }
}
