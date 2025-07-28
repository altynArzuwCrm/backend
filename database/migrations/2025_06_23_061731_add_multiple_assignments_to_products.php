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
        // Создаем таблицу для множественных назначений продуктов
        Schema::create('product_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role_type', ['designer', 'print_operator', 'workshop_worker']);
            $table->integer('priority')->default(1); // Приоритет назначения (1 - высший)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Уникальный индекс для предотвращения дублирования
            $table->unique(['product_id', 'user_id', 'role_type']);
        });

        // Мигрируем существующие назначения
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            if ($product->designer_id) {
                DB::table('product_assignments')->insert([
                    'product_id' => $product->id,
                    'user_id' => $product->designer_id,
                    'role_type' => 'designer',
                    'priority' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($product->print_operator_id) {
                DB::table('product_assignments')->insert([
                    'product_id' => $product->id,
                    'user_id' => $product->print_operator_id,
                    'role_type' => 'print_operator',
                    'priority' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($product->workshop_worker_id) {
                DB::table('product_assignments')->insert([
                    'product_id' => $product->id,
                    'user_id' => $product->workshop_worker_id,
                    'role_type' => 'workshop_worker',
                    'priority' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_assignments');
    }
};
