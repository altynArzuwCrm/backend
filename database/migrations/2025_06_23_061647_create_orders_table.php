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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['draft', 'design', 'print', 'workshop', 'final', 'archived'])->default('draft');
            $table->enum('status', ['completed', 'cancelled']);
            $table->timestamp('deadline')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('payment_amount', 10, 2)->default(0);
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
