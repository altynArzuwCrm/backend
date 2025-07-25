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
        // Добавляем роль engraving_operator
        DB::table('roles')->insert([
            'name' => 'engraving_operator',
            'display_name' => 'Оператор гравировки',
            'description' => 'Специалист по гравировке и лазерной обработке',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем роль engraving_operator
        DB::table('roles')->where('name', 'engraving_operator')->delete();
    }
};
