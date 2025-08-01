<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Stage;
use App\Models\Project;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем стадии по порядку
        $firstStage = Stage::where('order', 1)->first();
        $secondStage = Stage::where('order', 2)->first();

        Order::create([
            'client_id' => 1,
            'project_id' => 1,
            'product_id' => 1,
            'quantity' => 10,
            'deadline' => Carbon::now()->addDays(5),
            'stage_id' => $secondStage ? $secondStage->id : ($firstStage ? $firstStage->id : 1),
            'price' => null,
        ]);

        Order::create([
            'client_id' => 1,
            'project_id' => 1,
            'product_id' => 2,
            'quantity' => 3,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);
    }
}
