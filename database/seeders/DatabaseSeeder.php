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
            StageRoleSeeder::class,
            ClientSeeder::class,
            ClientContactSeeder::class,
            ProjectSeeder::class,
            ProductSeeder::class,
            ProductStageSeeder::class,
            OrderSeeder::class,
            OrderAssignmentSeeder::class,
            ProductAssignmentSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
