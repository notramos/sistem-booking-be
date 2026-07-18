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
            // $this->booking_date adalah instance Carbon (cast 'date:Y-m-d'); JSON-encode
            // default Carbon mengonversi ke UTC lalu ISO-8601 penuh, yang bisa menggeser
            // tanggal kalender kalau APP_TIMEZONE bukan UTC (di sini Asia/Jakarta) — format
            // eksplisit di sini supaya konsumen selalu terima tanggal polos "Y-m-d".
            'booking_date' => $this->booking_date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'purpose_type' => $this->purpose_type,
            'expected_attendees' => $this->expected_attendees,
            'contact_person' => $this->contact_person,
            'status' => $this->status,
            'booking_type' => $this->booking_type,
            'recurring_pattern' => $this->recurring_pattern,
            'recurring_dates' => $this->recurring_dates,
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
            'approvals' => BookingApprovalResource::collection($this->whenLoaded('approvals')),
            'logs' => BookingLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at,
        ];
    }
}
