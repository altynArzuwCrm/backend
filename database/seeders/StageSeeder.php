<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            ['name' => 'Новый заказ', 'order' => 1, 'funnel_id' => 1],
            ['name' => 'Обработка (подтверждение/оплата)', 'order' => 2, 'funnel_id' => 1],
            ['name' => 'Доставка', 'order' => 3, 'funnel_id' => 1],
            ['name' => 'Доставлен', 'order' => 4, 'funnel_id' => 1],
            ['name' => 'Возврат / Отмена', 'order' => 5, 'funnel_id' => 1],
            ['name' => 'Новый заказ', 'order' => 6, 'funnel_id' => 2],
            ['name' => 'Подтвержден (оплата/проверка)', 'order' => 7, 'funnel_id' => 2],
            ['name' => 'Доставка', 'order' => 8, 'funnel_id' => 2],
            ['name' => 'Доставлен', 'order' => 9, 'funnel_id' => 2],
            ['name' => 'Возврат / Отмена', 'order' => 10, 'funnel_id' => 2],
            ['name' => 'Новый заказ', 'order' => 6, 'funnel_id' => 3],
            ['name' => 'Согласование (внутреннее утверждение)', 'order' => 7, 'funnel_id' => 3],
            ['name' => 'Отгрузка', 'order' => 8, 'funnel_id' => 3],
            ['name' => 'Завершён', 'order' => 9, 'funnel_id' => 3],
            ['name' => 'Возврат / Отмена', 'order' => 10, 'funnel_id' => 3],
        ];

        foreach ($stages as $stage) {
            Stage::create($stage);
        }
    }
}
