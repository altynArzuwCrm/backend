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
            // Добавляем новое поле stage_id
            $table->foreignId('stage_id')->nullable()->after('product_id')->constrained()->nullOnDelete();

            // Создаем индекс для быстрого поиска
            $table->index('stage_id');
        });

        // Мигрируем данные из старого поля stage в новое stage_id
        $this->migrateStageData();

        // Удаляем старое поле stage
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Восстанавливаем старое поле stage
            $table->enum('stage', ['draft', 'design', 'print', 'workshop', 'final', 'completed', 'cancelled'])->default('draft')->after('product_id');
        });

        // Мигрируем данные обратно
        $this->rollbackStageData();

        Schema::table('orders', function (Blueprint $table) {
            // Удаляем новое поле stage_id
            $table->dropForeign(['stage_id']);
            $table->dropIndex(['stage_id']);
            $table->dropColumn('stage_id');
        });
    }

    /**
     * Мигрируем данные из stage в stage_id
     */
    private function migrateStageData(): void
    {
        $stageMapping = [
            'draft' => 'draft',
            'design' => 'design',
            'print' => 'print',
            'workshop' => 'workshop',
            'final' => 'final',
            'completed' => 'completed',
            'cancelled' => 'cancelled'
        ];

        foreach ($stageMapping as $oldStage => $newStageName) {
            $stage = \App\Models\Stage::where('name', $newStageName)->first();
            if ($stage) {
                \DB::table('orders')
                    ->where('stage', $oldStage)
                    ->update(['stage_id' => $stage->id]);
            }
        }

        // Обрабатываем специальные случаи (например, begowka)
        $unknownStages = \DB::table('orders')
            ->whereNull('stage_id')
            ->whereNotNull('stage')
            ->distinct()
            ->pluck('stage');

        foreach ($unknownStages as $unknownStage) {
            // Создаем стадию если её нет
            $stage = \App\Models\Stage::firstOrCreate(
                ['name' => $unknownStage],
                [
                    'display_name' => ucfirst($unknownStage),
                    'order' => 999, // Временный порядок
                    'is_active' => true,
                    'color' => '#6b7280' // Дефолтный цвет
                ]
            );

            \DB::table('orders')
                ->where('stage', $unknownStage)
                ->update(['stage_id' => $stage->id]);
        }
    }

    /**
     * Откатываем данные обратно в stage
     */
    private function rollbackStageData(): void
    {
        $stages = \App\Models\Stage::all();

        foreach ($stages as $stage) {
            \DB::table('orders')
                ->where('stage_id', $stage->id)
                ->update(['stage' => $stage->name]);
        }
    }
};
