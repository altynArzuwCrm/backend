<?php

use App\Enums\ReasonStatus;
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
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('deadline')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('stage', ['draft', 'design', 'print', 'workshop', 'final', 'completed', 'cancelled'])->default('draft');
            $table->text('reason')->nullable();
            $table->enum('reason_status', ReasonStatus::values())->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('is_archived')->default(false);
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
