<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function monthly(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|digits:4',
            'month' => 'required|digits:2|between:01,12',
        ]);

        $data = $this->reportService->monthly($request->year, $request->month);

        return $this->success($data);
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'type' => 'required|in:bookings,utilization',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

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

        $filename = "laporan-{$type}-" . now()->format('YmdHis') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class($data, $type) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                public function __construct(private $data, private string $type) {}

                public function array(): array
                {
                    if ($this->type === 'bookings') {
                        return $this->data->map(fn($b) => [
                            'Judul' => $b->title,
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
                        ? ['Judul', 'Ruangan', 'Pemesan', 'Tanggal', 'Mulai', 'Selesai', 'Status']
                        : ['Nama Ruangan', 'Kapasitas', 'Total Booking', 'Menit Terpakai', 'Utilisasi (%)'];
                }
            },
            $filename
        );
    }
}
