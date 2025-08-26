<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert default stages that match current system
        $stages = [
            [
                'name' => 'draft',
                'display_name' => 'Черновик',
                'description' => 'Начальный этап заказа',
                'order' => 1,
                'color' => '#64748b'
            ],
            [
                'name' => 'design',
                'display_name' => 'Дизайн',
                'description' => 'Создание дизайна',
                'order' => 2,
                'color' => '#8b5cf6'
            ],
            [
                'name' => 'print',
                'display_name' => 'Печать',
                'description' => 'Печать продукции',
                'order' => 3,
                'color' => '#06b6d4'
            ],
            [
                'name' => 'engraving',
                'display_name' => 'Гравировка',
                'description' => 'Гравировка на продукции',
                'order' => 4,
                'color' => '#f59e0b'
            ],
            [
                'name' => 'workshop',
                'display_name' => 'Цех',
                'description' => 'Производство в цехе',
                'order' => 5,
                'color' => '#10b981'
            ],
            [
                'name' => 'final',
                'display_name' => 'Финал',
                'description' => 'Финальная обработка',
                'order' => 6,
                'color' => '#3b82f6'
            ],
            [
                'name' => 'completed',
                'display_name' => 'Завершен',
                'description' => 'Заказ выполнен',
                'order' => 7,
                'color' => '#22c55e'
            ],
            [
                'name' => 'cancelled',
                'display_name' => 'Отменен',
                'description' => 'Заказ отменен',
                'order' => 8,
                'color' => '#ef4444'
            ],
        ];

        foreach ($stages as $stage) {
            DB::table('stages')->updateOrInsert(
                ['name' => $stage['name']],
                $stage
            );
        }
    }
}
