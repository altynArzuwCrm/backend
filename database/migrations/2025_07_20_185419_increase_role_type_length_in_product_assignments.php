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
            $table->string('role_type', 50)->change(); // Увеличиваем с 20 до 50 символов
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_assignments', function (Blueprint $table) {
            $table->string('role_type', 20)->change(); // Возвращаем к исходному размеру
        });
    }
};
