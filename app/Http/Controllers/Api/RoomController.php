<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ApiResponse;

    public function __construct(private RoomService $roomService) {}

    public function index(Request $request): JsonResponse
    {
        $rooms = $this->roomService->list($request->all());
        return $this->paginated($rooms);
    }

    public function show(string $id): JsonResponse
    {
        $room = $this->roomService->find($id);
        return $this->success($room);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:room_categories,id',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'floor' => 'nullable|string|max:50',
            'building' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,maintenance,unavailable',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:room_facilities,id',
        ]);

        $room = $this->roomService->create($validated);
        return $this->created($room->load(['category', 'facilities']), 'Ruangan berhasil dibuat');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:room_categories,id',
            'description' => 'nullable|string',
            'capacity' => 'sometimes|integer|min:1',
            'floor' => 'nullable|string|max:50',
            'building' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,maintenance,unavailable',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:room_facilities,id',
        ]);

        $room = $this->roomService->update($id, $validated);
        return $this->success($room->load(['category', 'facilities']), 'Ruangan berhasil diperbarui');
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

    public function availability(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $hasConflict = app(\App\Repositories\BookingRepository::class)->hasConflict(
            roomId: $id,
            date: $request->date,
            startTime: $request->start_time,
            endTime: $request->end_time,
        );

        return $this->success([
            'available' => !$hasConflict,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);
    }

    public function uploadImage(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_primary' => 'boolean',
        ]);

        $roomImage = $this->roomService->uploadImage(
            roomId: $id,
            image: $request->file('image'),
            isPrimary: $request->boolean('is_primary')
        );

        return $this->created($roomImage, 'Gambar berhasil diunggah');
    }

    public function deleteImage(string $roomId, string $imageId): JsonResponse
    {
        $image = \App\Models\RoomImage::where('room_id', $roomId)->findOrFail($imageId);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return $this->success(null, 'Gambar berhasil dihapus');
    }

    public function setPrimaryImage(string $roomId, string $imageId): JsonResponse
    {
        \App\Models\RoomImage::where('room_id', $roomId)->update(['is_primary' => false]);
        \App\Models\RoomImage::where('room_id', $roomId)->where('id', $imageId)->update(['is_primary' => true]);

        return $this->success(null, 'Gambar utama berhasil diatur');
    }
}
