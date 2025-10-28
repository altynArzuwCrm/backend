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
                $activeAssignmentsCount = $model->users()->whereHas('assignments', function ($query) {
                    $query->whereHas('order', function ($orderQuery) {
                        $orderQuery->where('is_archived', false);
                    });
                })->count();
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
                // Можно добавить проверки если нужно
                break;
        }

        return null;
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

            default:
                break;
        }
    }
}

