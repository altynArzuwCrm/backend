<?php

namespace Database\Seeders;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = [
            [
                'title' => 'Order #1',
                'client_id' => 1,
                'status' => 'completed',
                'stage' => 'draft',
                'deadline' => Carbon::now()->addDays(5),
                'price' => 1500.50,
                'payment_amount' => 0,
                'finalized_at' => null,
            ],
            [
                'title' => 'Order #2',
                'client_id' => 3,
                'status' => 'cancelled',
                'stage' => 'draft',
                'deadline' => Carbon::now()->addDays(10),
                'price' => 2500.00,
                'payment_amount' => 1200.00,
                'finalized_at' => null,
            ],
            [
                'title' => 'Order #3',
                'client_id' => 2,
                'status' => 'completed',
                'stage' => 'design',
                'deadline' => Carbon::now()->subDays(2),
                'price' => 3500.75,
                'payment_amount' => 3500.75,
                'finalized_at' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($orders as $order) {
            Order::firstOrCreate(
                ['title' => $order['title']],
                $order
            );
        }
    }
}
