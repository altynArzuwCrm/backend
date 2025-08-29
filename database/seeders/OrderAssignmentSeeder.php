<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderAssignment;
use App\Models\User;
use Carbon\Carbon;

class OrderAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::with('roles')->get();

        // Функция для получения role_type пользователя
        $getUserRoleType = function ($user) {
            if (!$user) return null;

            $roles = $user->roles->pluck('name')->toArray();

            // Приоритет ролей для назначения
            if (in_array('designer', $roles)) return 'designer';
            if (in_array('print_operator', $roles)) return 'print_operator';
            if (in_array('workshop_worker', $roles)) return 'workshop_worker';
            if (in_array('engraving_operator', $roles)) return 'engraving_operator';
            if (in_array('bukhgalter', $roles)) return 'bukhgalter';

            // Если нет специализированных ролей, возвращаем первую роль
            return $roles[0] ?? null;
        };

        // Функция для создания назначения с role_type
        $createAssignment = function ($orderId, $userName, $assignedByName, $status, $assignedAt, $startedAt = null) use ($users, $getUserRoleType) {
            $user = $users->where('name', $userName)->first();
            $assignedBy = $users->where('name', $assignedByName)->first();

            if (!$user) {
                echo "Warning: User '{$userName}' not found\n";
                return;
            }

            if (!$assignedBy) {
                echo "Warning: Assigned by user '{$assignedByName}' not found\n";
                return;
            }

            OrderAssignment::create([
                'order_id' => $orderId,
                'user_id' => $user->id,
                'assigned_by' => $assignedBy->id,
                'role_type' => $getUserRoleType($user),
                'status' => $status,
                'assigned_at' => Carbon::parse($assignedAt),
                'started_at' => $startedAt ? Carbon::parse($startedAt) : null,
            ]);
        };

        // Создаем все назначения
        $createAssignment(1, 'Диана', 'Нязли', 'in_progress', '2025-03-17', '2025-03-17');
        $createAssignment(1, 'Ширали', 'Нязли', 'in_progress', '2025-03-17', '2025-03-17');
        $createAssignment(2, 'Алексей', 'Нязли', 'in_progress', '2025-03-28', '2025-03-28');
        $createAssignment(3, 'Алексей', 'Нязли', 'in_progress', '2025-04-10', '2025-04-10');
        $createAssignment(4, 'Вика', 'Неля', 'in_progress', '2025-05-05', '2025-05-05');
        $createAssignment(5, 'Диана', 'Неля', 'in_progress', '2025-05-14', '2025-05-14');
        $createAssignment(6, 'Вика', 'Неля', 'in_progress', '2025-05-27', '2025-05-27');
        $createAssignment(7, 'Ширали', 'Елена', 'in_progress', '2025-06-04', '2025-06-04');
        $createAssignment(8, 'Илья', 'Нязли', 'in_progress', '2025-06-11', '2025-06-11');
        $createAssignment(9, 'Вика', 'Нязли', 'in_progress', '2025-06-13', '2025-06-13');
        $createAssignment(10, 'Диана', 'Неля', 'in_progress', '2025-06-18', '2025-06-18');
        $createAssignment(11, 'Диана', 'Нязли', 'in_progress', '2025-06-20', '2025-06-20');
        $createAssignment(12, 'Вика', 'Нязли', 'in_progress', '2025-06-27', '2025-06-27');
        $createAssignment(13, 'Вика', 'Нязли', 'in_progress', '2025-07-01', '2025-07-01');
        $createAssignment(14, 'Вика', 'Дженнет', 'in_progress', '2025-07-05', '2025-07-05');
        $createAssignment(15, 'Диана', 'Нязли', 'in_progress', '2025-07-07', '2025-07-07');
        $createAssignment(16, 'Максим', 'Нязли', 'in_progress', '2025-07-07', '2025-07-07');
        $createAssignment(17, 'Максим', 'Нязли', 'in_progress', '2025-07-08', '2025-07-08');
        $createAssignment(18, 'Диана', 'Неля', 'in_progress', '2025-07-11', '2025-07-11');
        $createAssignment(19, 'Вика', 'Дженнет', 'in_progress', '2025-07-12', '2025-07-12');
        $createAssignment(20, 'Илья', 'Нязли', 'in_progress', '2025-07-14', '2025-07-14');
        $createAssignment(21, 'Ширали', 'Нязли', 'in_progress', '2025-07-15', '2025-07-15');
        $createAssignment(22, 'Вика', 'Нязли', 'in_progress', '2025-07-16', '2025-07-16');
        $createAssignment(23, 'Илья', 'Нязли', 'in_progress', '2025-07-17', '2025-07-17');
        $createAssignment(24, 'Вика', 'Нязли', 'in_progress', '2025-07-17', '2025-07-17');
        $createAssignment(25, 'Нязли', 'Нязли', 'in_progress', '2025-07-17', '2025-07-17');
        $createAssignment(26, 'Илья', 'Нязли', 'in_progress', '2025-07-18', '2025-07-18');
        $createAssignment(27, 'Максим', 'Нязли', 'in_progress', '2025-07-18', '2025-07-18');
        $createAssignment(28, 'Ширали', 'Дженнет', 'in_progress', '2025-07-18', '2025-07-18');
        $createAssignment(30, 'Вика', 'Нязли', 'in_progress', '2025-07-21', '2025-07-21');
        $createAssignment(31, 'Вика', 'Нязли', 'in_progress', '2025-07-21', '2025-07-21');
        $createAssignment(32, 'Илья', 'Нязли', 'in_progress', '2025-07-21', '2025-07-21');
        $createAssignment(34, 'Диана', 'Неля', 'in_progress', '2025-07-22', '2025-07-22');
        $createAssignment(35, 'Вика', 'Нязли', 'in_progress', '2025-07-22', '2025-07-22');
        $createAssignment(36, 'Диана', 'Нязли', 'in_progress', '2025-07-22', '2025-07-22');
        $createAssignment(37, 'Ширали', 'Нязли', 'in_progress', '2025-07-22', '2025-07-22');
        $createAssignment(38, 'Ширали', 'Неля', 'in_progress', '2025-07-09', '2025-07-09');
        $createAssignment(40, 'Ширали', 'Нязли', 'in_progress', '2025-07-23', '2025-07-23');
        $createAssignment(41, 'Диана', 'Нязли', 'in_progress', '2025-07-23', '2025-07-23');
        $createAssignment(42, 'Ширали', 'Нязли', 'in_progress', '2025-07-23', '2025-07-23');
        $createAssignment(43, 'Илья', 'Нязли', 'in_progress', '2025-07-23', '2025-07-23');
        $createAssignment(44, 'Максим', 'Нязли', 'in_progress', '2025-07-24', '2025-07-24');
        $createAssignment(46, 'Илья', 'Нязли', 'in_progress', '2025-07-24', '2025-07-24');
        $createAssignment(47, 'Ширали', 'Нязли', 'in_progress', '2025-07-24', '2025-07-24');
        $createAssignment(48, 'Вика', 'Нязли', 'in_progress', '2025-07-24', '2025-07-24');
        $createAssignment(49, 'Вика', 'Елена', 'in_progress', '2025-07-24', '2025-07-24');
        $createAssignment(51, 'Диана', 'Нязли', 'in_progress', '2025-07-26', '2025-07-26');
        $createAssignment(53, 'Вика', 'Нязли', 'in_progress', '2025-07-28', '2025-07-28');
        $createAssignment(54, 'Максим', 'Нязли', 'in_progress', '2025-07-28', '2025-07-28');
        $createAssignment(55, 'Диана', 'Неля', 'in_progress', '2025-07-28', '2025-07-28');
        $createAssignment(59, 'Вика', 'Нязли', 'in_progress', '2025-07-28', '2025-07-28');
        $createAssignment(60, 'Ширали', 'Нязли', 'in_progress', '2025-07-28', '2025-07-28');
        $createAssignment(62, 'Диана', 'Неля', 'in_progress', '2025-07-29', '2025-07-29');
        $createAssignment(64, 'Максим', 'Дженнет', 'in_progress', '2025-07-29', '2025-07-29');
        $createAssignment(66, 'Вика', 'Нязли', 'in_progress', '2025-07-29', '2025-07-29');
        $createAssignment(67, 'Диана', 'Нязли', 'in_progress', '2025-07-29', '2025-07-29');
        $createAssignment(68, 'Вика', 'Нязли', 'in_progress', '2025-07-28', '2025-07-28');
        $createAssignment(69, 'Диана', 'Неля', 'in_progress', '2025-07-29', '2025-07-29');
        $createAssignment(70, 'Максим', 'Елена', 'in_progress', '2025-07-29', '2025-07-29');
        $createAssignment(71, 'Максим', 'Нязли', 'in_progress', '2025-07-29', '2025-07-29');
        $createAssignment(72, 'Вика', 'Нязли', 'in_progress', '2025-07-30', '2025-07-30');
        $createAssignment(74, 'Максим', 'Нязли', 'in_progress', '2025-07-30', '2025-07-30');
        $createAssignment(75, 'Диана', 'Неля', 'in_progress', '2025-07-30', '2025-07-30');
        $createAssignment(80, 'Ширали', 'Елена', 'in_progress', '2025-07-31', '2025-07-31');
        $createAssignment(81, 'Вика', 'Нязли', 'in_progress', '2025-07-31', '2025-07-31');
        $createAssignment(82, 'Вика', 'Нязли', 'in_progress', '2025-07-31', '2025-07-31');
        $createAssignment(83, 'Вика', 'Нязли', 'in_progress', '2025-07-31', '2025-07-31');
        $createAssignment(84, 'Ширали', 'Нязли', 'in_progress', '2025-07-31', '2025-07-31');
        $createAssignment(85, 'Максим', 'Неля', 'in_progress', '2025-07-31', '2025-07-31');
        $createAssignment(86, 'Ширали', 'Неля', 'in_progress', '2025-07-31', '2025-07-31');
        $createAssignment(87, 'Илья', 'Дженнет', 'in_progress', '2025-08-01', '2025-08-01');
        $createAssignment(90, 'Ширали', 'Дженнет', 'in_progress', '2025-08-01', '2025-08-01');

        // Добавляем назначения для пользователей с разными ролями для тестирования
        $createAssignment(94, 'Куват', 'Нязли', 'in_progress', '2025-08-02', '2025-08-02');
        $createAssignment(88, 'Ата ага', 'Неля', 'in_progress', '2025-08-02', '2025-08-02');
        $createAssignment(89, 'Николай', 'Елена', 'in_progress', '2025-08-02', '2025-08-02');
        $createAssignment(29, 'Джейхун', 'Дженнет', 'in_progress', '2025-08-02', '2025-08-02');

        echo "Order assignments created successfully with role_type!\n";
    }
}
