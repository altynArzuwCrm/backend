<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductStageSeeder extends Seeder
{
    public function run(): void
    {
        $this->addDefaultStagesToProducts();
    }

    private function addDefaultStagesToProducts()
    {
        $products = DB::table('products')->get();
        $stages = DB::table('stages')->get()->keyBy('name');

        foreach ($products as $product) {
            foreach ($stages as $stage) {
                DB::table('product_stages')->updateOrInsert(
                    ['product_id' => $product->id, 'stage_id' => $stage->id],
                    [
                        'is_available' => true,
                        'is_default' => $stage->name === 'draft',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
