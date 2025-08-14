<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiMultipleAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Получаем существующие роли
        $this->adminRole = Role::where('name', 'admin')->first();
        $this->designerRole = Role::where('name', 'designer')->first();
        $this->printOperatorRole = Role::where('name', 'print_operator')->first();

        // Создаем пользователей
        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $this->designer1 = User::create([
            'name' => 'Дизайнер 1',
            'username' => 'designer1',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $this->designer2 = User::create([
            'name' => 'Дизайнер 2',
            'username' => 'designer2',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $this->designer3 = User::create([
            'name' => 'Дизайнер 3',
            'username' => 'designer3',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        $this->designer4 = User::create([
            'name' => 'Дизайнер 4',
            'username' => 'designer4',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        // Назначаем роли
        $this->admin->roles()->attach($this->adminRole->id);
        $this->designer1->roles()->attach($this->designerRole->id);
        $this->designer2->roles()->attach($this->designerRole->id);
        $this->designer3->roles()->attach($this->designerRole->id);
        $this->designer4->roles()->attach($this->designerRole->id);

        // Создаем продукт
        $this->product = Product::create([
            'name' => 'Тестовый продукт',
        ]);
    }

    /** @test */
    public function can_assign_multiple_designers_via_api()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/products/{$this->product->id}/assignments/bulk", [
                'assignments' => [
                    [
                        'user_id' => $this->designer1->id,
                        'role_type' => 'designer',
                        'priority' => 1
                    ],
                    [
                        'user_id' => $this->designer2->id,
                        'role_type' => 'designer',
                        'priority' => 2
                    ],
                    [
                        'user_id' => $this->designer3->id,
                        'role_type' => 'designer',
                        'priority' => 3
                    ],
                    [
                        'user_id' => $this->designer4->id,
                        'role_type' => 'designer',
                        'priority' => 4
                    ]
                ]
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Массовое назначение завершено',
            'total_created' => 4
        ]);

        // Проверяем, что назначения созданы
        $this->assertEquals(4, $this->product->designerAssignments()->count());
    }

    /** @test */
    public function can_get_product_assignments_via_api()
    {
        // Создаем назначения
        $this->product->assignments()->createMany([
            [
                'user_id' => $this->designer1->id,
                'role_type' => 'designer',
                'priority' => 1
            ],
            [
                'user_id' => $this->designer2->id,
                'role_type' => 'designer',
                'priority' => 2
            ]
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/products/{$this->product->id}/assignments");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'product_id',
            'assignments' => [
                'designer'
            ]
        ]);

        $data = $response->json();
        $this->assertEquals(2, count($data['assignments']['designer']));
    }

    /** @test */
    public function can_get_available_users_via_api()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/products/{$this->product->id}/assignments/available-users?role_type=designer");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'role_type',
            'available_users'
        ]);

        $data = $response->json();
        $this->assertEquals('designer', $data['role_type']);
        $this->assertEquals(4, count($data['available_users'])); // 4 дизайнера
    }

    /** @test */
    public function prevents_duplicate_assignments_via_api()
    {
        // Создаем первое назначение
        $this->product->assignments()->create([
            'user_id' => $this->designer1->id,
            'role_type' => 'designer',
            'priority' => 1
        ]);

        // Пытаемся создать дубликат
        $response = $this->actingAs($this->admin)
            ->postJson("/api/products/{$this->product->id}/assignments", [
                'user_id' => $this->designer1->id,
                'role_type' => 'designer',
                'priority' => 2
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Пользователь уже назначен на эту роль для данного продукта'
        ]);
    }

    /** @test */
    public function validates_user_role_via_api()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/products/{$this->product->id}/assignments", [
                'user_id' => $this->designer1->id, // пользователь с ролью designer
                'role_type' => 'print_operator', // пытаемся назначить как оператора печати
                'priority' => 1
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Пользователь не имеет роль print_operator'
        ]);
    }
}
