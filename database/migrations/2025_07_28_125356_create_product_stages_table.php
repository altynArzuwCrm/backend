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

        // Migrate existing has_*_stage data to new system
        $this->migrateExistingStageData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stages');
    }

    private function migrateExistingStageData()
    {
        // Get stage mappings
        $stageMap = [
            'design' => 'has_design_stage',
            'print' => 'has_print_stage',
            'engraving' => 'has_engraving_stage',
            'workshop' => 'has_workshop_stage'
        ];

        $products = DB::table('products')->get();
        $stages = DB::table('stages')->whereIn('name', array_keys($stageMap))->get()->keyBy('name');

        foreach ($products as $product) {
            foreach ($stageMap as $stageName => $hasField) {
                $stage = $stages->get($stageName);
                if ($stage && $product->$hasField) {
                    DB::table('product_stages')->insert([
                        'product_id' => $product->id,
                        'stage_id' => $stage->id,
                        'is_available' => true,
                        'is_default' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Add default stages that all products should have
            $defaultStages = ['draft', 'final', 'completed', 'cancelled'];
            foreach ($defaultStages as $stageName) {
                $stage = $stages->get($stageName) ?? DB::table('stages')->where('name', $stageName)->first();
                if ($stage) {
                    // Check if not already added
                    $exists = DB::table('product_stages')
                        ->where('product_id', $product->id)
                        ->where('stage_id', $stage->id)
                        ->exists();

                    if (!$exists) {
                        DB::table('product_stages')->insert([
                            'product_id' => $product->id,
                            'stage_id' => $stage->id,
                            'is_available' => true,
                            'is_default' => $stageName === 'draft', // draft is default starting stage
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
};
