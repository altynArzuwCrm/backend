<?php

namespace App\Repositories;

use App\Models\Stage;
use App\DTOs\StageDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;

class StageRepository
{
    public function getPaginatedStages(Request $request): LengthAwarePaginator
    {
        $query = Stage::with(['roles']);

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('display_name', 'like', '%' . $search . '%');
            });
        }



        // Сортировка
        $sortBy = $request->get('sort_by', 'order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = (int) $request->get('per_page', 30);
        return $query->paginate($perPage);
    }

    public function getStageById(int $id): ?StageDTO
    {
        $stage = Stage::with(['roles'])->find($id);

        if (!$stage) {
            return null;
        }

        return StageDTO::fromModel($stage);
    }

    public function createStage(array $data): StageDTO
    {
        $stage = Stage::create($data);

        // Если есть роли, привязываем их
        if (isset($data['roles']) && is_array($data['roles'])) {
            $stage->roles()->attach($data['roles']);
        }

        // Инвалидируем кэш стадий
        CacheService::invalidateStageCaches();
        // Инвалидируем кэш поиска стадий по имени
        \Illuminate\Support\Facades\Cache::forget('stages_by_name_map');
        
        $stage = Stage::with('roles')->find($stage->id);
        return StageDTO::fromModel($stage);
    }

    public function updateStage(Stage $stage, array $data): StageDTO
    {
        $oldName = $stage->name; // Сохраняем старое имя для инвалидации кэша
        $stage->update($data);

        // Обновляем роли если они переданы
        if (isset($data['roles']) && is_array($data['roles'])) {
            $stage->roles()->sync($data['roles']);
        }
        
        // Инвалидируем кэш стадий
        CacheService::invalidateStageCaches();
        // Инвалидируем кэш поиска стадий по имени
        \Illuminate\Support\Facades\Cache::forget('stages_by_name_map');
        \Illuminate\Support\Facades\Cache::forget("stage_by_name_{$oldName}");
        if (isset($data['name']) && $data['name'] !== $oldName) {
            \Illuminate\Support\Facades\Cache::forget("stage_by_name_{$data['name']}");
        }
        
        $stage = Stage::with('roles')->find($stage->id);
        return StageDTO::fromModel($stage);
    }

    public function deleteStage(Stage $stage): bool
    {
        $stageName = $stage->name; // Сохраняем имя для инвалидации кэша
        $result = $stage->delete();
        
        // Инвалидируем кэш стадий
        CacheService::invalidateStageCaches();
        // Инвалидируем кэш поиска стадий по имени
        \Illuminate\Support\Facades\Cache::forget('stages_by_name_map');
        \Illuminate\Support\Facades\Cache::forget("stage_by_name_{$stageName}");
        
        return $result;
    }

    public function getAllStages(): array
    {
        // Кэшируем все стадии на 4 часа, так как они меняются редко
        $cacheKey = 'all_stages_dto';
        return Cache::remember($cacheKey, 14400, function () {
            $stages = Stage::select('stages.id', 'stages.name', 'stages.display_name', 'stages.description', 'stages.order', 'stages.color', 'stages.created_at', 'stages.updated_at')
                ->with(['roles' => function ($q) {
                    $q->select('roles.id', 'roles.name', 'roles.display_name');
                }])
                ->orderBy('order')
                ->get();
            return array_map([StageDTO::class, 'fromModel'], $stages->toArray());
        });
    }

    public function getActiveStages(): array
    {
        $stages = Stage::with(['roles'])
            ->orderBy('order')
            ->get();
        return array_map([StageDTO::class, 'fromModel'], $stages->toArray());
    }

    public function getStagesByRole(string $roleName): array
    {
        $stages = Stage::with(['roles'])
            ->whereExists(function ($subquery) use ($roleName) {
                $subquery->select(DB::raw(1))
                    ->from('stage_roles')
                    ->join('roles', 'stage_roles.role_id', '=', 'roles.id')
                    ->whereColumn('stage_roles.stage_id', 'stages.id')
                    ->where('roles.name', $roleName);
            })
            ->orderBy('order')
            ->get();
        return array_map([StageDTO::class, 'fromModel'], $stages->toArray());
    }

    public function reorderStages(array $stageOrders): bool
    {
        foreach ($stageOrders as $stageOrder) {
            if (isset($stageOrder['id']) && isset($stageOrder['order'])) {
                Stage::where('id', $stageOrder['id'])
                    ->update(['order' => $stageOrder['order']]);
            }
        }
        return true;
    }

    public function getStageByName(string $name): ?StageDTO
    {
        $stage = Stage::with(['roles'])
            ->where('name', $name)
            ->first();

        if (!$stage) {
            return null;
        }

        return StageDTO::fromModel($stage);
    }
}
