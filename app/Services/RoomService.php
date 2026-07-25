<?php

namespace App\Services;

use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Models\RoomImage;
use App\Repositories\RoomRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoomService
{
    public function __construct(
        private RoomRepository $roomRepo,
        private AuditService $auditService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->roomRepo->getFilteredRooms($filters);
    }

    public function find(string $id): Room
    {
        return $this->roomRepo->findWithRelations($id);
    }

    public function create(array $data): Room
    {
        return DB::transaction(function () use ($data) {
            $facilities = $data['facilities'] ?? [];
            unset($data['facilities']);

            $room = $this->roomRepo->create($data);

            if (!empty($facilities)) {
                $room->facilities()->sync($facilities);
            }

            $this->auditService->log('room.created', $room);

            return $room;
        });
    }

    public function update(string $id, array $data): Room
    {
        return DB::transaction(function () use ($id, $data) {
            $facilities = $data['facilities'] ?? null;
            unset($data['facilities']);

            $room = $this->roomRepo->update($id, $data);

            if ($facilities !== null) {
                $room->facilities()->sync($facilities);
            }

            $this->auditService->log('room.updated', $room);

            return $room;
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            $room = $this->roomRepo->findOrFail($id);
            $this->roomRepo->delete($id);
            $this->auditService->log('room.deleted', $room);
        });
    }

    /**
     * Rekomendasi ruangan untuk suatu tanggal berdasarkan jumlah peserta.
     * Ruangan yang kapasitasnya cukup diambil (best-fit terkecil dulu), lalu
     * ketersediaan hari itu dihitung dengan SATU query booking bulk (anti N+1).
     * Urutan: yang masih punya slot bebas didahulukan, lalu kapasitas terkecil —
     * sehingga "ruangan paling pas yang masih tersedia" ada di paling atas.
     * Hasil akhir cuma menyisakan ruangan pada tingkat kapasitas TERDEKAT itu saja
     * (bisa lebih dari satu kalau ada beberapa ruangan dengan kapasitas persis
     * sama) — bukan daftar semua ruangan yang muat, supaya user langsung diarahkan
     * ke pilihan paling pas alih-alih harus memilah dari daftar panjang.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recommendRooms(string $date, int $attendees): array
    {
        $rooms = $this->roomRepo->getRoomsByCapacity($attendees);
        $bookedByRoom = $this->roomRepo->getDayBookedSlotsForRooms($rooms->pluck('id')->all(), $date);
        $hours = config('booking.operating_hours');

        $sorted = $rooms
            ->map(function (Room $room) use ($bookedByRoom, $hours) {
                $booked = $bookedByRoom->get($room->id) ?? collect();
                $freeSlots = $this->computeFreeSlots($booked, $hours['open'], $hours['close']);
                $hasFree = ! empty($freeSlots);

                return [
                    'room' => $room,
                    'capacity' => $room->capacity,
                    'fits' => true,
                    'has_free_slot' => $hasFree,
                    'fully_booked' => ! $hasFree,
                    'booked_slots' => $this->mapBookedSlots($booked),
                    'free_slots' => $freeSlots,
                ];
            })
            ->sort(fn ($a, $b) => [$a['has_free_slot'] ? 0 : 1, $a['capacity']]
                <=> [$b['has_free_slot'] ? 0 : 1, $b['capacity']])
            ->values();

        $closestCapacity = $sorted->first()['capacity'] ?? null;
        $final = $closestCapacity === null
            ? $sorted
            : $sorted->filter(fn (array $item) => $item['capacity'] === $closestCapacity);

        return $final
            ->map(fn (array $item) => [
                'room' => new RoomResource($item['room']),
                'fits' => $item['fits'],
                'has_free_slot' => $item['has_free_slot'],
                'fully_booked' => $item['fully_booked'],
                'booked_slots' => $item['booked_slots'],
                'free_slots' => $item['free_slots'],
            ])
            ->values()
            ->all();
    }

    /**
     * Ketersediaan satu ruangan pada satu tanggal: daftar jam terpesan dan
     * daftar slot bebas dalam jam operasional.
     *
     * @return array<string, mixed>
     */
    public function getDayAvailability(string $roomId, string $date): array
    {
        $this->roomRepo->findOrFail($roomId); // memastikan 404 bila ruangan tak ada
        $hours = config('booking.operating_hours');
        $booked = $this->roomRepo->getDayBookedSlots($roomId, $date);

        return [
            'room_id' => $roomId,
            'date' => $date,
            'operating_hours' => $hours,
            'booked_slots' => $this->mapBookedSlots($booked),
            'free_slots' => $this->computeFreeSlots($booked, $hours['open'], $hours['close']),
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function mapBookedSlots(Collection $booked): array
    {
        return $booked->map(fn ($b) => [
            'start_time' => substr((string) $b->start_time, 0, 5),
            'end_time' => substr((string) $b->end_time, 0, 5),
            'title' => $b->title,
            'status' => $b->status,
        ])->values()->all();
    }

    /**
     * Komplemen dari interval terpesan dalam [open, close]: gabungkan interval
     * yang beririsan, lalu ambil celah bebas. Celah lebih pendek dari
     * min_duration_minutes tidak dianggap slot yang dapat dipesan.
     *
     * @return array<int, array<string, string>>
     */
    private function computeFreeSlots(Collection $booked, string $open, string $close): array
    {
        $step = (int) config('booking.min_duration_minutes', 30);
        $openMin = $this->toMinutes($open);
        $closeMin = $this->toMinutes($close);

        $intervals = $booked
            ->map(fn ($b) => [
                max($openMin, $this->toMinutes(substr((string) $b->start_time, 0, 5))),
                min($closeMin, $this->toMinutes(substr((string) $b->end_time, 0, 5))),
            ])
            ->filter(fn ($iv) => $iv[0] < $iv[1])
            ->sortBy(0)
            ->values()
            ->all();

        $merged = [];
        foreach ($intervals as $iv) {
            $last = count($merged) - 1;
            if ($last < 0 || $iv[0] > $merged[$last][1]) {
                $merged[] = $iv;
            } else {
                $merged[$last][1] = max($merged[$last][1], $iv[1]);
            }
        }

        $free = [];
        $cursor = $openMin;
        foreach ($merged as $iv) {
            if ($iv[0] - $cursor >= $step) {
                $free[] = ['start_time' => $this->fromMinutes($cursor), 'end_time' => $this->fromMinutes($iv[0])];
            }
            $cursor = max($cursor, $iv[1]);
        }
        if ($closeMin - $cursor >= $step) {
            $free[] = ['start_time' => $this->fromMinutes($cursor), 'end_time' => $this->fromMinutes($closeMin)];
        }

        return $free;
    }

    private function toMinutes(string $hm): int
    {
        [$h, $m] = array_map('intval', array_pad(explode(':', $hm), 2, 0));

        return $h * 60 + $m;
    }

    private function fromMinutes(int $min): string
    {
        return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
    }

    public function uploadImage(string $roomId, UploadedFile $image, bool $isPrimary = false): RoomImage
    {
        return DB::transaction(function () use ($roomId, $image, $isPrimary) {
            $room = $this->roomRepo->findOrFail($roomId);
            $path = $image->store('rooms', 'public');

            if ($isPrimary) {
                $room->images()->update(['is_primary' => false]);
            }

            $sortOrder = $room->images()->max('sort_order') + 1;

            $roomImage = $room->images()->create([
                'image_path' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => $sortOrder,
            ]);

            $this->auditService->log('room.image_uploaded', $roomImage);

            return $roomImage;
        });
    }
}
