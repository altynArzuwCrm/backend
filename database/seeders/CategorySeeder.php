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
            ['name' => 'Дополнительные услуги'],
            ['name' => 'Офсетная продукция'],
            ['name' => 'Оперативная печать'],
            ['name' => 'Финишные работы'],
            ['name' => 'Наклейки и этикетки'],
            ['name' => 'Пакеты'],
            ['name' => 'Сувенирная продукция'],
            ['name' => 'Календари'],
            ['name' => 'Бланки / Договоры / Сертификаты / Офисная печать'],
            ['name' => 'Постеры'],
            ['name' => 'Баннеры (широкоформатная печать)'],
            ['name' => 'Каталоги'],
            ['name' => 'Буклеты'],
            ['name' => 'Флаеры'],
            ['name' => 'Визитки'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
