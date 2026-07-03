<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ExportPdfReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:bookings,utilization',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'jenis laporan',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
        ];
    }
}
