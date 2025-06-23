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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('default_designer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_workshop_required')->default(false);
            $table->enum('stage', ['design', 'print', 'workshop'])->nullable();
            $table->enum('workshop_type', ['montage', 'binding'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
