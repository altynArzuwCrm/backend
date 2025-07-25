<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use App\Models\Project;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Обновляем заказы, у которых есть project_id, но нет client_id
        $orders = Order::whereNotNull('project_id')->whereNull('client_id')->get();
        
        foreach ($orders as $order) {
            $project = Project::find($order->project_id);
            if ($project) {
                $order->update(['client_id' => $project->client_id]);
            }
        }
        
        // Для заказов без project_id устанавливаем client_id = 1 (или первый доступный клиент)
        $firstClientId = \App\Models\Client::first()->id ?? 1;
        Order::whereNull('client_id')->update(['client_id' => $firstClientId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // В down можно оставить пустым, так как это обновление данных
    }
};
