<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomImage;
use App\Repositories\RoomRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
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
