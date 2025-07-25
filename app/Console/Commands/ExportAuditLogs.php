<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:export 
                            {--format=csv : Формат экспорта (csv, json)}
                            {--date-from= : Дата начала (YYYY-MM-DD)}
                            {--date-to= : Дата окончания (YYYY-MM-DD)}
                            {--action= : Фильтр по действию}
                            {--model= : Фильтр по модели}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Экспорт аудит-логов в файл';

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
        
        $query = AuditLog::with(['user', 'auditable' => function ($query) {
            // Загружаем product и client только для заказов
            if ($query->getModel() instanceof \App\Models\Order) {
                $query->with(['product', 'client']);
            }
        }]);

        // Применяем фильтры
        if ($this->option('date-from')) {
            $query->whereDate('created_at', '>=', $this->option('date-from'));
        }

        if ($this->option('date-to')) {
            $query->whereDate('created_at', '<=', $this->option('date-to'));
        }

        if ($this->option('action')) {
            $query->where('action', $this->option('action'));
        }

        if ($this->option('model')) {
            $query->where('auditable_type', $this->option('model'));
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        if ($logs->isEmpty()) {
            $this->error('Нет данных для экспорта.');
            return;
        }

        $format = $this->option('format');
        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        if ($format === 'csv') {
            $this->exportToCsv($logs, $filename);
        } elseif ($format === 'json') {
            $this->exportToJson($logs, $filename);
        } else {
            $this->error('Неподдерживаемый формат: ' . $format);
            return;
        }

        $this->info("Экспортировано {$logs->count()} записей в файл: {$filename}");
    }

    /**
     * Экспорт в CSV
     */
    protected function exportToCsv($logs, $filename)
    {
        $csv = fopen('php://temp', 'r+');

        // Заголовки
        fputcsv($csv, [
            'ID',
            'Дата',
            'Пользователь',
            'Действие',
            'Модель',
            'ID записи',
            'Старые значения',
            'Новые значения'
        ]);

        // Данные
        foreach ($logs as $log) {
            fputcsv($csv, [
                $log->id,
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user ? $log->user->name : 'Система',
                $log->action_name,
                $log->model_name,
                $log->auditable_id,
                json_encode($log->old_values, JSON_UNESCAPED_UNICODE),
                json_encode($log->new_values, JSON_UNESCAPED_UNICODE)
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        Storage::disk('local')->put('exports/' . $filename, $content);
    }

    /**
     * Экспорт в JSON
     */
    protected function exportToJson($logs, $filename)
    {
        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'username' => $log->user->username,
                ] : null,
                'action' => $log->action,
                'action_name' => $log->action_name,
                'model' => $log->model_name,
                'auditable_id' => $log->auditable_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ];
        });

        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        Storage::disk('local')->put('exports/' . $filename, $content);
    }
} 