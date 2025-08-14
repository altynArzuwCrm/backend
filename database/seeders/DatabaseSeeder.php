<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            StageSeeder::class,
            ClientSeeder::class,
            ClientContactSeeder::class,
            ProjectSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            AuditLogSeeder::class,
            OrderAssignmentSeeder::class,
            ProductAssignmentSeeder::class,
        ]);
    }
}
