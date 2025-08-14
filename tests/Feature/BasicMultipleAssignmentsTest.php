<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicMultipleAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_roles_and_users()
    {
        // Получаем существующие роли
        $adminRole = Role::where('name', 'admin')->first();
        $designerRole = Role::where('name', 'designer')->first();

        // Создаем пользователей
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $designer1 = User::create([
            'name' => 'Дизайнер 1',
            'username' => 'designer1',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $designer2 = User::create([
            'name' => 'Дизайнер 2',
            'username' => 'designer2',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        // Назначаем роли
        $admin->roles()->attach($adminRole->id);
        $designer1->roles()->attach($designerRole->id);
        $designer2->roles()->attach($designerRole->id);

        // Проверяем
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($designer1->hasRole('designer'));
        $this->assertTrue($designer2->hasRole('designer'));
        $this->assertFalse($designer1->hasRole('admin'));
    }

    public function test_can_create_product_assignments()
    {
        // Получаем существующую роль
        $designerRole = Role::where('name', 'designer')->first();

        $designer1 = User::create([
            'name' => 'Дизайнер 1',
            'username' => 'designer1',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $designer2 = User::create([
            'name' => 'Дизайнер 2',
            'username' => 'designer2',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $designer1->roles()->attach($designerRole->id);
        $designer2->roles()->attach($designerRole->id);

        // Создаем продукт
        $product = Product::create([
            'name' => 'Тестовый продукт',
        ]);

        // Создаем назначения
        $assignment1 = $product->assignments()->create([
            'user_id' => $designer1->id,
            'role_type' => 'designer',
            'is_active' => true
        ]);

        $assignment2 = $product->assignments()->create([
            'user_id' => $designer2->id,
            'role_type' => 'designer',
            'is_active' => true
        ]);

        // Проверяем
        $this->assertEquals(2, $product->designerAssignments()->count());

        // Проверяем методы продукта
        $designers = $product->getDesigners();
        $this->assertEquals(2, $designers->count());
        $this->assertTrue($designers->contains('id', $designer1->id));
        $this->assertTrue($designers->contains('id', $designer2->id));
    }

    public function test_can_get_next_available_user()
    {
        // Получаем существующую роль
        $designerRole = Role::where('name', 'designer')->first();

        $designer1 = User::create([
            'name' => 'Дизайнер 1',
            'username' => 'designer1',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $designer2 = User::create([
            'name' => 'Дизайнер 2',
            'username' => 'designer2',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $designer1->roles()->attach($designerRole->id);
        $designer2->roles()->attach($designerRole->id);

        // Создаем продукт
        $product = Product::create([
            'name' => 'Тестовый продукт',
        ]);

        // Создаем назначения
        $product->assignments()->createMany([
            [
                'user_id' => $designer1->id,
                'role_type' => 'designer',
                'is_active' => true
            ],
            [
                'user_id' => $designer2->id,
                'role_type' => 'designer',
                'is_active' => true
            ]
        ]);

        // Тестируем получение следующего доступного пользователя
        $nextUser = $product->getNextAvailableUser('designer');
        $this->assertNotNull($nextUser);
        $this->assertEquals($designer1->id, $nextUser->user_id);

        // Тестируем с исключением
        $nextUser = $product->getNextAvailableUser('designer', [$designer1->id]);
        $this->assertNotNull($nextUser);
        $this->assertEquals($designer2->id, $nextUser->user_id);
    }
}
