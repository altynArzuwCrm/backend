<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'available_stages' => $this->availableStages->map(function ($stage) {
                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'display_name' => $stage->display_name,
                    'color' => $stage->color,
                    'is_default' => $stage->pivot->is_default ?? false,
                    'roles' => $stage->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                ];
            }),
            'all_stages' => $this->productStages->map(function ($productStage) {
                return [
                    'id' => $productStage->stage->id,
                    'name' => $productStage->stage->name,
                    'display_name' => $productStage->stage->display_name,
                    'color' => $productStage->stage->color,
                    'is_available' => $productStage->is_available,
                    'is_default' => $productStage->is_default,
                    'roles' => $productStage->stage->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                ];
            }),

            'assignments' => $this->assignments->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'role_type' => $assignment->role_type,
                    'is_active' => $assignment->is_active,
                    'user' => $assignment->user ? new UserResource($assignment->user) : null,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
