<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public string $message;

    public function __construct(int $userId, string $message)
    {
        $this->userId = $userId;
        $this->message = $message;
    }

    public function handle(SmsService $smsService): void
    {
        $user = User::find($this->userId);

        if (!$user || !$user->phone) {
            Log::warning('SMS skipped', ['user_id' => $this->userId]);
            return;
        }

        $sent = $smsService->send($user->phone, $this->message);

        if ($sent) {
            Log::info('SMS delivered', ['user_id' => $this->userId]);
            return;
        }

        Log::error('SMS delivery failed', ['user_id' => $this->userId]);
    }
}
