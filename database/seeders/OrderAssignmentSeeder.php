<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $workers = User::whereIn('role', ['designer', 'print_operator', 'workshop_worker'])->get();
        $managers = User::where('role', 'manager')->get();

        if ($workers->isEmpty() || $managers->isEmpty()) {
            return;
        }

        OrderAssignment::create([
            'order_id' => 1,
            'user_id' => $workers->first()->id,
            'status' => 'in_progress',
            'assigned_at' => Carbon::now()->subDays(2),
            'started_at' => Carbon::now()->subDays(1),
            'assigned_by' => $managers->first()->id,
        ]);

        OrderAssignment::create([
            'order_id' => 2,
            'user_id' => $workers->last()->id,
            'status' => 'pending',
            'assigned_at' => Carbon::now()->subDays(1),
            'assigned_by' => $managers->first()->id,
        ]);
    }
} 