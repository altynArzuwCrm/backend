<?php

namespace Database\Seeders;


use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAssignment;
use App\Models\Product;
use App\Models\Reason;
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
            ReasonSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            OrderItemSeeder::class,
        ]);

        Client::factory(10)->create();
        ClientContact::factory(10)->create();
        User::factory(10)->create();
        Order::factory(10)->create();
        Product::factory(10)->create();
        OrderItem::factory(10)->create();
        OrderItemAssignment::factory(10)->create();
        Reason::factory(10)->create();
        Comment::factory(10)->create();
    }
}
