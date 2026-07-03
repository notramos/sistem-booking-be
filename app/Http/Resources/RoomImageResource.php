<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_id' => $this->room_id,
            'image_path' => $this->image_path,
            'is_primary' => $this->is_primary,
            'sort_order' => $this->sort_order,
        ];
    }
}
