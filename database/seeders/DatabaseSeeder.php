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
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            UserRoleSeeder::class,
            ClientSeeder::class,
            ProjectSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            OrderAssignmentSeeder::class,
            CommentSeeder::class,
            AuditLogSeeder::class,
            ClientContactSeeder::class,
            // Добавляю ProductAssignmentSeeder
            ProductAssignmentSeeder::class,
        ]);

        // All data created in specific seeders above
        // Client::factory(10)->create();
        // ClientContact::factory(10)->create();
        // User::factory(10)->create();
        // Project::factory(10)->create();
        // Product::factory(10)->create();
        // Order::factory(20)->create();
        // OrderAssignment::factory(10)->create();
        // Comment::factory(10)->create();
    }
}
