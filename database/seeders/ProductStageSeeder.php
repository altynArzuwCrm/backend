<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add default stages for all existing products
        $this->addDefaultStagesToProducts();
    }

    private function addDefaultStagesToProducts()
    {
        $products = DB::table('products')->get();
        $stages = DB::table('stages')->get()->keyBy('name');

        foreach ($products as $product) {
            // Add all stages to all products by default
            foreach ($stages as $stage) {
                DB::table('product_stages')->updateOrInsert(
                    ['product_id' => $product->id, 'stage_id' => $stage->id],
                    [
                        'is_available' => true,
                        'is_default' => $stage->name === 'draft', // draft is default starting stage
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
