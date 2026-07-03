<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RoomAvailabilityRequest;
use App\Http\Requests\Api\StoreRoomRequest;
use App\Http\Requests\Api\UpdateRoomRequest;
use App\Http\Requests\Api\UploadRoomImageRequest;
use App\Http\Resources\RoomImageResource;
use App\Http\Resources\RoomResource;
use App\Http\Response\ApiResponse;
use App\Models\RoomImage;
use App\Repositories\BookingRepository;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    use ApiResponse;

    public function __construct(private RoomService $roomService) {}

    public function index(Request $request): JsonResponse
    {
        $rooms = $this->roomService->list($request->all());

        return $this->paginated(RoomResource::collection($rooms));
    }

    public function show(string $id): JsonResponse
    {
        $room = $this->roomService->find($id);

        return $this->success(new RoomResource($room));
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create($request->validated());

        return $this->created(new RoomResource($room->load(['category', 'facilities'])), 'Ruangan berhasil dibuat');
    }

    public function update(UpdateRoomRequest $request, string $id): JsonResponse
    {
        $room = $this->roomService->update($id, $request->validated());

        return $this->success(new RoomResource($room->load(['category', 'facilities'])), 'Ruangan berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->roomService->delete($id);

        return $this->success(null, 'Ruangan berhasil dihapus');
    }

    public function schedules(string $id, Request $request): JsonResponse
    {
        $room = $this->roomService->find($id);
        $startDate = $request->get('start', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end', now()->endOfMonth()->toDateString());

        $bookings = $room->activeBookings()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->get(['id', 'title', 'booking_date', 'start_time', 'end_time', 'status']);

        return $this->success($bookings);
    }

    public function availability(string $id, RoomAvailabilityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $hasConflict = app(BookingRepository::class)->hasConflict(
            roomId: $id,
            date: $validated['date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
        );

        return $this->success([
            'available' => ! $hasConflict,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);
    }

    public function uploadImage(UploadRoomImageRequest $request, string $id): JsonResponse
    {
        $roomImage = $this->roomService->uploadImage(
            roomId: $id,
            image: $request->file('image'),
            isPrimary: $request->boolean('is_primary')
        );

        return $this->created(new RoomImageResource($roomImage), 'Gambar berhasil diunggah');
    }

    public function deleteImage(string $roomId, string $imageId): JsonResponse
    {
        $image = RoomImage::where('room_id', $roomId)->findOrFail($imageId);
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return $this->success(null, 'Gambar berhasil dihapus');
    }

    public function setPrimaryImage(string $roomId, string $imageId): JsonResponse
    {
        RoomImage::where('room_id', $roomId)->update(['is_primary' => false]);
        RoomImage::where('room_id', $roomId)->where('id', $imageId)->update(['is_primary' => true]);

        return $this->success(null, 'Gambar utama berhasil diatur');
    }
}
