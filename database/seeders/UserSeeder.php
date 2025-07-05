<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Aylana',
                'username' => 'lana06',
                'image' => null,
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Test',
                'username' => 'test00',
                'image' => 'users/manager.jpg',
                'password' => Hash::make('password123'),
                'role' => 'workshop_worker',
            ],
            [
                'name' => 'Test1',
                'username' => 'test01',
                'image' => null,
                'password' => Hash::make('password123'),
                'role' => 'designer',
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
