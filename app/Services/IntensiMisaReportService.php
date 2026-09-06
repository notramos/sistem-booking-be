<?php

namespace App\Services;

use App\Models\CongregationService;
use Illuminate\Database\Eloquent\Builder;

class IntensiMisaReportService
{
    public function query(array $filters): Builder
    {
        return CongregationService::query()
            ->where('service_type', 'intensi_misa')
            ->where('dynamic_fields->tanggal_misa', '>=', $filters['start_date'])
            ->where('dynamic_fields->tanggal_misa', '<=', $filters['end_date'])
            ->when($filters['schedule'] ?? null, fn ($q, $time) => $q->where('dynamic_fields->jadwal_misa', $time))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('dynamic_fields->tanggal_misa')
            ->orderBy('dynamic_fields->jadwal_misa')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function row(CongregationService $service): array
    {
        $fields = $service->dynamic_fields ?? [];
        $text = fn ($key) => is_scalar($fields[$key] ?? null) ? (string) $fields[$key] : '';
        $amount = $text('stipendium_amount');

        return [
            'id' => $service->id,
            'submitted_at' => $service->created_at?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
            'received_at' => $service->received_at?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
            'misa_date' => $text('tanggal_misa'),
            'schedule' => $text('jadwal_misa'),
            'applicant_name' => $service->applicant_name,
            'region' => $service->region,
            'neighborhood' => $service->neighborhood,
            'ucapan_syukur' => $text('ucapan_syukur'),
            'doa_arwah' => $text('doa_arwah'),
            'permohonan_lainnya' => $text('permohonan_lainnya'),
            'stipendium' => is_numeric($amount) ? (float) $amount : null,
            'status' => $service->status,
        ];
    }
}
