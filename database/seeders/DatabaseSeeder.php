<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FunnelSeeder::class,
            StageSeeder::class,
        ]);

        Client::factory(10)->create();
        User::factory()->count(10)->create();
        Order::factory()->count(10)->create();
        Message::factory()->count(10)->create();
    }
}
