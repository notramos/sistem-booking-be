<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateBookingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('bookings.approve') ?? false; }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255', 'description' => 'nullable|string',
            'room_id' => 'sometimes|exists:rooms,id', 'booking_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i', 'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'contact_person' => 'nullable|string|max:255', 'expected_attendees' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:pending,sekretariat_review,admin_review,approved,rejected,cancelled,completed',
            'notes' => 'nullable|string',
        ];
    }
}
