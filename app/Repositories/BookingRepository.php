<?php

namespace App\Repositories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingRepository
{
    /**
     * Serialize booking create/update for a room within the current transaction.
     *
     * hasConflict()'s SELECT ... FOR UPDATE only locks rows that already exist, so two
     * concurrent requests for a still-open slot can both pass the check before either
     * INSERT commits (classic phantom-read race). Row-locking the room itself closes
     * this race: the room row is guaranteed to already exist, so a standard SELECT ...
     * FOR UPDATE on it serializes all booking writes for that room for the lifetime of
     * the enclosing transaction — portable across Postgres and MySQL (no driver-specific
     * advisory-lock function needed). Must be called inside DB::transaction().
     */
    public function lockRoom(string $roomId): void
    {
        DB::table('rooms')->where('id', $roomId)->lockForUpdate()->first();
    }

    public function findOrFail(string $id): Booking
    {
        return Booking::with(['user', 'room', 'approvals', 'logs.user'])->findOrFail($id);
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    /**
     * Booking rutin cuma 1 baris tapi bisa mewakili banyak tanggal (recurring_dates JSON),
     * jadi $date dianggap bentrok kalau cocok dengan booking_date (booking biasa/tanggal
     * pertama rutin) ATAU muncul di dalam recurring_dates milik booking lain.
     */
    public function hasConflict(
        string $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $excludeBookingId = null,
        bool $lockForUpdate = false
    ): bool {
        $query = Booking::where('room_id', $roomId)
            ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::APPROVED->value])
            ->where(function ($q) use ($date) {
                $q->where('booking_date', $date)
                    ->orWhereJsonContains('recurring_dates', $date);
            })
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    public function getUserBookings(string $userId, ?string $status = null, int $page = 1, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Booking::with(['room:id,name,slug', 'approvals'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($search).'%']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Booking rutin (1 baris, banyak tanggal di recurring_dates) di-expand jadi 1 event
     * kalender per tanggal. Filter rentang untuk rutin dilakukan di PHP (bukan di query)
     * karena recurring_dates cuma JSON array, bukan kolom yang bisa di-BETWEEN.
     */
    public function getCalendarData(string $start, string $end, ?string $roomId = null): Collection
    {
        $statuses = [
            BookingStatus::PENDING->value,
            BookingStatus::APPROVED->value,
            BookingStatus::COMPLETED->value,
        ];

        $reguler = Booking::with(['room:id,name,slug', 'user:id,name'])
            ->where('booking_type', 'reguler')
            ->whereBetween('booking_date', [$start, $end])
            ->whereIn('status', $statuses)
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->get();

        $rutin = Booking::with(['room:id,name,slug', 'user:id,name'])
            ->where('booking_type', 'rutin')
            ->whereIn('status', $statuses)
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->get();

        $events = collect();

        foreach ($reguler as $booking) {
            $events->push($this->toCalendarEvent($booking, $booking->booking_date->format('Y-m-d')));
        }

        foreach ($rutin as $booking) {
            foreach (($booking->recurring_dates ?? []) as $date) {
                if ($date >= $start && $date <= $end) {
                    $events->push($this->toCalendarEvent($booking, $date));
                }
            }
        }

        return $events;
    }

    private function toCalendarEvent(Booking $booking, string $date): array
    {
        return [
            'id' => "{$booking->id}::{$date}",
            'booking_id' => $booking->id,
            'title' => $booking->title,
            'room' => $booking->room->name,
            'room_id' => $booking->room_id,
            'user' => $booking->user->name,
            'start' => $date.'T'.$booking->start_time,
            'end' => $date.'T'.$booking->end_time,
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
                'is_recurring' => $booking->booking_type === 'rutin',
            ],
        ];
    }

    /**
     * P2/Pastor/IT Admin melihat semua booking yang masih bisa diaksi (termasuk yang
     * masih di tahap sekretariat, karena mereka bisa override/skip), sekretariat
     * hanya melihat antrean tahap pertama.
     */
    public function getPendingBookings(User $user): LengthAwarePaginator
    {
        $query = Booking::with(['user:id,name,department', 'room:id,name,slug'])
            ->orderBy('created_at', 'asc');

        if ($user->hasAnyRole(['p2', 'pastor', 'it_admin'])) {
            $query->whereIn('status', [
                BookingStatus::PENDING->value,
                BookingStatus::SEKRETARIAT_REVIEW->value,
                BookingStatus::ADMIN_REVIEW->value,
            ]);
        } else {
            $query->pendingSekretariat();
        }

        return $query->paginate(15);
    }
}
