<?php

namespace App\Repositories;

use App\Models\Stage;
use App\DTOs\StageDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

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

        return StageDTO::fromModel($stage->load('roles'));
    }

    public function updateStage(Stage $stage, array $data): StageDTO
    {
        $stage->update($data);

        // Обновляем роли если они переданы
        if (isset($data['roles']) && is_array($data['roles'])) {
            $stage->roles()->sync($data['roles']);
        }

        return StageDTO::fromModel($stage->load('roles'));
    }

    public function deleteStage(Stage $stage): bool
    {
        return $stage->delete();
    }

    public function getAllStages(): array
    {
        $stages = Stage::with(['roles'])->orderBy('order')->get();
        return array_map([StageDTO::class, 'fromModel'], $stages->toArray());
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
            ->whereHas('roles', function ($query) use ($roleName) {
                $query->where('name', $roleName);
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
