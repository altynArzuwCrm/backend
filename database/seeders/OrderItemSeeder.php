<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        OrderItem::create([
            'order_id' => 1,
            'product_id' => 1,
            'reason_id' => null,
            'quantity' => 10,
            'manager_id' => 2,
            'deadline' => Carbon::now()->addDays(5),
            'status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => 1,
            'product_id' => 2,
            'reason_id' => 1,
            'quantity' => 3,
            'manager_id' => null,
            'deadline' => Carbon::now()->addDays(2),
            'status' => 'in_progress',
        ]);
    }
}
