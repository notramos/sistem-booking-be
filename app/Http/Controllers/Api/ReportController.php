<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExportPdfReportRequest;
use App\Http\Requests\Api\MonthlyReportRequest;
use App\Http\Response\ApiResponse;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(private ReportService $reportService) {}

    public function bookings(Request $request): JsonResponse
    {
        $data = $this->reportService->bookingReport(
            startDate: $request->start_date,
            endDate: $request->end_date,
            status: $request->status,
        );

        return $this->paginated($data);
    }

    public function roomUtilization(Request $request): JsonResponse
    {
        $data = $this->reportService->roomUtilization(
            startDate: $request->start_date,
            endDate: $request->end_date,
        );

        return $this->success($data);
    }

    public function userActivity(Request $request): JsonResponse
    {
        $data = $this->reportService->userActivity(
            startDate: $request->start_date,
            endDate: $request->end_date,
        );

        return $this->paginated($data);
    }

    public function monthly(MonthlyReportRequest $request): JsonResponse
    {
        $data = $this->reportService->monthly($request->year, $request->month);

        return $this->success($data);
    }

    public function exportPdf(ExportPdfReportRequest $request)
    {
        return $this->reportService->exportPdf($request->type, $request->only(['start_date', 'end_date']));
    }

    public function exportExcel(Request $request)
    {
        $type = $request->get('type', 'bookings');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $data = match ($type) {
            'bookings' => $this->reportService->bookingReport($startDate, $endDate),
            'utilization' => $this->reportService->roomUtilization($startDate, $endDate),
            default => throw new \InvalidArgumentException('Invalid report type'),
        };

        $filename = "laporan-{$type}-".now()->format('YmdHis').'.xlsx';

        return Excel::download(
            new class($data, $type) implements FromArray, WithHeadings
            {
                public function __construct(private $data, private string $type) {}

                public function array(): array
                {
                    if ($this->type === 'bookings') {
                        return $this->data->map(fn ($b) => [
                            'Peminjam' => $b->title,
                            'Kegiatan' => $b->description ?? '-',
                            'Ruangan' => $b->room->name ?? '-',
                            'Pemesan' => $b->user->name ?? '-',
                            'Tanggal' => $b->booking_date,
                            'Mulai' => $b->start_time,
                            'Selesai' => $b->end_time,
                            'Status' => $b->status,
                        ])->toArray();
                    }

                    return $this->data->toArray();
                }

                public function headings(): array
                {
                    return $this->type === 'bookings'
                        ? ['Peminjam', 'Kegiatan', 'Ruangan', 'Pemesan', 'Tanggal', 'Mulai', 'Selesai', 'Status']
                        : ['Nama Ruangan', 'Kapasitas', 'Total Booking', 'Menit Terpakai', 'Utilisasi (%)'];
                }
            },
            $filename
        );
    }
}
