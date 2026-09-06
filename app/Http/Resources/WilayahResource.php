<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WilayahResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'lingkungan_count' => $this->whenCounted('lingkungan'),
            'users_count' => $this->whenCounted('users'),
            'lingkungan' => $this->whenLoaded('lingkungan', fn () => $this->lingkungan->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'area' => $l->area,
                'is_active' => $l->is_active,
                'users_count' => $l->users_count ?? null,
            ])),
        ];
    }
}
