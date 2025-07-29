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
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // internal name like 'draft', 'design'
            $table->string('display_name'); // display name like 'Черновик', 'Дизайн'
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // order in workflow
            $table->boolean('is_active')->default(true);
            $table->boolean('is_initial')->default(false); // starting stage
            $table->boolean('is_final')->default(false); // ending stages (completed, cancelled)
            $table->string('color', 7)->default('#6366f1'); // hex color for UI
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'order']);
        });

        // Insert default stages that match current system
        $stages = [
            ['name' => 'draft', 'display_name' => 'Черновик', 'order' => 1, 'is_initial' => true, 'color' => '#64748b'],
            ['name' => 'design', 'display_name' => 'Дизайн', 'order' => 2, 'color' => '#8b5cf6'],
            ['name' => 'print', 'display_name' => 'Печать', 'order' => 3, 'color' => '#06b6d4'],
            ['name' => 'engraving', 'display_name' => 'Гравировка', 'order' => 4, 'color' => '#f59e0b'],
            ['name' => 'workshop', 'display_name' => 'Цех', 'order' => 5, 'color' => '#10b981'],
            ['name' => 'final', 'display_name' => 'Финал', 'order' => 6, 'color' => '#3b82f6'],
            ['name' => 'completed', 'display_name' => 'Завершен', 'order' => 7, 'is_final' => true, 'color' => '#22c55e'],
            ['name' => 'cancelled', 'display_name' => 'Отменен', 'order' => 8, 'is_final' => true, 'color' => '#ef4444'],
        ];

        foreach ($stages as $stage) {
            $stage['created_at'] = now();
            $stage['updated_at'] = now();
            DB::table('stages')->insert($stage);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
