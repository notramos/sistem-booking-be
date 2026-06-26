<?php

namespace App\Services;

use App\DTOs\CalendarFilterDTO;
use App\Models\MaintenanceSchedule;
use App\Repositories\BookingRepository;
use Illuminate\Support\Collection;

class CalendarService
{
    public function __construct(
        private BookingRepository $bookingRepo,
    ) {}

    public function getEvents(CalendarFilterDTO $dto): Collection
    {
        $bookings = $this->bookingRepo->getCalendarData($dto->start, $dto->end, $dto->roomId);

        $maintenance = MaintenanceSchedule::with('room')
            ->whereBetween('start_date', [$dto->start, $dto->end])
            ->orWhereBetween('end_date', [$dto->start, $dto->end])
            ->get()
            ->map(fn ($m) => [
                'id' => 'maintenance-' . $m->id,
                'title' => '🔧 ' . $m->title,
                'room' => $m->room->name,
                'start' => $m->start_date,
                'end' => $m->end_date,
                'allDay' => $m->is_all_day,
                'backgroundColor' => '#ef4444',
                'borderColor' => '#ef4444',
                'textColor' => '#ffffff',
                'display' => 'block',
                'extendedProps' => [
                    'type' => 'maintenance',
                    'description' => $m->description,
                ],
            ]);

        return $bookings->concat($maintenance);
    }
}
