<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Визитная карточка',
                'designer_id' => 3,
                'is_workshop_required' => false,
                'workshop_type' => null,
            ],
            [
                'name' => 'Переплет книги',
                'designer_id' => null,
                'is_workshop_required' => true,
                'workshop_type' => 'binding',
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
