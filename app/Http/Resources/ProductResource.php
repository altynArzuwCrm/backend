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
            'has_design_stage' => $this->has_design_stage,
            'has_print_stage' => $this->has_print_stage,
            'has_engraving_stage' => $this->has_engraving_stage,
            'has_workshop_stage' => $this->has_workshop_stage,
            'designers' => UserResource::collection($this->getDesigners()),
            'print_operators' => UserResource::collection($this->getPrintOperators()),
            'engraving_operators' => UserResource::collection($this->getEngravingOperators()),
            'workshop_workers' => UserResource::collection($this->getWorkshopWorkers()),
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
