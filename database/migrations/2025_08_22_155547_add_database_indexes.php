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
        // Функция для безопасного добавления индекса
        $addIndexIfNotExists = function ($tableName, $indexName, $columns, $type = 'index') {
            $indexes = DB::select("SHOW INDEX FROM {$tableName} WHERE Key_name = ?", [$indexName]);
            if (empty($indexes)) {
                if ($type === 'unique') {
                    Schema::table($tableName, function (Blueprint $table) use ($columns) {
                        $table->unique($columns);
                    });
                } else {
                    Schema::table($tableName, function (Blueprint $table) use ($columns) {
                        $table->index($columns);
                    });
                }
            }
        };

        // Индексы для таблицы orders (проверяем только те, которых может не быть)
        $addIndexIfNotExists('orders', 'orders_project_id_index', 'project_id');
        $addIndexIfNotExists('orders', 'orders_product_id_index', 'product_id');
        $addIndexIfNotExists('orders', 'orders_deadline_index', 'deadline');
        $addIndexIfNotExists('orders', 'orders_price_index', 'price');
        $addIndexIfNotExists('orders', 'orders_reason_status_index', 'reason_status');
        $addIndexIfNotExists('orders', 'orders_is_archived_index', 'is_archived');
        $addIndexIfNotExists('orders', 'orders_archived_at_index', 'archived_at');
        $addIndexIfNotExists('orders', 'orders_updated_at_index', 'updated_at');

        // Составные индексы для сложных запросов
        $addIndexIfNotExists('orders', 'orders_stage_id_is_archived_index', ['stage_id', 'is_archived']);
        $addIndexIfNotExists('orders', 'orders_client_id_is_archived_index', ['client_id', 'is_archived']);
        $addIndexIfNotExists('orders', 'orders_deadline_is_archived_index', ['deadline', 'is_archived']);

        // Индексы для таблицы order_assignments
        $addIndexIfNotExists('order_assignments', 'order_assignments_status_index', 'status');
        $addIndexIfNotExists('order_assignments', 'order_assignments_assigned_by_index', 'assigned_by');
        $addIndexIfNotExists('order_assignments', 'order_assignments_role_type_index', 'role_type');
        $addIndexIfNotExists('order_assignments', 'order_assignments_assigned_at_index', 'assigned_at');
        $addIndexIfNotExists('order_assignments', 'order_assignments_started_at_index', 'started_at');
        $addIndexIfNotExists('order_assignments', 'order_assignments_completed_at_index', 'completed_at');

        // Составные индексы
        $addIndexIfNotExists('order_assignments', 'order_assignments_user_id_status_index', ['user_id', 'status']);
        $addIndexIfNotExists('order_assignments', 'order_assignments_order_id_status_index', ['order_id', 'status']);
        $addIndexIfNotExists('order_assignments', 'order_assignments_status_assigned_at_index', ['status', 'assigned_at']);

        // Индексы для таблицы products
        $addIndexIfNotExists('products', 'products_name_index', 'name');

        // Индексы для таблицы clients
        $addIndexIfNotExists('clients', 'clients_name_index', 'name');

        // Индексы для таблицы client_contacts
        $addIndexIfNotExists('client_contacts', 'client_contacts_type_index', 'type');

        // Индексы для таблицы projects
        $addIndexIfNotExists('projects', 'projects_title_index', 'title');

        // Индексы для таблицы stages
        $addIndexIfNotExists('stages', 'stages_name_index', 'name');
        $addIndexIfNotExists('stages', 'stages_order_index', 'order');

        // Индексы для таблицы roles
        $addIndexIfNotExists('roles', 'roles_name_index', 'name');

        // Индексы для таблицы user_roles (уникальный составной индекс)
        $addIndexIfNotExists('user_roles', 'user_roles_user_id_role_id_unique', ['user_id', 'role_id'], 'unique');

        // Индексы для таблицы comments
        $addIndexIfNotExists('comments', 'comments_created_at_index', 'created_at');

        // Индексы для таблицы audit_logs
        $addIndexIfNotExists('audit_logs', 'audit_logs_action_index', 'action');
        $addIndexIfNotExists('audit_logs', 'audit_logs_auditable_type_index', 'auditable_type');
        $addIndexIfNotExists('audit_logs', 'audit_logs_change_type_index', 'change_type');

        // Составные индексы
        $addIndexIfNotExists('audit_logs', 'audit_logs_auditable_type_auditable_id_index', ['auditable_type', 'auditable_id']);
        $addIndexIfNotExists('audit_logs', 'audit_logs_user_id_created_at_index', ['user_id', 'created_at']);

        // Индексы для таблицы notifications
        $addIndexIfNotExists('notifications', 'notifications_type_index', 'type');
        $addIndexIfNotExists('notifications', 'notifications_read_at_index', 'read_at');

        // Составные индексы
        $addIndexIfNotExists('notifications', 'notifications_notifiable_id_read_at_index', ['notifiable_id', 'read_at']);
        $addIndexIfNotExists('notifications', 'notifications_type_created_at_index', ['type', 'created_at']);

        // Индексы для таблицы stage_roles (уникальный составной индекс)
        $addIndexIfNotExists('stage_roles', 'stage_roles_stage_id_role_id_unique', ['stage_id', 'role_id'], 'unique');

        // Индексы для таблицы product_stages
        $addIndexIfNotExists('product_stages', 'product_stages_product_id_stage_id_unique', ['product_id', 'stage_id'], 'unique');

        // Индексы для таблицы order_stage_assignments
        $addIndexIfNotExists('order_stage_assignments', 'order_stage_assignments_created_at_index', 'created_at');

        // Индексы для таблицы product_assignments
        $addIndexIfNotExists('product_assignments', 'product_assignments_role_type_index', 'role_type');

        // Составные индексы
        $addIndexIfNotExists('product_assignments', 'product_assignments_user_id_role_type_index', ['user_id', 'role_type']);

        // Индексы для таблицы order_status_logs
        $addIndexIfNotExists('order_status_logs', 'order_status_logs_from_status_index', 'from_status');
        $addIndexIfNotExists('order_status_logs', 'order_status_logs_to_status_index', 'to_status');
        $addIndexIfNotExists('order_status_logs', 'order_status_logs_changed_at_index', 'changed_at');

        // Составные индексы
        $addIndexIfNotExists('order_status_logs', 'order_status_logs_order_id_changed_at_index', ['order_id', 'changed_at']);
        $addIndexIfNotExists('order_status_logs', 'order_status_logs_from_status_to_status_index', ['from_status', 'to_status']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем все добавленные индексы (только те, которые мы добавили)

        // orders
        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->dropIndex(['project_id']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['product_id']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['deadline']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['price']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['reason_status']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['is_archived']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['archived_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['updated_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['stage_id', 'is_archived']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['client_id', 'is_archived']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['deadline', 'is_archived']);
            } catch (\Exception $e) {
            }
        });

        // order_assignments
        Schema::table('order_assignments', function (Blueprint $table) {
            try {
                $table->dropIndex(['status']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['assigned_by']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['role_type']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['assigned_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['started_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['completed_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['user_id', 'status']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['order_id', 'status']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['status', 'assigned_at']);
            } catch (\Exception $e) {
            }
        });

        // products
        Schema::table('products', function (Blueprint $table) {
            try {
                $table->dropIndex(['name']);
            } catch (\Exception $e) {
            }
        });

        // clients
        Schema::table('clients', function (Blueprint $table) {
            try {
                $table->dropIndex(['name']);
            } catch (\Exception $e) {
            }
        });

        // client_contacts
        Schema::table('client_contacts', function (Blueprint $table) {
            try {
                $table->dropIndex(['type']);
            } catch (\Exception $e) {
            }
        });

        // projects
        Schema::table('projects', function (Blueprint $table) {
            try {
                $table->dropIndex(['title']);
            } catch (\Exception $e) {
            }
        });

        // stages
        Schema::table('stages', function (Blueprint $table) {
            try {
                $table->dropIndex(['name']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['order']);
            } catch (\Exception $e) {
            }
        });

        // roles
        Schema::table('roles', function (Blueprint $table) {
            try {
                $table->dropIndex(['name']);
            } catch (\Exception $e) {
            }
        });

        // user_roles
        Schema::table('user_roles', function (Blueprint $table) {
            try {
                $table->dropUnique(['user_id', 'role_id']);
            } catch (\Exception $e) {
            }
        });

        // comments
        Schema::table('comments', function (Blueprint $table) {
            try {
                $table->dropIndex(['created_at']);
            } catch (\Exception $e) {
            }
        });

        // audit_logs
        Schema::table('audit_logs', function (Blueprint $table) {
            try {
                $table->dropIndex(['action']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['auditable_type']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['change_type']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['auditable_type', 'auditable_id']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['user_id', 'created_at']);
            } catch (\Exception $e) {
            }
        });

        // notifications
        Schema::table('notifications', function (Blueprint $table) {
            try {
                $table->dropIndex(['type']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['read_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['notifiable_id', 'read_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['type', 'created_at']);
            } catch (\Exception $e) {
            }
        });

        // stage_roles
        Schema::table('stage_roles', function (Blueprint $table) {
            try {
                $table->dropUnique(['stage_id', 'role_id']);
            } catch (\Exception $e) {
            }
        });

        // product_stages
        Schema::table('product_stages', function (Blueprint $table) {
            try {
                $table->dropUnique(['product_id', 'stage_id']);
            } catch (\Exception $e) {
            }
        });

        // order_stage_assignments
        Schema::table('order_stage_assignments', function (Blueprint $table) {
            try {
                $table->dropIndex(['created_at']);
            } catch (\Exception $e) {
            }
        });

        // product_assignments
        Schema::table('product_assignments', function (Blueprint $table) {
            try {
                $table->dropIndex(['role_type']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['user_id', 'role_type']);
            } catch (\Exception $e) {
            }
        });

        // order_status_logs
        Schema::table('order_status_logs', function (Blueprint $table) {
            try {
                $table->dropIndex(['from_status']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['to_status']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['changed_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['order_id', 'changed_at']);
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex(['from_status', 'to_status']);
            } catch (\Exception $e) {
            }
        });
    }
};
