<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем таблицу ролей
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Создаем промежуточную таблицу user_roles
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // Добавляем базовые роли
        $roles = [
            ['name' => 'admin', 'display_name' => 'Администратор'],
            ['name' => 'manager', 'display_name' => 'Менеджер'],
            ['name' => 'designer', 'display_name' => 'Дизайнер'],
            ['name' => 'print_operator', 'display_name' => 'Оператор печати'],
            ['name' => 'workshop_worker', 'display_name' => 'Работник цеха'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert($role);
        }

        // Мигрируем существующие роли пользователей
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $roleId = DB::table('roles')->where('name', $user->role)->value('id');
            if ($roleId) {
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Восстанавливаем поле role в users
        // Schema::table('users', function (Blueprint $table) {
        //     if (!Schema::hasColumn('users', 'role')) {
        //         $table->enum('role', ['admin', 'manager', 'designer', 'print_operator', 'workshop_worker'])->after('is_active');
        //     }
        // });

        // Восстанавливаем роли пользователей
        // $userRoles = DB::table('user_roles')->where('is_primary', true)->get();
        // foreach ($userRoles as $userRole) {
        //     $roleName = DB::table('roles')->where('id', $userRole->role_id)->value('name');
        //     DB::table('users')->where('id', $userRole->user_id)->update(['role' => $roleName]);
        // }

        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
