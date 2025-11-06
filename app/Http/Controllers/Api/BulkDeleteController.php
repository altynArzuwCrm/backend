<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Client;
use App\Models\Product;
use App\Models\Project;
use App\Models\Order;
use App\Models\Category;
use App\Models\Role;
use App\Models\Stage;
use App\Services\CacheService;

class BulkDeleteController extends Controller
{
    /**
     * Массовое удаление сущностей
     */
    public function destroy(Request $request, string $entity)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        $modelClass = $this->getModelClass($entity);
        
        if (!$modelClass) {
            return response()->json([
                'message' => 'Неверный тип сущности'
            ], 400);
        }

        $deleted = 0;
        $errors = [];
        $skipped = 0;

        foreach ($data['ids'] as $id) {
            try {
                $model = $modelClass::find($id);
                
                if (!$model) {
                    $errors[] = "ID $id: не найден";
                    continue;
                }

                // Проверка прав
                $gateMethod = 'delete';
                if (Gate::denies($gateMethod, $model)) {
                    $errors[] = "ID $id: нет прав для удаления";
                    continue;
                }

                // Специфичные проверки перед удалением
                $validationError = $this->validateBeforeDelete($model, $entity);
                if ($validationError) {
                    $errors[] = "ID $id: {$validationError}";
                    $skipped++;
                    Log::warning("Bulk delete validation failed for $entity ID $id", [
                        'entity' => $entity,
                        'id' => $id,
                        'error' => $validationError,
                        'user_id' => auth()->id()
                    ]);
                    continue;
                }

                // Удаление в транзакции
                DB::transaction(function () use ($model, $entity, $id, &$deleted) {
                    $model->delete();
                    $deleted++;
                    
                    // Инвалидация кэша
                    $this->invalidateCache($entity, $id);
                    
                    Log::info("Bulk delete: $entity ID $id deleted", [
                        'entity' => $entity,
                        'id' => $id,
                        'user_id' => auth()->id()
                    ]);
                });

            } catch (\Exception $e) {
                $errors[] = "ID $id: {$e->getMessage()}";
                Log::error("Bulk delete error for $entity ID $id", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Дополнительная инвалидация кэша для всей сущности после массового удаления
        if ($deleted > 0) {
            $this->invalidateEntityCache($entity);
        }

        $response = [
            'message' => "Успешно удалено: $deleted, пропущено: $skipped",
            'deleted' => $deleted,
            'skipped' => $skipped,
            'total_requested' => count($data['ids'])
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $deleted > 0 ? 200 : 422);
    }

    /**
     * Получить класс модели по типу сущности
     */
    private function getModelClass(string $entity): ?string
    {
        $models = [
            'users' => User::class,
            'clients' => Client::class,
            'products' => Product::class,
            'projects' => Project::class,
            'orders' => Order::class,
            'categories' => Category::class,
            'roles' => Role::class,
            'stages' => Stage::class,
        ];

        return $models[$entity] ?? null;
    }

    /**
     * Валидация перед удалением
     */
    private function validateBeforeDelete($model, string $entity): ?string
    {
        switch ($entity) {
            case 'users':
                if (auth()->id() === $model->id) {
                    return 'нельзя удалить самого себя';
                }
                $orderAssignmentsCount = $model->assignments()->count();
                if ($orderAssignmentsCount > 0) {
                    return "назначен в $orderAssignmentsCount заказах";
                }
                break;

            case 'clients':
                $activeOrdersCount = $model->orders()->where('is_archived', false)->count();
                if ($activeOrdersCount > 0) {
                    return "есть $activeOrdersCount активных заказов";
                }
                break;

            case 'products':
                $ordersCount = $model->orders()->count();
                if ($ordersCount > 0) {
                    return "используется в $ordersCount заказах";
                }
                break;

            case 'projects':
                $activeOrdersCount = $model->orders()->where('is_archived', false)->count();
                if ($activeOrdersCount > 0) {
                    return "есть $activeOrdersCount активных заказов";
                }
                break;

            case 'orders':
                // Orders можно удалять без дополнительных проверок
                break;

            case 'roles':
                // Оптимизация: используем whereExists вместо whereHas
                $activeAssignmentsCount = $model->users()
                    ->whereExists(function ($subquery) {
                        $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('order_assignments')
                            ->join('orders', 'order_assignments.order_id', '=', 'orders.id')
                            ->whereColumn('order_assignments.user_id', 'users.id')
                            ->where('orders.is_archived', false);
                    })
                    ->count();
                if ($activeAssignmentsCount > 0) {
                    return "используется в $activeAssignmentsCount активных назначениях";
                }
                break;

            case 'categories':
                $productsCount = $model->products()->count();
                if ($productsCount > 0) {
                    return "используется в $productsCount товарах";
                }
                break;

            case 'stages':
                // Проверяем использование этапа во ВСЕХ заказах (активных и архивированных)
                $allOrdersCount = $model->orders()->count();
                $activeOrdersCount = $model->orders()->where('is_archived', false)->count();
                $archivedOrdersCount = $model->orders()->where('is_archived', true)->count();
                
                // Проверяем использование этапа в продуктах (product_stages)
                $productsCount = DB::table('product_stages')
                    ->where('stage_id', $model->id)
                    ->count();
                
                $errors = [];
                
                if ($allOrdersCount > 0) {
                    $errors[] = "используется в {$allOrdersCount} заказах";
                    if ($activeOrdersCount > 0) {
                        $errors[] = "из них {$activeOrdersCount} активных";
                    }
                    if ($archivedOrdersCount > 0) {
                        $errors[] = "и {$archivedOrdersCount} архивированных";
                    }
                }
                
                if ($productsCount > 0) {
                    $errors[] = "используется в {$productsCount} продуктах";
                }
                
                if (!empty($errors)) {
                    return 'Невозможно удалить этап: ' . implode(', ', $errors) . '. Сначала удалите или измените все связанные заказы и продукты.';
                }
                break;
        }

        return null;
    }

    /**
     * Инвалидация кэша для всей сущности
     */
    private function invalidateEntityCache(string $entity): void
    {
        switch ($entity) {
            case 'clients':
                CacheService::invalidateByTags([CacheService::TAG_CLIENTS]);
                break;
            case 'users':
                CacheService::invalidateByTags([CacheService::TAG_USERS]);
                break;
            case 'products':
                CacheService::invalidateByTags([CacheService::TAG_PRODUCTS]);
                break;
            case 'projects':
                CacheService::invalidateByTags([CacheService::TAG_PROJECTS]);
                break;
            case 'orders':
                CacheService::invalidateByTags([CacheService::TAG_ORDERS]);
                break;
            case 'categories':
                CacheService::invalidateByTags([CacheService::TAG_CATEGORIES]);
                break;
            case 'roles':
                CacheService::invalidateByTags([CacheService::TAG_ROLES]);
                break;
            case 'stages':
                CacheService::invalidateByTags([CacheService::TAG_STAGES]);
                break;
        }
    }

    /**
     * Инвалидация кэша
     */
    private function invalidateCache(string $entity, int $id): void
    {
        switch ($entity) {
            case 'users':
                CacheService::invalidateUsersByStageRolesCache();
                CacheService::invalidateByTags([CacheService::TAG_USERS]);
                break;

            case 'clients':
                CacheService::invalidateClientCaches($id);
                break;

            case 'products':
                CacheService::invalidateProductCaches($id);
                break;

            case 'projects':
                CacheService::invalidateByTags([CacheService::TAG_PROJECTS]);
                break;

            case 'orders':
                Cache::forget('orders_' . $id);
                CacheService::invalidateByTags([CacheService::TAG_ORDERS]);
                break;

            case 'categories':
                CacheService::invalidateByTags([CacheService::TAG_CATEGORIES]);
                break;

            case 'stages':
                CacheService::invalidateStageCaches();
                CacheService::invalidateByTags([CacheService::TAG_STAGES]);
                // Также инвалидируем кеш заказов, так как удаление этапа влияет на заказы
                CacheService::invalidateByTags([CacheService::TAG_ORDERS]);
                break;

            default:
                break;
        }
    }
}

