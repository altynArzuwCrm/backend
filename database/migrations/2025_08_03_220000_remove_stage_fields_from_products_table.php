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
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'has_design_stage',
                'has_print_stage',
                'has_workshop_stage'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_design_stage')->default(false);
            $table->boolean('has_print_stage')->default(false);
            $table->boolean('has_workshop_stage')->default(false);
        });
    }
};
