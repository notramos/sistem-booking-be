<?php

namespace App\Repositories;

use App\Enums\BookingStatus;
use App\Models\Booking;
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
     * INSERT commits (classic phantom-read race). This advisory lock is per-room and is
     * held for the lifetime of the enclosing transaction, so it fully serializes booking
     * writes for that room and closes the race. Must be called inside DB::transaction().
     *
     * Postgres-only (production/docker always run Postgres, see docker-compose.yml).
     * No-op on other drivers ONLY in local/testing (e.g. sqlite in tests, which lack
     * advisory locks) — anywhere else, a non-pgsql driver means the double-booking
     * race is silently unprotected, so we fail loudly instead of pretending it's safe.
     */
    public function lockRoom(string $roomId): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('SELECT pg_advisory_xact_lock(hashtext(?))', [$roomId]);

            return;
        }

        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException(
                "Booking advisory lock membutuhkan driver 'pgsql' untuk mencegah race condition double-booking, ".
                "tapi koneksi saat ini pakai driver '{$driver}'. Set DB_CONNECTION=pgsql sebelum menjalankan di lingkungan ini."
            );
        }
    }

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

    public function getUserBookings(string $userId, ?string $status = null, int $page = 1, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Booking::with(['room:id,name,slug', 'approval'])
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
            'start' => $booking->booking_date.'T'.$booking->start_time,
            'end' => $booking->booking_date.'T'.$booking->end_time,
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
