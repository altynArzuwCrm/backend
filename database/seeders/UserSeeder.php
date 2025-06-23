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
              'name' => 'Администратор',
              'username' => 'admin',
              'password' => Hash::make('password123')
          ],
          [
              'name' => 'Менеджер',
              'username' => 'manager',
              'password' => Hash::make('password123')
          ]
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['username' => $user['username']],
                $user
            );
        }
    }
}
