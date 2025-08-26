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
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // internal name like 'draft', 'design'
            $table->string('display_name'); // display name like 'Черновик', 'Дизайн'
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // order in workflow
            $table->string('color', 7)->default('#6366f1'); // hex color for UI
            $table->timestamps();
            $table->softDeletes();

            $table->index('order'); // индекс только для поля order
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
