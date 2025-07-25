<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Role;
use Tests\TestCase;

class SimpleApiTest extends TestCase
{
    public function test_can_access_products_api()
    {
        // Получаем существующего админа
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $this->markTestSkipped('Admin user not found');
        }

        $response = $this->actingAs($admin)
            ->getJson('/api/products');

        $response->assertStatus(200);
    }

    public function test_can_create_product_assignment()
    {
        // Получаем существующего админа
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $this->markTestSkipped('Admin user not found');
        }

        // Получаем существующий продукт
        $product = Product::first();

        if (!$product) {
            $this->markTestSkipped('Product not found');
        }

        // Получаем существующего дизайнера
        $designer = User::where('role', 'designer')->first();

        if (!$designer) {
            $this->markTestSkipped('Designer user not found');
        }

        $response = $this->actingAs($admin)
            ->postJson("/api/products/{$product->id}/assignments", [
                'user_id' => $designer->id,
                'role_type' => 'designer',
                'priority' => 1
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Назначение создано'
        ]);
    }

    public function test_can_get_product_assignments()
    {
        // Получаем существующего админа
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $this->markTestSkipped('Admin user not found');
        }

        // Получаем существующий продукт
        $product = Product::first();

        if (!$product) {
            $this->markTestSkipped('Product not found');
        }

        $response = $this->actingAs($admin)
            ->getJson("/api/products/{$product->id}/assignments");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'product_id',
            'assignments'
        ]);
    }

    public function test_can_get_available_users()
    {
        // Получаем существующего админа
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $this->markTestSkipped('Admin user not found');
        }

        // Получаем существующий продукт
        $product = Product::first();

        if (!$product) {
            $this->markTestSkipped('Product not found');
        }

        $response = $this->actingAs($admin)
            ->getJson("/api/products/{$product->id}/assignments/available-users?role_type=designer");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'role_type',
            'available_users'
        ]);
    }
}
