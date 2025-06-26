<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItemAssignment>
 */
class OrderItemAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = OrderItemAssignment::class;

    public function definition(): array
    {
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
            'order_item_id' => OrderItem::inRandomOrder()->value('id') ?? OrderItem::factory(),
            'user_id'       => User::inRandomOrder()->value('id') ?? User::factory(),
            'status'        => $status,
            'assigned_at'   => $baseTime,
            'assigned_by'   => User::inRandomOrder()->value('id') ?? User::factory(),
        ], $timestamps);
    }
}
