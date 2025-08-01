<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Проверяем, есть ли уже роли в базе данных
        if (Role::count() > 0) {
            $this->command->info('Roles already exist. Skipping role creation.');
            return;
        }

        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Администратор',
                'description' => 'Полный доступ к системе со всеми разрешениями'
            ],
            [
                'name' => 'manager',
                'display_name' => 'Менеджер',
                'description' => 'Доступ к управлению с большинством разрешений'
            ],
            [
                'name' => 'designer',
                'display_name' => 'Дизайнер',
                'description' => 'Роль дизайнера для работы с дизайном заказов'
            ],
            [
                'name' => 'print_operator',
                'display_name' => 'Оператор печати',
                'description' => 'Роль оператора печати для работы с печатью'
            ],
            [
                'name' => 'workshop_worker',
                'display_name' => 'Работник цеха',
                'description' => 'Роль работника цеха для работы в цехе'
            ],
            [
                'name' => 'engraving_operator',
                'display_name' => 'Оператор гравировки',
                'description' => 'Роль оператора гравировки для работы с гравировкой'
            ],
            [
                'name' => 'bukhgalter',
                'display_name' => 'Бухгалтер',
                'description' => 'Роль бухгалтера для финансовых операций'
            ]
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        $this->command->info('Roles created successfully.');
    }
}
