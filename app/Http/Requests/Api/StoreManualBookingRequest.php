<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bookings.approve') ?? false;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'contact_person' => 'nullable|string|max:255',
            'expected_attendees' => 'nullable|integer|min:1',
            // Status di-input langsung oleh staf — mengecualikan status revisi (fitur
            // revisi sudah dihapus, bukan pilihan untuk entri baru) dan sekretariat_review/
            // admin_review dipertahankan untuk fleksibilitas kalau ingin dicatat "masih
            // dalam proses" alih-alih langsung final.
            'status' => 'required|in:pending,sekretariat_review,admin_review,approved,rejected,cancelled,completed',
        ];
    }

    public function attributes(): array
    {
        return [
            'room_id' => 'ruangan',
            'title' => 'peminjam',
            'booking_date' => 'tanggal',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'expected_attendees' => 'jumlah peserta',
        ];
    }
}
