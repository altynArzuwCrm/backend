<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Добавляем базовые роли
        $roles = [
            ['name' => 'admin', 'display_name' => 'Администратор', 'description' => 'Полный доступ к системе'],
            ['name' => 'manager', 'display_name' => 'Менеджер', 'description' => 'Управление заказами и клиентами'],
            ['name' => 'designer', 'display_name' => 'Дизайнер', 'description' => 'Создание дизайнов'],
            ['name' => 'print_operator', 'display_name' => 'Оператор печати', 'description' => 'Печать продукции'],
            ['name' => 'workshop_worker', 'display_name' => 'Работник цеха', 'description' => 'Производство продукции'],
            ['name' => 'engraving_operator', 'display_name' => 'Оператор гравировки', 'description' => 'Гравировка на продукции'],
            ['name' => 'bukhgalter', 'display_name' => 'Бухгалтер', 'description' => 'Финансовый учет'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                $role
            );
        }

        // Назначаем роль admin первому пользователю по умолчанию
        $firstUser = DB::table('users')->first();
        if ($firstUser) {
            $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
            if ($adminRoleId) {
                DB::table('user_roles')->updateOrInsert(
                    ['user_id' => $firstUser->id, 'role_id' => $adminRoleId],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
