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
        // Update existing stages to remove is_active, is_initial, is_final fields
        // Since we already removed these columns in the previous migration,
        // we just need to ensure the data is clean

        // Remove the index that included is_active
        Schema::table('stages', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'order']);
        });

        // Add new index on order only
        Schema::table('stages', function (Blueprint $table) {
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the order index
        Schema::table('stages', function (Blueprint $table) {
            $table->dropIndex(['order']);
        });

        // Add back the old index (this will fail if columns don't exist, but that's expected)
        Schema::table('stages', function (Blueprint $table) {
            $table->index(['is_active', 'order']);
        });
    }
};
