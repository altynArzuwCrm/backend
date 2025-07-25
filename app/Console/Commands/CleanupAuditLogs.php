<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;

class CleanupAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup {--days=90 : Количество дней для хранения логов}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка старых аудит-логов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Проверяем права доступа (в консоли используем системного пользователя)
        $systemUser = User::where('role', 'admin')->first();
        if (!$systemUser) {
            $this->error('Не найден пользователь с правами администратора.');
            return;
        }
        
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $count = AuditLog::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('Нет старых логов для удаления.');
            return;
        }

        if ($this->confirm("Удалить {$count} записей аудит-логов старше {$days} дней?")) {
            $deleted = AuditLog::where('created_at', '<', $cutoffDate)->delete();
            $this->info("Удалено {$deleted} записей аудит-логов.");
        } else {
            $this->info('Операция отменена.');
        }
    }
} 