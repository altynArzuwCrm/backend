<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_assignments', function (Blueprint $table) {
            // Изменяем enum на string для поддержки динамических ролей
            $table->string('role_type', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_assignments', function (Blueprint $table) {
            // Возвращаем enum (если нужно откатить)
            $table->enum('role_type', ['designer', 'print_operator', 'workshop_worker'])->change();
        });
    }
};
