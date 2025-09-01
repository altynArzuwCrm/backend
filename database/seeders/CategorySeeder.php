<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Печатная продукция'],
            ['name' => 'Сувенирная продукция'],
            ['name' => 'Рекламные материалы'],
            ['name' => 'Упаковка'],
            ['name' => 'Канцелярские товары'],
            ['name' => 'Промо-материалы'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
