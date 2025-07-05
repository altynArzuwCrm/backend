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
        Schema::table('orders', function (Blueprint $table) {
            // Rename the column
            $table->renameColumn('order_id', 'project_id');
            
            // Add new foreign key constraint
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['project_id']);
            
            // Rename the column back
            $table->renameColumn('project_id', 'order_id');
        });
    }
};
