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
        // Check if stage_id column already exists
        if (!Schema::hasColumn('orders', 'stage_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // Добавляем новое поле stage_id
                $table->foreignId('stage_id')->nullable()->after('product_id')->constrained()->nullOnDelete();

                // Создаем индекс для быстрого поиска
                $table->index('stage_id');
            });
        }

        // Set default stage for existing orders
        $this->setDefaultStageForOrders();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Удаляем новое поле stage_id
            $table->dropForeign(['stage_id']);
            $table->dropIndex(['stage_id']);
            $table->dropColumn('stage_id');
        });
    }

    /**
     * Устанавливаем дефолтную стадию для существующих заказов
     */
    private function setDefaultStageForOrders(): void
    {
        $draftStage = \App\Models\Stage::where('name', 'draft')->first();
        if ($draftStage) {
            \DB::table('orders')
                ->whereNull('stage_id')
                ->update(['stage_id' => $draftStage->id]);
        }
    }
};
