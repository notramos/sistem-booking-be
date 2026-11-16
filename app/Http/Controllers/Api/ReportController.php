<?php

namespace App\Http\Controllers\Api;

use App\Exports\IntensiMisaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExportPdfReportRequest;
use App\Http\Requests\Api\IntensiMisaReportRequest;
use App\Http\Requests\Api\MonthlyReportRequest;
use App\Http\Response\ApiResponse;
use App\Services\IntensiMisaReportService;
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

    public function intensiMisa(IntensiMisaReportRequest $request, IntensiMisaReportService $report): JsonResponse
    {
        $data = $report->query($request->validated())->paginate(25);
        $data->through(fn ($service) => $report->row($service));

        return $this->paginated($data);
    }

    public function exportIntensiMisa(IntensiMisaReportRequest $request)
    {
        return Excel::download(new IntensiMisaExport($request->validated()), 'intensi-misa-'.now()->format('YmdHis').'.xlsx');
    }

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
            'bookings' => $this->reportService->bookingReportForExport($startDate, $endDate),
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
                        return $this->data->map(fn ($row) => [
                            'Peminjam' => $row['booking']->title,
                            'Kegiatan' => $row['booking']->description ?? '-',
                            'Ruangan' => $row['booking']->room->display_label ?? '-',
                            'Pemesan' => $row['booking']->user->name ?? '-',
                            'Tanggal' => $row['booking_date'],
                            'Mulai' => $row['booking']->start_time,
                            'Selesai' => $row['booking']->end_time,
                            'Status' => $row['booking']->status,
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
