<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductAssignment;

class ProductAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $name = $product->name;
            // Пакеты
            if (mb_strtolower($name) === 'пакеты') {
                $this->assign($product, [
                    'designer' => ['Вика'],
                    'print_operator' => ['Ширали'],
                    'engraving_operator' => ['Куват'],
                    'workshop_worker' => ['Николай', 'Джейхун'],
                ]);
                continue;
            }
            // Пакеты стикер
            if (mb_strtolower($name) === 'пакеты стикер') {
                $this->assign($product, [
                    'designer' => ['Вика'],
                    'print_operator' => ['Куват'],
                    'workshop_worker' => ['Николай'],
                ]);
                continue;
            }
            // Папки
            if (mb_strtolower($name) === 'папки') {
                $this->assign($product, [
                    'designer' => ['Вика', 'Диана', 'Илья', 'Максим', 'Ширали'],
                    'print_operator' => ['Ата ага'],
                    'workshop_worker' => ['Николай'],
                ]);
                continue;
            }
            // Остальные продукты
            $this->assign($product, [
                'designer' => ['Вика', 'Диана', 'Илья', 'Максим', 'Ширали'],
                'print_operator' => ['Ширали', 'Ата ага', 'Куват'],
                'engraving_operator' => ['Куват'],
                'workshop_worker' => ['Николай', 'Джейхун'],
            ]);
        }
    }

    private function assign($product, $roles)
    {
        foreach ($roles as $role => $users) {
            foreach ($users as $userName) {
                // Ищем пользователя по имени (с учетом регистра)
                $user = User::where('name', $userName)->first();
                if (!$user) {
                    // Если не найден, попробуем найти по username
                    $user = User::where('username', strtolower($userName))->first();
                }
                if (!$user) {
                    $this->command->warn("User not found: {$userName}");
                    continue;
                }

                ProductAssignment::firstOrCreate([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'role_type' => $role,
                ], [
                    'is_active' => true,
                ]);
            }
        }
    }
}
