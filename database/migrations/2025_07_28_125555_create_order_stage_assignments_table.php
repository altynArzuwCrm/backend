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
        Schema::create('order_stage_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_assigned')->default(true);
            $table->timestamps();

            $table->unique(['order_assignment_id', 'stage_id']);
        });

        // No migration needed since we're removing the old stage fields
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_stage_assignments');
    }
};
