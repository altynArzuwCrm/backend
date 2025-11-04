<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Order;
use App\Notifications\EndOfDayReminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SendEndOfDayReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:end-of-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send end of day reminder notifications to all active users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending end of day reminders...');

        // Получаем текущую дату в формате для Ашхабада
        $ashgabatDate = Carbon::now('Asia/Ashgabat');
        $formattedDate = $ashgabatDate->format('d.m.Y');

        // Проверяем, что это рабочий день (понедельник-пятница)
        if ($ashgabatDate->isWeekend()) {
            $this->info('Today is weekend, skipping notifications.');
            return;
        }

        // Оптимизация: используем JOIN вместо whereHas для лучшей производительности
        // Получаем статистику заказов через JOIN
        $pendingOrders = DB::table('orders')
            ->join('stages', 'orders.stage_id', '=', 'stages.id')
            ->whereNotIn('stages.name', ['completed', 'cancelled'])
            ->where('orders.is_archived', false)
            ->count();

        $overdueOrders = DB::table('orders')
            ->join('stages', 'orders.stage_id', '=', 'stages.id')
            ->where('orders.deadline', '<', now())
            ->whereNotIn('stages.name', ['completed', 'cancelled'])
            ->where('orders.is_archived', false)
            ->count();

        // Получаем всех активных пользователей с предзагруженными ролями (избегаем N+1)
        $users = User::with('roles')->where('is_active', true)->get();

        // Предзагружаем статистику для всех сотрудников одним запросом
        $employeeStats = [];
        $employeeIds = $users->filter(function ($user) {
            $roles = $user->roles->pluck('name')->toArray();
            return !in_array('admin', $roles) && !in_array('manager', $roles);
        })->pluck('id');

        if ($employeeIds->isNotEmpty()) {
            // Оптимизация: используем один запрос для всех сотрудников
            $pendingStats = DB::table('orders')
                ->join('order_assignments', 'orders.id', '=', 'order_assignments.order_id')
                ->join('stages', 'orders.stage_id', '=', 'stages.id')
                ->whereIn('order_assignments.user_id', $employeeIds)
                ->where('order_assignments.status', '!=', 'completed')
                ->whereNotIn('stages.name', ['completed', 'cancelled'])
                ->where('orders.is_archived', false)
                ->select('order_assignments.user_id', DB::raw('count(*) as count'))
                ->groupBy('order_assignments.user_id')
                ->pluck('count', 'user_id');

            $overdueStats = DB::table('orders')
                ->join('order_assignments', 'orders.id', '=', 'order_assignments.order_id')
                ->join('stages', 'orders.stage_id', '=', 'stages.id')
                ->whereIn('order_assignments.user_id', $employeeIds)
                ->where('order_assignments.status', '!=', 'completed')
                ->where('orders.deadline', '<', now())
                ->whereNotIn('stages.name', ['completed', 'cancelled'])
                ->where('orders.is_archived', false)
                ->select('order_assignments.user_id', DB::raw('count(*) as count'))
                ->groupBy('order_assignments.user_id')
                ->pluck('count', 'user_id');

            foreach ($employeeIds as $userId) {
                $employeeStats[$userId] = [
                    'pending' => $pendingStats->get($userId, 0),
                    'overdue' => $overdueStats->get($userId, 0)
                ];
            }
        }

        $sentCount = 0;
        foreach ($users as $user) {
            try {
                // Определяем роль пользователя (используем предзагруженные роли)
                $userRoles = $user->roles->pluck('name')->toArray();
                $isManagerOrAdmin = in_array('admin', $userRoles) || in_array('manager', $userRoles);

                if ($isManagerOrAdmin) {
                    // Для менеджеров и администраторов - общая статистика
                    $userPendingOrders = $pendingOrders;
                    $userOverdueOrders = $overdueOrders;
                } else {
                    // Для сотрудников - используем предвычисленную статистику
                    $userPendingOrders = $employeeStats[$user->id]['pending'] ?? 0;
                    $userOverdueOrders = $employeeStats[$user->id]['overdue'] ?? 0;
                }

                $user->notify(new EndOfDayReminder(
                    $formattedDate,
                    $userPendingOrders,
                    $userOverdueOrders,
                    $isManagerOrAdmin
                ));
                $sentCount++;
            } catch (\Exception $e) {
                $this->error("Failed to send notification to user {$user->id}: " . $e->getMessage());
            }
        }

        $this->info("Sent {$sentCount} end of day reminders for {$formattedDate}");
        $this->info("Pending orders: {$pendingOrders}, Overdue orders: {$overdueOrders}");
    }
}
