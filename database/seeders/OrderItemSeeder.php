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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = Product::first();
        $order = Order::first();
        $printer = User::whereHas('roles', fn($q) => $q->where('name', 'Оператор печати'))->first();
        $workshopWorker = User::whereHas('roles', fn($q) => $q->where('name', 'Сотрудник цеха'))->first();
        $designer = User::whereHas('roles', fn($q) => $q->where('name', 'Дизайнер'))->first();
        $manager = User::whereHas('roles', fn($q) => $q->where('name', 'Менеджер'))->first();

        OrderItem::firstOrCreate(
            [
                'order_id' => $order->id,
                'product_id' => $product->id,
            ],
            [
                'quantity' => 10,
                'designer_id' => $designer?->id,
                'printer_id' => $printer?->id,
                'workshop_worker_id' => $workshopWorker?->id,
                'manager_id' => $manager?->id,
                'individual_deadline' => now()->addDays(5),
                'status' => 'ожидание',
                'assigned_at' => Carbon::now()->subDays(10),
                'started_at' => Carbon::now()->subDays(9),
                'completed_at' => Carbon::now()->subDays(1),
            ]
        );

    }
}
