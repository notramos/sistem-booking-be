<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $loaded = $this->resource ? $this->resource->getAttributes() : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->when(array_key_exists('description', $loaded), $this->description),
            'is_active' => $this->when(array_key_exists('is_active', $loaded), $this->is_active),
        ];
    }
}
