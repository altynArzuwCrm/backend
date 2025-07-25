<?php

namespace Database\Seeders;

use App\Models\Order;
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
        Order::create([
            'client_id' => 1,
            'project_id' => 1,
            'product_id' => 1,
            'quantity' => 10,
            'deadline' => Carbon::now()->addDays(5),
            'stage' => 'print',
            'price' => null,
        ]);

        Order::create([
            'client_id' => 1,
            'project_id' => 1,
            'product_id' => 2,
            'quantity' => 3,
            'deadline' => Carbon::now()->addDays(2),
            'stage' => 'design',
            'price' => null,
        ]);
    }
}
