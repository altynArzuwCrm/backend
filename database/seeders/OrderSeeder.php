<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Stage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $firstStage = Stage::where('order', 1)->first();

        Order::create([
            'client_id' => 3,
            'project_id' => 1,
            'product_id' => 78,
            'quantity' => 10,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 4,
            'project_id' => 1,
            'product_id' => 53,
            'quantity' => 15,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 5,
            'project_id' => 1,
            'product_id' => 86,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 7,
            'project_id' => 1,
            'product_id' => 73,
            'quantity' => 100,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 9,
            'project_id' => 1,
            'product_id' => 69,
            'quantity' => 30000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 10,
            'project_id' => 1,
            'product_id' => 59,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 11,
            'project_id' => 1,
            'product_id' => 39,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 12,
            'project_id' => 1,
            'product_id' => 15,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 15,
            'project_id' => 1,
            'product_id' => 75,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 16,
            'project_id' => 1,
            'product_id' => 91,
            'quantity' => 1000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 17,
            'project_id' => 1,
            'product_id' => 74,
            'quantity' => 400,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 18,
            'project_id' => 1,
            'product_id' => 46,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 19,
            'project_id' => 1,
            'product_id' => 45,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 20,
            'project_id' => 1,
            'product_id' => 83,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 24,
            'project_id' => 1,
            'product_id' => 55,
            'quantity' => 1000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 25,
            'project_id' => 1,
            'product_id' => 20,
            'quantity' => 50,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 26,
            'project_id' => 1,
            'product_id' => 4,
            'quantity' => 100,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 27,
            'project_id' => 1,
            'product_id' => 9,
            'quantity' => 6000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 30,
            'project_id' => 1,
            'product_id' => 26,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 31,
            'project_id' => 1,
            'product_id' => 49,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 33,
            'project_id' => 1,
            'product_id' => 14,
            'quantity' => 28000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 34,
            'project_id' => 1,
            'product_id' => 16,
            'quantity' => 400,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 35,
            'project_id' => 1,
            'product_id' => 33,
            'quantity' => 100,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 46,
            'project_id' => 1,
            'product_id' => 61,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 47,
            'project_id' => 1,
            'product_id' => 51,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 51,
            'project_id' => 1,
            'product_id' => 34,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 52,
            'project_id' => 1,
            'product_id' => 7,
            'quantity' => 2,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 55,
            'project_id' => 1,
            'product_id' => 88,
            'quantity' => 28,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 57,
            'project_id' => 1,
            'product_id' => 28,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 58,
            'project_id' => 1,
            'product_id' => 24,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 59,
            'project_id' => 1,
            'product_id' => 85,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 61,
            'project_id' => 1,
            'product_id' => 23,
            'quantity' => 2,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 62,
            'project_id' => 1,
            'product_id' => 54,
            'quantity' => 3000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 63,
            'project_id' => 1,
            'product_id' => 47,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 64,
            'project_id' => 1,
            'product_id' => 84,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 65,
            'project_id' => 1,
            'product_id' => 77,
            'quantity' => 186,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 66,
            'project_id' => 1,
            'product_id' => 17,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 67,
            'project_id' => 1,
            'product_id' => 22,
            'quantity' => 8000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 68,
            'project_id' => 1,
            'product_id' => 52,
            'quantity' => 10000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 69,
            'project_id' => 1,
            'product_id' => 89,
            'quantity' => 2,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 70,
            'project_id' => 1,
            'product_id' => 42,
            'quantity' => 1000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 71,
            'project_id' => 1,
            'product_id' => 43,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 72,
            'project_id' => 1,
            'product_id' => 36,
            'quantity' => 2,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 73,
            'project_id' => 1,
            'product_id' => 57,
            'quantity' => 5000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 81,
            'project_id' => 1,
            'product_id' => 2,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 82,
            'project_id' => 1,
            'product_id' => 27,
            'quantity' => 100,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 86,
            'project_id' => 1,
            'product_id' => 58,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 87,
            'project_id' => 1,
            'product_id' => 70,
            'quantity' => 2,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 90,
            'project_id' => 1,
            'product_id' => 44,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 91,
            'project_id' => 1,
            'product_id' => 65,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 92,
            'project_id' => 1,
            'product_id' => 60,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 93,
            'project_id' => 1,
            'product_id' => 13,
            'quantity' => 50,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 101,
            'project_id' => 1,
            'product_id' => 35,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 102,
            'project_id' => 1,
            'product_id' => 30,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 103,
            'project_id' => 1,
            'product_id' => 48,
            'quantity' => 100,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 104,
            'project_id' => 1,
            'product_id' => 8,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 105,
            'project_id' => 1,
            'product_id' => 79,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 106,
            'project_id' => 1,
            'product_id' => 32,
            'quantity' => 2000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 107,
            'project_id' => 1,
            'product_id' => 25,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 108,
            'project_id' => 1,
            'product_id' => 29,
            'quantity' => 50,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 109,
            'project_id' => 1,
            'product_id' => 68,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 110,
            'project_id' => 1,
            'product_id' => 80,
            'quantity' => 50,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 111,
            'project_id' => 1,
            'product_id' => 62,
            'quantity' => 25,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 114,
            'project_id' => 1,
            'product_id' => 71,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 115,
            'project_id' => 1,
            'product_id' => 50,
            'quantity' => 3000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 116,
            'project_id' => 1,
            'product_id' => 76,
            'quantity' => 100,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 117,
            'project_id' => 1,
            'product_id' => 41,
            'quantity' => 2,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 118,
            'project_id' => 1,
            'product_id' => 72,
            'quantity' => 300,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 124,
            'project_id' => 1,
            'product_id' => 63,
            'quantity' => 17,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 125,
            'project_id' => 1,
            'product_id' => 1,
            'quantity' => 7,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 126,
            'project_id' => 1,
            'product_id' => 3,
            'quantity' => 2,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 127,
            'project_id' => 1,
            'product_id' => 31,
            'quantity' => 3000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 128,
            'project_id' => 1,
            'product_id' => 21,
            'quantity' => 3,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 129,
            'project_id' => 1,
            'product_id' => 81,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 130,
            'project_id' => 1,
            'product_id' => 66,
            'quantity' => 100,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 131,
            'project_id' => 1,
            'product_id' => 11,
            'quantity' => 2000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 132,
            'project_id' => 1,
            'product_id' => 67,
            'quantity' => 3,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 133,
            'project_id' => 1,
            'product_id' => 64,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 134,
            'project_id' => 1,
            'product_id' => 10,
            'quantity' => 10,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 135,
            'project_id' => 1,
            'product_id' => 6,
            'quantity' => 3,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 137,
            'project_id' => 1,
            'product_id' => 19,
            'quantity' => 20,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 138,
            'project_id' => 1,
            'product_id' => 38,
            'quantity' => 2000,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 139,
            'project_id' => 1,
            'product_id' => 5,
            'quantity' => 15,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 144,
            'project_id' => 1,
            'product_id' => 18,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 145,
            'project_id' => 1,
            'product_id' => 37,
            'quantity' => 500,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 147,
            'project_id' => 1,
            'product_id' => 87,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 148,
            'project_id' => 1,
            'product_id' => 90,
            'quantity' => 20,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 150,
            'project_id' => 1,
            'product_id' => 82,
            'quantity' => 4,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 151,
            'project_id' => 1,
            'product_id' => 56,
            'quantity' => 600,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 153,
            'project_id' => 1,
            'product_id' => 12,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);

        Order::create([
            'client_id' => 154,
            'project_id' => 1,
            'product_id' => 40,
            'quantity' => 1,
            'deadline' => Carbon::now()->addDays(2),
            'stage_id' => $firstStage ? $firstStage->id : 1,
            'price' => null,
        ]);
    }
}
