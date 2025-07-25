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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_assignments');
    }
};
