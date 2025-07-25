<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        // Получаем все роли
        $roles = Role::all()->keyBy('name');

        // Назначаем роли пользователям на основе их старой роли
        $users = User::all();

        foreach ($users as $user) {
            // Спецназначения для Ширали и Кувата
            if ($user->username === 'shirali') {
                $roleNames = ['designer', 'print_operator'];
                foreach ($roleNames as $roleName) {
                    if (isset($roles[$roleName])) {
                        $user->roles()->syncWithoutDetaching([$roles[$roleName]->id]);
                        echo "Назначена роль {$roleName} пользователю {$user->name}\n";
                    }
                }
                continue;
            }
            if ($user->username === 'kuwat') {
                $roleNames = ['print_operator', 'engraving_operator'];
                foreach ($roleNames as $roleName) {
                    if (isset($roles[$roleName])) {
                        $user->roles()->syncWithoutDetaching([$roles[$roleName]->id]);
                        echo "Назначена роль {$roleName} пользователю {$user->name}\n";
                    }
                }
                continue;
            }
            // Остальные пользователи
            if ($user->username === 'aylana') {
                $user->roles()->syncWithoutDetaching([$roles['admin']->id]);
                echo "Назначена роль admin пользователю {$user->name}\n";
                continue;
            }
            if ($user->username === 'test00') {
                $user->roles()->syncWithoutDetaching([$roles['manager']->id]);
                echo "Назначена роль manager пользователю {$user->name}\n";
                continue;
            }
            if (in_array($user->username, ['vika', 'diana', 'ilya', 'maxim'])) {
                $user->roles()->syncWithoutDetaching([$roles['designer']->id]);
                echo "Назначена роль designer пользователю {$user->name}\n";
                continue;
            }
            if ($user->username === 'ataaga') {
                $user->roles()->syncWithoutDetaching([$roles['print_operator']->id]);
                echo "Назначена роль print_operator пользователю {$user->name}\n";
                continue;
            }
            if (in_array($user->username, ['nikolay', 'djaykhun'])) {
                $user->roles()->syncWithoutDetaching([$roles['workshop_worker']->id]);
                echo "Назначена роль workshop_worker пользователю {$user->name}\n";
                continue;
            }
        }

        echo "Назначение ролей завершено\n";
    }
}
