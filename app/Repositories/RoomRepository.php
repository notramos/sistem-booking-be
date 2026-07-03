<?php

namespace App\Repositories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class RoomRepository
{
    private function getCacheVersion(): int
    {
        return (int) Cache::store('database')->get('room-availability-version', 1);
    }

    private function getBookedRoomIds(string $date, string $startTime, string $endTime): Collection
    {
        $version = $this->getCacheVersion();
        $key = "room:booked:v{$version}:{$date}:{$startTime}:{$endTime}";

        return Cache::store('database')->remember($key, 300, function () use ($date, $startTime, $endTime) {
            return Booking::where('booking_date', $date)
                ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::APPROVED->value])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->pluck('room_id');
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
            $bookedIds = $this->getBookedRoomIds($filters['date'], $filters['start_time'], $filters['end_time']);
            $query->whereNotIn('id', $bookedIds);
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 12;

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    public function getAvailableRooms(string $date, string $startTime, string $endTime): Collection
    {
        $bookedIds = $this->getBookedRoomIds($date, $startTime, $endTime);

        return Room::with(['category', 'primaryImage', 'facilities'])
            ->where('is_active', true)
            ->where('status', 'available')
            ->whereNotIn('id', $bookedIds)
            ->get();
    }
}
