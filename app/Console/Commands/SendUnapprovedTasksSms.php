<?php

namespace App\Console\Commands;

use App\Jobs\SendSmsNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendUnapprovedTasksSms extends Command
{
    protected $signature = 'tasks:check-unapproved';

    protected $description = 'Dispatch SMS reminders for users with unapproved tasks';

    public function handle(): int
    {
        $lock = Cache::lock('unapproved-tasks-sms', 900);

        if (!$lock->get()) {
            $this->info('Another unapproved tasks run is still in progress');
            return self::SUCCESS;
        }

        try {
            $users = User::query()
                ->whereNotNull('phone')
                ->withCount(['assignments as unapproved_tasks_count' => function ($query) {
                    $query->where('status', '!=', 'approved');
                }])
                ->having('unapproved_tasks_count', '>', 0)
                ->get();

            foreach ($users as $user) {
                $message = "You have {$user->unapproved_tasks_count} unapproved tasks. Please check them.";
                SendSmsNotification::dispatch($user->id, $message);
            }

            $this->info('SMS jobs dispatched');
            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
