<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomFacilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $loaded = $this->resource ? $this->resource->getAttributes() : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->when(array_key_exists('icon', $loaded), $this->icon),
            'is_active' => $this->when(array_key_exists('is_active', $loaded), $this->is_active),
        ];
    }
}
