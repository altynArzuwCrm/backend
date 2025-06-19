<?php

namespace Database\Seeders;

use App\Models\Funnel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FunnelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $funnels = [
            [
                'name' => 'Универсальная воронка для онлайн-заказов (B2B и B2C вместе)',
                'description' => 'Стадия,Описание',
            ],
            [
                'name' => 'Воронка онлайн-заказов для B2C',
                'description' => 'Стадия,Описание',
            ],
            [
                'name' => 'Воронка онлайн-заказов для B2B',
                'description' => 'Стадия,Описание',
            ],
        ];

        foreach ($funnels as $funnel) {
            Funnel::create([
                'name' => $funnel['name'],
                'description' => $funnel['description'],
            ]);
        }
    }
}
