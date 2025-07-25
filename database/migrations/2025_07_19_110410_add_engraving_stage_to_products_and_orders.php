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
        // Добавляем поле has_engraving_stage в таблицу products
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_engraving_stage')->default(false)->after('has_workshop_stage');
        });

        // Добавляем стадию engraving в enum orders.stage
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('stage', ['draft', 'design', 'print', 'engraving', 'workshop', 'final', 'completed', 'cancelled'])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем поле has_engraving_stage из таблицы products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_engraving_stage');
        });

        // Возвращаем старую версию enum orders.stage
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('stage', ['draft', 'design', 'print', 'workshop', 'final', 'completed', 'cancelled'])->default('draft')->change();
        });
    }
};
