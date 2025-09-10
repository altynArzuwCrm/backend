<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageUrl = null;
        if ($this->image) {
            $imageUrl = Storage::disk('public')->url($this->image);
        }

        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'username'   => $this->username,
            'phone'      => $this->phone,
            'image'      => $this->image,
            'image_url'  => $imageUrl, // Полный URL для изображения
            'fcm_token'  => $this->fcm_token, // FCM токен для push-уведомлений
            'is_active'  => $this->is_active,
            'roles'      => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
