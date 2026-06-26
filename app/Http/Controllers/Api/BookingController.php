<?php

namespace App\Http\Controllers\Api;

use App\DTOs\BookingDTO;
use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Services\BookingService;
use App\Services\CalendarService;
use App\DTOs\CalendarFilterDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private BookingService $bookingService,
        private CalendarService $calendarService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = \App\Models\Booking::with(['user:id,name', 'room:id,name,slug'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->room_id, fn($q, $r) => $q->where('room_id', $r))
            ->when($request->date, fn($q, $d) => $q->where('booking_date', $d))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->paginated($bookings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'purpose_type' => 'nullable|in:ibadah,acara_keluarga,latihan_musik,pembinaan,rapat,seminar,publik',
            'expected_attendees' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $dto = new BookingDTO(
            roomId: $validated['room_id'],
            title: $validated['title'],
            bookingDate: $validated['booking_date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            description: $validated['description'] ?? null,
            purposeType: $validated['purpose_type'] ?? null,
            expectedAttendees: $validated['expected_attendees'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        $booking = $this->bookingService->create($dto);

        return $this->created(
            $booking->load(['room:id,name,slug', 'user:id,name']),
            'Booking berhasil dibuat dan menunggu persetujuan'
        );
    }

    public function show(string $id): JsonResponse
    {
        $booking = \App\Models\Booking::with([
            'user', 'room.category', 'room.facilities', 'room.images',
            'approval.approver:id,name', 'logs.user:id,name',
        ])->findOrFail($id);

        $this->authorize('view', $booking);

        return $this->success($booking);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $booking = \App\Models\Booking::findOrFail($id);

        if (!$booking->isPending()) {
            return $this->error('Booking tidak dapat diubah karena sudah diproses', 422);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
        ]);

        $booking->update($validated);

        return $this->success($booking, 'Booking berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $booking = \App\Models\Booking::findOrFail($id);
        $this->authorize('cancel', $booking);

        $this->bookingService->cancel($id);

        return $this->success(null, 'Booking berhasil dibatalkan');
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->getUserBookings($request->status);
        return $this->success($bookings);
    }

    public function calendar(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $dto = new CalendarFilterDTO(
            start: $request->start,
            end: $request->end,
            roomId: $request->room_id,
        );

        $events = $this->calendarService->getEvents($dto);

        return $this->success($events);
    }

    public function pending(): JsonResponse
    {
        $bookings = app(\App\Services\ApprovalService::class)->getPendingBookings();
        return $this->paginated($bookings);
    }
}
