<?php

namespace Database\Seeders;


use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ClientSeeder::class,
            ClientContactSeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            ProjectSeeder::class,
            OrderSeeder::class,
            OrderAssignmentSeeder::class,
        ]);

        Client::factory(10)->create();
        ClientContact::factory(10)->create();
        User::factory(10)->create();
        Project::factory(10)->create();
        Product::factory(10)->create();
        Order::factory(10)->create();
        OrderAssignment::factory(10)->create();
        Comment::factory(10)->create();
    }
}
