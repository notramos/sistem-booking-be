<?php

namespace App\Repositories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\MaintenanceSchedule;
use App\Models\Room;
use App\Support\Pagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class RoomRepository
{
    private function getCacheVersion(): int
    {
        return (int) Cache::store('database')->get('room-availability-version', 1);
    }

    /**
     * Ruangan yang TIDAK bisa dipesan untuk slot ini — sedang dibooking (pending/
     * approved) ATAU sedang dalam jadwal perbaikan yang tumpang tindih. Dulu cuma
     * mengecek booking (ruangan dalam maintenance tetap muncul "tersedia" di listing,
     * padahal BookingService::create() akan menolaknya) — sekarang keduanya konsisten.
     */
    private function getUnavailableRoomIds(string $date, string $startTime, string $endTime): Collection
    {
        $version = $this->getCacheVersion();
        $key = "room:unavailable:v{$version}:{$date}:{$startTime}:{$endTime}";

        return Cache::store('database')->remember($key, 300, function () use ($date, $startTime, $endTime) {
            $bookedIds = Booking::where('booking_date', $date)
                ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::APPROVED->value])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->pluck('room_id');

            $maintenanceIds = MaintenanceSchedule::where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('is_all_day', true)
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<', $endTime)
                                ->where('end_time', '>', $startTime);
                        });
                })
                ->pluck('room_id');

            return $bookedIds->merge($maintenanceIds)->unique()->values();
        });
    }

    public function clearAvailabilityCache(): void
    {
        Cache::store('database')->increment('room-availability-version');
    }

    public function findOrFail(string $id): Room
    {
        return Room::findOrFail($id);
    }

    public function findWithRelations(string $id): Room
    {
        return Room::with(['category', 'facilities', 'images', 'bookings' => function ($q) {
            $q->orderBy('booking_date', 'desc')->orderBy('start_time', 'desc');
        }])->findOrFail($id);
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(string $id, array $data): Room
    {
        $room = $this->findOrFail($id);
        $room->update($data);

        return $room;
    }

    public function delete(string $id): void
    {
        $room = $this->findOrFail($id);
        $room->delete();
    }

    public function getFilteredRooms(array $filters = []): LengthAwarePaginator
    {
        $query = Room::with(['category', 'primaryImage', 'facilities']);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['capacity'])) {
            $query->byCapacity((int) $filters['capacity']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['building'])) {
            $query->where('building', 'ilike', "%{$filters['building']}%");
        }

        $availableOnly = ! empty($filters['available_only']) && $filters['available_only'];
        if ($availableOnly && ! empty($filters['date']) && ! empty($filters['start_time']) && ! empty($filters['end_time'])) {
            $unavailableIds = $this->getUnavailableRoomIds($filters['date'], $filters['start_time'], $filters['end_time']);
            $query->whereNotIn('id', $unavailableIds);
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = Pagination::perPage($filters['per_page'] ?? null, 12);

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    public function getAvailableRooms(string $date, string $startTime, string $endTime): Collection
    {
        $unavailableIds = $this->getUnavailableRoomIds($date, $startTime, $endTime);

        return Room::with(['category', 'primaryImage', 'facilities'])
            ->where('is_active', true)
            ->where('status', 'available')
            ->whereNotIn('id', $unavailableIds)
            ->get();
    }

    /**
     * Ruangan aktif & tersedia yang kapasitasnya cukup untuk jumlah peserta,
     * diurutkan dari kapasitas terkecil (best-fit) agar rekomendasi teratas
     * adalah ruangan paling pas.
     */
    public function getRoomsByCapacity(int $attendees): Collection
    {
        return Room::with(['category', 'primaryImage', 'facilities'])
            ->where('is_active', true)
            ->where('status', 'available')
            ->byCapacity($attendees)
            ->orderBy('capacity', 'asc')
            ->get();
    }

    /**
     * Booking aktif (pending/approved) satu ruangan pada satu tanggal,
     * diurutkan menurut jam mulai. Dipakai untuk membangun timeline
     * jam terpesan vs tersedia.
     */
    public function getDayBookedSlots(string $roomId, string $date): Collection
    {
        return Booking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::APPROVED->value])
            ->orderBy('start_time')
            ->get(['id', 'title', 'start_time', 'end_time', 'status']);
    }

    /**
     * Booking aktif untuk banyak ruangan pada satu tanggal, dikelompokkan per
     * room_id — satu query untuk menghindari N+1 saat menghitung ketersediaan
     * daftar rekomendasi.
     *
     * @param  array<int, string>  $roomIds
     * @return \Illuminate\Support\Collection<string, Collection>
     */
    public function getDayBookedSlotsForRooms(array $roomIds, string $date): \Illuminate\Support\Collection
    {
        if (empty($roomIds)) {
            return collect();
        }

        return Booking::whereIn('room_id', $roomIds)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::APPROVED->value])
            ->orderBy('start_time')
            ->get(['id', 'room_id', 'title', 'start_time', 'end_time', 'status'])
            ->groupBy('room_id');
    }
}
