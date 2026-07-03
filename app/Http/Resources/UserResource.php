<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Some list endpoints eager-load this relation with a partial column selection
     * (e.g. 'user:id,name') to keep queries cheap. Unselected columns must be omitted
     * rather than shown as null, otherwise "not fetched" looks identical to "empty in DB".
     */
    public function toArray(Request $request): array
    {
        $loaded = $this->resource ? $this->resource->getAttributes() : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when(array_key_exists('email', $loaded), $this->email),
            'phone' => $this->when(array_key_exists('phone', $loaded), $this->phone),
            'avatar' => $this->when(array_key_exists('avatar', $loaded), $this->avatar),
            'department' => $this->when(array_key_exists('department', $loaded), $this->department),
            'position' => $this->when(array_key_exists('position', $loaded), $this->position),
            'nip' => $this->when(array_key_exists('nip', $loaded), $this->nip),
            'is_active' => $this->when(array_key_exists('is_active', $loaded), $this->is_active),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ])),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
            ])),
        ];
    }
}
