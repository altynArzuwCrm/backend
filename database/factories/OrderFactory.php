<?php

namespace Database\Factories;


use App\Models\Client;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $client = Client::inRandomOrder()->first();
        $stage = Stage::inRandomOrder()->first();
        $manager = User::where('role', 'manager')->inRandomOrder()->first();
        $executor = User::where('role', 'executor')->inRandomOrder()->first();
        return [
            'title' => $this->faker->sentence(3),
            'client_id' =>  $client->id,
            'status' => $this->faker->randomElement(['cannot_be_done', 'needs_revision', 'cancelled', 'done']),
            'stage_id' => $stage->id,
            'manager_id' => $manager->id,
            'executor_id' => $executor->id,
        ];
    }
}
