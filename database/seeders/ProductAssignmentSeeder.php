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
                    'designer' => ['вика'],
                    'print_operator' => ['ширали'],
                    'engraving_operator' => ['куват'],
                    'workshop_worker' => ['николай', 'джейхун'],
                ]);
                continue;
            }
            // Пакеты стикер
            if (mb_strtolower($name) === 'пакеты стикер') {
                $this->assign($product, [
                    'designer' => ['вика'],
                    'print_operator' => ['куват'],
                    'workshop_worker' => ['николай'],
                ]);
                continue;
            }
            // Папки
            if (mb_strtolower($name) === 'папки') {
                $this->assign($product, [
                    'designer' => ['вика', 'диана', 'илья', 'максим', 'ширали'],
                    'print_operator' => ['ата ага'],
                    'workshop_worker' => ['николай'],
                ]);
                continue;
            }
            // Остальные продукты
            $this->assign($product, [
                'designer' => ['вика', 'диана', 'илья', 'максим', 'ширали'],
                'print_operator' => ['ширали', 'ата ага', 'куват',],
                'engraving_operator' => ['куват'],
                'workshop_worker' => ['николай', 'джейхун'],
            ]);
        }
    }

    private function assign($product, $roles)
    {
        foreach ($roles as $role => $users) {
            foreach ($users as $userName) {
                $user = User::where('name', $userName)->first();
                if (!$user) continue;
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
