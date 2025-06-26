<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientContact>
 */
class ClientContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['phone', 'email', 'telegram', 'whatsapp', 'instagram', 'other'];
        $type = $this->faker->randomElement($types);

        return [
            'client_id' => Client::inRandomOrder()->value('id'),
            'type' => $type,
            'value' => match($type) {
                'phone', 'whatsapp' => $this->faker->phoneNumber,
                'email' => $this->faker->unique()->safeEmail,
                'telegram', 'instagram' => '@' . $this->faker->userName,
                'other' => $this->faker->url,
            },
        ];
    }
}
