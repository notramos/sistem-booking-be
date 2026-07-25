<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Some list endpoints eager-load this relation with a partial column selection
     * (e.g. 'room:id,name,slug') to keep queries cheap. Unselected columns must be
     * omitted rather than shown as null — see UserResource for the same rationale.
     */
    public function toArray(Request $request): array
    {
        $loaded = $this->resource ? $this->resource->getAttributes() : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'patron_name' => $this->when(array_key_exists('patron_name', $loaded), $this->patron_name),
            'slug' => $this->slug,
            'category_id' => $this->when(array_key_exists('category_id', $loaded), $this->category_id),
            'category' => new RoomCategoryResource($this->whenLoaded('category')),
            'description' => $this->when(array_key_exists('description', $loaded), $this->description),
            'capacity' => $this->when(array_key_exists('capacity', $loaded), $this->capacity),
            'floor' => $this->when(array_key_exists('floor', $loaded), $this->floor),
            'building' => $this->when(array_key_exists('building', $loaded), $this->building),
            'status' => $this->when(array_key_exists('status', $loaded), $this->status),
            'is_active' => $this->when(array_key_exists('is_active', $loaded), $this->is_active),
            'facilities' => RoomFacilityResource::collection($this->whenLoaded('facilities')),
            'images' => RoomImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new RoomImageResource($this->whenLoaded('primaryImage')),
            'created_at' => $this->when(array_key_exists('created_at', $loaded), $this->created_at),
        ];
    }
}
