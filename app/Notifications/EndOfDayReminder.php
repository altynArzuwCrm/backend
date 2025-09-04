<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EndOfDayReminder extends Notification
{
    use Queueable;

    public $date;
    public $pendingOrders;
    public $overdueOrders;
    public $isManagerOrAdmin;

    public function __construct($date, $pendingOrders = 0, $overdueOrders = 0, $isManagerOrAdmin = false)
    {
        $this->date = $date;
        $this->pendingOrders = $pendingOrders;
        $this->overdueOrders = $overdueOrders;
        $this->isManagerOrAdmin = $isManagerOrAdmin;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $message = "Напоминание о конце рабочего дня - " . $this->date;

        if ($this->pendingOrders > 0 || $this->overdueOrders > 0) {
            $stats = [];
            if ($this->pendingOrders > 0) {
                if ($this->isManagerOrAdmin) {
                    $stats[] = "В работе: {$this->pendingOrders}";
                } else {
                    $stats[] = "Ожидают выполнения: {$this->pendingOrders}";
                }
            }
            if ($this->overdueOrders > 0) {
                $stats[] = "Просроченные: {$this->overdueOrders}";
            }

            if (!empty($stats)) {
                $message .= "\n\n" . implode(", ", $stats);
            }
        }

        return [
            'title' => 'Напоминание о конце рабочего дня',
            'message' => $message,
            'date' => $this->date,
            'pending_orders' => $this->pendingOrders,
            'overdue_orders' => $this->overdueOrders,
            'is_manager_or_admin' => $this->isManagerOrAdmin,
            'icon' => 'end_of_day',
            'type' => 'daily_reminder',
            'created_at' => now(),
        ];
    }
}
