<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_default')->default(false); // used for default stage when creating orders
            $table->timestamps();

            $table->unique(['product_id', 'stage_id']);
        });

        // Add default stages for all existing products
        $this->addDefaultStagesToProducts();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stages');
    }

    private function addDefaultStagesToProducts()
    {
        $products = DB::table('products')->get();
        $stages = DB::table('stages')->get()->keyBy('name');

        foreach ($products as $product) {
            // Add all stages to all products by default
            foreach ($stages as $stage) {
                DB::table('product_stages')->insert([
                    'product_id' => $product->id,
                    'stage_id' => $stage->id,
                    'is_available' => true,
                    'is_default' => $stage->name === 'draft', // draft is default starting stage
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
