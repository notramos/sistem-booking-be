<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'approver_id' => $this->approver_id,
            'action' => $this->action,
            'notes' => $this->notes,
            'approver' => new UserResource($this->whenLoaded('approver')),
            'created_at' => $this->created_at,
        ];
    }
}
