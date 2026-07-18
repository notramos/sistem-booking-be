<?php

namespace App\Http\Requests\Api;

use App\Models\Room;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $minDate = now()->addDays(config('booking.min_advance_days'))->toDateString();
        $maxFirstDate = now()->addDays(400)->toDateString();

        return [
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            // 'dates' otoritatif: sudah diresolusi pemohon di frontend (termasuk penggantian
            // tanggal bentrok) via endpoint preview — hanya tanggal PALING AWAL yang wajib H+7,
            // sisanya boleh melampaui H+30 karena itu memang inti dari booking rutin.
            'dates' => 'required|array|min:1|max:60',
            'dates.*' => "date|after_or_equal:{$minDate}|before_or_equal:{$maxFirstDate}",
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'purpose_type' => 'nullable|in:ibadah,acara_keluarga,latihan_musik,pembinaan,rapat,seminar,publik',
            'expected_attendees' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'pattern' => 'required|in:weekly,monthly',
        ];
    }

    public function messages(): array
    {
        $minDate = now()->locale('id')->addDays(config('booking.min_advance_days'))->translatedFormat('d F Y');

        return [
            'dates.*.after_or_equal' => "Tanggal paling awal harus minimal {$minDate} (H+".config('booking.min_advance_days').' dari hari ini).',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hours = config('booking.operating_hours');
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if ($start && $start < $hours['open']) {
                $validator->errors()->add('start_time', "Waktu mulai tidak boleh sebelum jam operasional ({$hours['open']}).");
            }
            if ($end && $end > $hours['close']) {
                $validator->errors()->add('end_time', "Waktu selesai tidak boleh melewati jam operasional ({$hours['close']}).");
            }

            $attendees = $this->input('expected_attendees');
            $roomId = $this->input('room_id');
            if ($attendees && $roomId) {
                $room = Room::find($roomId);
                if ($room && (int) $attendees > (int) $room->capacity) {
                    $validator->errors()->add('expected_attendees', "Jumlah peserta melebihi kapasitas ruangan ({$room->capacity} orang).");
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'room_id' => 'ruangan',
            'title' => 'judul',
            'description' => 'deskripsi',
            'dates' => 'tanggal',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'purpose_type' => 'tujuan penggunaan',
            'expected_attendees' => 'perkiraan peserta',
            'notes' => 'catatan',
            'pattern' => 'pola pengulangan',
        ];
    }
}
