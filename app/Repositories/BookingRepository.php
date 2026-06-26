<?php

namespace App\Repositories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookingRepository
{
    public function findOrFail(string $id): Booking
    {
        return Booking::with(['user', 'room', 'approval', 'logs.user'])->findOrFail($id);
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function hasConflict(
        string $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $excludeBookingId = null,
        bool $lockForUpdate = false
    ): bool {
        $query = Booking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::APPROVED->value])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    public function getUserBookings(string $userId, ?string $status = null): Collection
    {
        $query = Booking::with(['room:id,name,slug', 'approval'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getCalendarData(string $start, string $end, ?string $roomId = null): Collection
    {
        $query = Booking::with(['room:id,name,slug', 'user:id,name'])
            ->whereBetween('booking_date', [$start, $end])
            ->whereIn('status', [
                BookingStatus::PENDING->value,
                BookingStatus::APPROVED->value,
                BookingStatus::COMPLETED->value,
            ]);

        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        return $query->get()->map(fn ($booking) => [
            'id' => $booking->id,
            'title' => $booking->title,
            'room' => $booking->room->name,
            'room_id' => $booking->room_id,
            'user' => $booking->user->name,
            'start' => $booking->booking_date . 'T' . $booking->start_time,
            'end' => $booking->booking_date . 'T' . $booking->end_time,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'status' => $booking->status,
            'backgroundColor' => match ($booking->status) {
                BookingStatus::PENDING->value => '#f59e0b',
                BookingStatus::APPROVED->value => '#22c55e',
                BookingStatus::COMPLETED->value => '#6366f1',
                default => '#6b7280',
            },
            'borderColor' => match ($booking->status) {
                BookingStatus::PENDING->value => '#f59e0b',
                BookingStatus::APPROVED->value => '#22c55e',
                BookingStatus::COMPLETED->value => '#6366f1',
                default => '#6b7280',
            },
            'textColor' => '#ffffff',
            'display' => 'block',
            'extendedProps' => [
                'type' => 'booking',
                'status_label' => BookingStatus::tryFrom($booking->status)?->label() ?? $booking->status,
                'description' => $booking->description,
            ],
        ]);
    }

    public function getPendingBookings(): LengthAwarePaginator
    {
        return Booking::with(['user:id,name,department', 'room:id,name,slug'])
            ->where('status', BookingStatus::PENDING->value)
            ->orderBy('created_at', 'asc')
            ->paginate(15);
    }
}
