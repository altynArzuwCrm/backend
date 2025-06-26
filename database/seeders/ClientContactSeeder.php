<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Database\Seeder;

class ClientContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['phone', 'email', 'telegram', 'whatsapp', 'instagram', 'other'];

        Client::all()->each(function ($client) use ($types) {
            $count = rand(1, 3);

            for ($i = 0; $i < $count; $i++) {
                $type = $types[array_rand($types)];

                $value = match ($type) {
                    'phone'     => fake()->phoneNumber(),
                    'email'     => fake()->unique()->safeEmail(),
                    'telegram'  => '@' . fake()->userName(),
                    'whatsapp'  => '+'.fake()->numerify('7##########'),
                    'instagram' => '@' . fake()->userName(),
                    'other'     => fake()->url(),
                };

                ClientContact::create([
                    'client_id' => $client->id,
                    'type' => $type,
                    'value' => $value,
                ]);
            }
        });
    }
}
