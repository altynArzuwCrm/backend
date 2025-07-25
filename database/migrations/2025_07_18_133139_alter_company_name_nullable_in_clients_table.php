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
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite не поддерживает MODIFY, используем Schema
            Schema::table('clients', function (Blueprint $table) {
                $table->string('company_name')->nullable()->change();
            });
        } else {
            // MySQL поддерживает MODIFY
        DB::statement('ALTER TABLE clients MODIFY company_name VARCHAR(255) NULL;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite не поддерживает MODIFY, используем Schema
            Schema::table('clients', function (Blueprint $table) {
                $table->string('company_name')->nullable(false)->change();
            });
        } else {
            // MySQL поддерживает MODIFY
        DB::statement('ALTER TABLE clients MODIFY company_name VARCHAR(255) NOT NULL;');
        }
    }
};
