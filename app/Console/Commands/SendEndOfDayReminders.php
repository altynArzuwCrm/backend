<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Order;
use App\Notifications\EndOfDayReminder;
use Carbon\Carbon;

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

        // Получаем статистику заказов
        // Исключаем завершенные и отмененные заказы через связь со стадиями
        $pendingOrders = Order::whereHas('stage', function ($query) {
            $query->whereNotIn('name', ['completed', 'cancelled']);
        })
            ->where('is_archived', false)
            ->count();

        $overdueOrders = Order::where('deadline', '<', now())
            ->whereHas('stage', function ($query) {
                $query->whereNotIn('name', ['completed', 'cancelled']);
            })
            ->where('is_archived', false)
            ->count();

        // Получаем всех активных пользователей
        $users = User::where('is_active', true)->get();

        $sentCount = 0;
        foreach ($users as $user) {
            try {
                // Определяем роль пользователя
                $userRoles = $user->roles->pluck('name')->toArray();
                $isManagerOrAdmin = in_array('admin', $userRoles) || in_array('manager', $userRoles);

                if ($isManagerOrAdmin) {
                    // Для менеджеров и администраторов - общая статистика
                    $userPendingOrders = $pendingOrders;
                    $userOverdueOrders = $overdueOrders;
                } else {
                    // Для сотрудников - только их назначенные заказы
                    $userPendingOrders = Order::whereHas('assignments', function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->where('status', '!=', 'completed');
                    })
                        ->whereHas('stage', function ($query) {
                            $query->whereNotIn('name', ['completed', 'cancelled']);
                        })
                        ->where('is_archived', false)
                        ->count();

                    $userOverdueOrders = Order::whereHas('assignments', function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->where('status', '!=', 'completed');
                    })
                        ->where('deadline', '<', now())
                        ->whereHas('stage', function ($query) {
                            $query->whereNotIn('name', ['completed', 'cancelled']);
                        })
                        ->where('is_archived', false)
                        ->count();
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
