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
        Schema::table('stages', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_initial', 'is_final']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('order');
            $table->boolean('is_initial')->default(false)->after('is_active');
            $table->boolean('is_final')->default(false)->after('is_initial');
        });
    }
};
