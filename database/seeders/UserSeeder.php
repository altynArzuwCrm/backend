<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Проверяем, есть ли уже пользователи в базе данных
        if (User::count() > 0) {
            $this->command->info('Users already exist. Skipping user creation.');
            return;
        }

        // Создаем администратора
        $admin = User::create([
            'name' => 'Aylana',
            'username' => 'aylana',
            'phone' => null,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->attach($adminRole->id);
        }

        // Создаем менеджера
        $manager = User::create([
            'name' => 'Test',
            'username' => 'test',
            'phone' => null,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $manager->roles()->attach($managerRole->id);
        }

        // Создаем сотрудников с разными ролями
        $employees = [
            [
                'name' => 'Вика',
                'username' => 'vika',
                'phone' => null,
                'roles' => ['designer']
            ],
            [
                'name' => 'Диана',
                'username' => 'diana',
                'phone' => null,
                'roles' => ['designer']
            ],
            [
                'name' => 'Илья',
                'username' => 'ilya',
                'phone' => null,
                'roles' => ['designer']
            ],
            [
                'name' => 'Максим',
                'username' => 'maxim',
                'phone' => null,
                'roles' => ['designer']
            ],
            [
                'name' => 'Ширали',
                'username' => 'shirali',
                'phone' => null,
                'roles' => ['designer', 'print_operator']
            ],
            [
                'name' => 'Куват',
                'username' => 'kuwat',
                'phone' => null,
                'roles' => ['print_operator', 'engraving_operator']
            ],
            [
                'name' => 'Ата ага',
                'username' => 'ata_aga',
                'phone' => null,
                'roles' => ['print_operator']
            ],
            [
                'name' => 'Николай',
                'username' => 'nikolay',
                'phone' => null,
                'roles' => ['workshop_worker']
            ],
            [
                'name' => 'Джейхун',
                'username' => 'jayhun',
                'phone' => null,
                'roles' => ['workshop_worker']
            ]
        ];

        foreach ($employees as $employeeData) {
            $user = User::create([
                'name' => $employeeData['name'],
                'username' => $employeeData['username'],
                'phone' => $employeeData['phone'],
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            // Привязываем все роли пользователя
            foreach ($employeeData['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $user->roles()->attach($role->id);
                }
            }
        }

        $this->command->info('Users created successfully.');
    }
}
