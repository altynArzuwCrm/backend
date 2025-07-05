<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderAssignment>
 */
class OrderAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = OrderAssignment::class;

    public function definition(): array
    {
        $workers = User::whereIn('role', ['designer', 'printer_operator', 'workshop_worker'])->get();

        $status = $this->faker->randomElement([
            'pending', 'in_progress', 'completed', 'cancelled', 'under_review', 'approved'
        ]);

        $timestamps = [
            'started_at'    => null,
            'completed_at'  => null,
            'cancelled_at'  => null,
            'approved_at'   => null,
        ];

        $baseTime = Carbon::now()->subDays(rand(1, 10));

        match ($status) {
            'in_progress' => $timestamps['started_at'] = $baseTime->copy(),
            'completed'   => $timestamps['completed_at'] = $baseTime->copy()->addDays(1),
            'cancelled'   => $timestamps['cancelled_at'] = $baseTime->copy()->addHours(5),
            'approved'    => $timestamps['approved_at'] = $baseTime->copy()->addDays(2),
            default       => null,
        };

        return array_merge([
            'order_id'      => Order::inRandomOrder()->value('id') ?? Order::factory(),
            'user_id'       => $workers->isNotEmpty() ? $workers->random()->id : User::factory(),
            'status'        => $status,
            'assigned_at'   => $baseTime,
            'assigned_by'   => User::inRandomOrder()->value('id') ?? User::factory(),
        ], $timestamps);
    }
} 