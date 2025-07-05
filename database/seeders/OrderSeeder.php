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
            'project_id' => 1,
            'product_id' => 1,
            'quantity' => 10,
            'manager_id' => 2,
            'deadline' => Carbon::now()->addDays(5),
            'stage' => 'print',
            'price' => null,
        ]);

        Order::create([
            'project_id' => 1,
            'product_id' => 2,
            'quantity' => 3,
            'manager_id' => null,
            'deadline' => Carbon::now()->addDays(2),
            'stage' => 'design',
            'price' => null,
        ]);
    }
}
