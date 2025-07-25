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
                'username' => 'aylana',
                'image' => 'users/admin.jpg',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'name' => 'Test',
                'username' => 'test00',
                'image' => 'users/manager.jpg',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'name' => 'Вика',
                'username' => 'vika',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
            [
                'name' => 'Диана',
                'username' => 'diana',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
            [
                'name' => 'Илья',
                'username' => 'ilya',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
            [
                'name' => 'Максим',
                'username' => 'maxim',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
            [
                'name' => 'Ширали',
                'username' => 'shirali',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
            [
                'name' => 'Куват',
                'username' => 'kuwat',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
            [
                'name' => 'Ата ага',
                'username' => 'ataaga',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
            [
                'name' => 'Николай',
                'username' => 'nikolay',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
            [
                'name' => 'Джейхун',
                'username' => 'djaykhun',
                'image' => null,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(['username' => $userData['username']], $userData);
        }
    }
}
