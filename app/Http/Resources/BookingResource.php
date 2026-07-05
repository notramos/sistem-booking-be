<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'room_id' => $this->room_id,
            'title' => $this->title,
            'description' => $this->description,
            'booking_date' => $this->booking_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'purpose_type' => $this->purpose_type,
            'expected_attendees' => $this->expected_attendees,
            'contact_person' => $this->contact_person,
            'status' => $this->status,
            'notes' => $this->notes,
            'service_details' => $this->service_details,
            'reject_reason' => $this->reject_reason,
            'cancelled_at' => $this->cancelled_at,
            'completed_at' => $this->completed_at,
            'is_cancellable' => $this->is_cancellable,
            'signature_pemohon' => $this->signature_pemohon,
            'signature_pemohon_at' => $this->signature_pemohon_at,
            'signature_petugas' => $this->signature_petugas,
            'signature_petugas_at' => $this->signature_petugas_at,
            'signed_petugas_by' => $this->whenLoaded('signedBy', fn () => $this->signedBy?->name),
            'user' => new UserResource($this->whenLoaded('user')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'approval' => new BookingApprovalResource($this->whenLoaded('approval')),
            'logs' => BookingLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at,
        ];
    }
}
