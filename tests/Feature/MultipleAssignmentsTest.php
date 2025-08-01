<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Stage;
use App\Models\ProductAssignment;
use App\Models\OrderAssignment;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем роли
        $roles = [
            ['name' => 'admin', 'display_name' => 'Администратор'],
            ['name' => 'manager', 'display_name' => 'Менеджер'],
            ['name' => 'designer', 'display_name' => 'Дизайнер'],
            ['name' => 'print_operator', 'display_name' => 'Оператор печати'],
            ['name' => 'workshop_worker', 'display_name' => 'Работник цеха'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

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

        $this->printOperator = User::create([
            'name' => 'Оператор печати',
            'username' => 'print_operator',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);

        // Назначаем роли пользователям
        $adminRole = Role::where('name', 'admin')->first();
        $designerRole = Role::where('name', 'designer')->first();
        $printOperatorRole = Role::where('name', 'print_operator')->first();

        $this->admin->roles()->attach($adminRole->id);
        $this->designer1->roles()->attach($designerRole->id);
        $this->designer2->roles()->attach($designerRole->id);
        $this->designer3->roles()->attach($designerRole->id);
        $this->designer4->roles()->attach($designerRole->id);
        $this->printOperator->roles()->attach($printOperatorRole->id);

        // Создаем продукт
        $this->product = Product::create([
            'name' => 'Тестовый продукт',
            'has_design_stage' => true,
            'has_print_stage' => true,
            'has_workshop_stage' => true,
        ]);

        // Создаем клиента
        $this->client = \App\Models\Client::create([
            'name' => 'Тестовый клиент',
            'company_name' => 'Тестовая компания',
            'phone' => '+7 999 123-45-67',
            'email' => 'test@example.com'
        ]);
    }

    /** @test */
    public function can_assign_multiple_designers_to_product()
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

        // Проверяем приоритеты
        $assignments = $this->product->designerAssignments()->get();
        $this->assertEquals(1, $assignments[0]->priority);
        $this->assertEquals(2, $assignments[1]->priority);
        $this->assertEquals(3, $assignments[2]->priority);
        $this->assertEquals(4, $assignments[3]->priority);
    }

    /** @test */
    public function can_get_product_assignments()
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
            ],
            [
                'user_id' => $this->printOperator->id,
                'role_type' => 'print_operator',
                'priority' => 1
            ]
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/products/{$this->product->id}/assignments");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'product_id',
            'assignments' => [
                'designer',
                'print_operator'
            ]
        ]);

        $data = $response->json();
        $this->assertEquals(2, count($data['assignments']['designer']));
        $this->assertEquals(1, count($data['assignments']['print_operator']));
    }

    /** @test */
    public function can_get_available_users()
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
    public function can_update_assignment()
    {
        $assignment = $this->product->assignments()->create([
            'user_id' => $this->designer1->id,
            'role_type' => 'designer',
            'priority' => 1
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/products/{$this->product->id}/assignments/{$assignment->id}", [
                'priority' => 2,
                'is_active' => false
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Назначение обновлено'
        ]);

        $assignment->refresh();
        $this->assertEquals(2, $assignment->priority);
        $this->assertFalse($assignment->is_active);
    }

    /** @test */
    public function can_delete_assignment()
    {
        $assignment = $this->product->assignments()->create([
            'user_id' => $this->designer1->id,
            'role_type' => 'designer',
            'priority' => 1
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/products/{$this->product->id}/assignments/{$assignment->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Назначение удалено'
        ]);

        $this->assertDatabaseMissing('product_assignments', ['id' => $assignment->id]);
    }

    /** @test */
    public function automatic_assignment_when_order_stage_changes()
    {
        // Создаем назначения для продукта
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
            ],
            [
                'user_id' => $this->printOperator->id,
                'role_type' => 'print_operator',
                'priority' => 1
            ]
        ]);

        // Получаем стадию draft
        $draftStage = Stage::where('name', 'draft')->first();

        // Создаем заказ
        $order = Order::create([
            'client_id' => $this->client->id,
            'product_id' => $this->product->id,
            'stage_id' => $draftStage ? $draftStage->id : 1
        ]);

        // Переводим на стадию дизайна
        $response = $this->actingAs($this->admin)
            ->putJson("/api/orders/{$order->id}/stage", [
                'stage' => 'design'
            ]);

        $response->assertStatus(200);

        // Проверяем, что создались назначения для дизайнеров
        $order->refresh();
        $designerAssignments = $order->assignments()
            ->whereHas('user.roles', function ($q) {
                $q->where('name', 'designer');
            })
            ->get();

        $this->assertEquals(2, $designerAssignments->count());
        $this->assertTrue($designerAssignments->contains('user_id', $this->designer1->id));
        $this->assertTrue($designerAssignments->contains('user_id', $this->designer2->id));
    }

    /** @test */
    public function product_methods_work_correctly()
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

        // Тестируем метод getDesigners()
        $designers = $this->product->getDesigners();
        $this->assertEquals(2, $designers->count());
        $this->assertTrue($designers->contains('id', $this->designer1->id));
        $this->assertTrue($designers->contains('id', $this->designer2->id));

        // Тестируем метод getNextAvailableUser()
        $nextUser = $this->product->getNextAvailableUser('designer');
        $this->assertNotNull($nextUser);
        $this->assertEquals($this->designer1->id, $nextUser->user_id);

        // Тестируем с исключением
        $nextUser = $this->product->getNextAvailableUser('designer', [$this->designer1->id]);
        $this->assertNotNull($nextUser);
        $this->assertEquals($this->designer2->id, $nextUser->user_id);
    }

    /** @test */
    public function prevents_duplicate_assignments()
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
    public function validates_user_role()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/products/{$this->product->id}/assignments", [
                'user_id' => $this->printOperator->id, // пользователь с ролью print_operator
                'role_type' => 'designer', // пытаемся назначить как дизайнера
                'priority' => 1
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Пользователь не имеет роль designer'
        ]);
    }
}
