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
            'lingkungan' => $this->whenLoaded('lingkungan', fn () => $this->lingkungan->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
            ])),
        ];
    }
}
