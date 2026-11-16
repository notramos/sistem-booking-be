<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class ReportService
{
    public function bookingReport(?string $startDate = null, ?string $endDate = null, ?string $status = null)
    {
        $query = Booking::with(['user:id,name', 'room:id,name,patron_name']);

        if ($startDate) $query->where('booking_date', '>=', $startDate);
        if ($endDate) $query->where('booking_date', '<=', $endDate);
        if ($status) $query->where('status', $status);

        return $query->orderBy('booking_date', 'desc')->paginate(15);
    }

    /**
     * Return one export row for every occurrence of a recurring booking.
     *
     * Recurring bookings are intentionally stored as one booking record with
     * all occurrence dates in recurring_dates. A normal booking has no such
     * list, so it is represented by booking_date.
     */
    public function bookingReportForExport(?string $startDate = null, ?string $endDate = null, ?string $status = null): Collection
    {
        $query = Booking::with(['user:id,name', 'room:id,name,patron_name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            // A recurring series can start before the selected period while
            // still having occurrences inside it.
            ->when($endDate, fn ($q) => $q->where('booking_date', '<=', $endDate))
            ->orderBy('booking_date', 'desc');

        return $query->get()
            ->flatMap(function (Booking $booking) use ($startDate, $endDate) {
                $dates = $booking->recurring_dates ?: [$booking->booking_date?->toDateString()];

                return collect($dates)
                    ->filter(fn ($date) => is_string($date) && (!$startDate || $date >= $startDate) && (!$endDate || $date <= $endDate))
                    ->map(fn (string $date) => [
                        'booking' => $booking,
                        'booking_date' => $date,
                    ]);
            })
            ->sortByDesc('booking_date')
            ->values();
    }

    public function roomUtilization(?string $startDate = null, ?string $endDate = null)
    {
        $rooms = Room::withCount(['bookings as total_bookings' => function ($q) use ($startDate, $endDate) {
            if ($startDate) $q->where('booking_date', '>=', $startDate);
            if ($endDate) $q->where('booking_date', '<=', $endDate);
        }])->get();

        $totalHours = 0;
        if ($startDate && $endDate) {
            $totalHours = max(1, now()->parse($startDate)->diffInDays(now()->parse($endDate)) * 12);
        }

        return $rooms->map(function ($room) use ($totalHours, $startDate, $endDate) {
            $bookedMinutes = Booking::where('room_id', $room->id)
                ->whereIn('status', ['approved', 'completed'])
                ->when($startDate, fn($q) => $q->where('booking_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('booking_date', '<=', $endDate))
                ->get()
                ->sum(fn($b) => now()->parse($b->start_time)->diffInMinutes(now()->parse($b->end_time)));

            return [
                'room_id' => $room->id,
                'room_name' => $room->display_name,
                'room_number' => $room->name,
                'capacity' => $room->capacity,
                'total_bookings' => $room->total_bookings,
                'booked_minutes' => $bookedMinutes,
                'utilization_percentage' => $totalHours > 0 ? round(($bookedMinutes / ($totalHours * 60)) * 100, 2) : 0,
            ];
        });
    }

    public function userActivity(?string $startDate = null, ?string $endDate = null)
    {
        return User::withCount(['bookings' => function ($q) use ($startDate, $endDate) {
            if ($startDate) $q->where('created_at', '>=', $startDate);
            if ($endDate) $q->where('created_at', '<=', $endDate);
        }])
            ->orderBy('bookings_count', 'desc')
            ->paginate(15);
    }

    public function monthly(string $year, string $month): array
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = now()->parse($startDate)->endOfMonth()->toDateString();

        $bookings = Booking::whereBetween('booking_date', [$startDate, $endDate])->get();

        return [
            'total_bookings' => $bookings->count(),
            'status_breakdown' => $bookings->groupBy('status')->map->count(),
            'purpose_breakdown' => $bookings->groupBy('purpose_type')->map->count(),
            'approved_bookings' => $bookings->where('status', 'approved')->count(),
            'rejected_bookings' => $bookings->where('status', 'rejected')->count(),
            'cancelled_bookings' => $bookings->where('status', 'cancelled')->count(),
            'total_rooms_used' => $bookings->groupBy('room_id')->count(),
            'unique_users' => $bookings->groupBy('user_id')->count(),
        ];
    }

    public function exportPdf(string $type, array $filters = [])
    {
        $data = match ($type) {
            'bookings' => ['bookings' => $this->bookingReport(
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null
            )],
            'utilization' => ['rooms' => $this->roomUtilization(
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null
            )],
            default => throw new \InvalidArgumentException('Invalid report type'),
        };

        $pdf = Pdf::loadView("reports.{$type}", $data);
        return $pdf->download("laporan-{$type}-" . now()->format('YmdHis') . '.pdf');
    }
}
