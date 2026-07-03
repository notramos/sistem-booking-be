<?php

namespace App\Http\Controllers\Api;

use App\DTOs\BookingDTO;
use App\DTOs\CalendarFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Http\Requests\Api\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Response\ApiResponse;
use App\Models\Booking;
use App\Services\ApprovalService;
use App\Services\BookingService;
use App\Services\CalendarService;
use Carbon\Carbon;
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
        $user = $request->user();
        $isPrivileged = $user->hasRole('admin') || $user->hasRole('sekretariat');

        $bookings = Booking::with(['user:id,name', 'room:id,name,slug'])
            ->when(! $isPrivileged, fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->room_id, fn ($q, $r) => $q->where('room_id', $r))
            ->when($request->date, fn ($q, $d) => $q->where('booking_date', $d))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->paginated(BookingResource::collection($bookings));
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
            new BookingResource($booking->load(['room:id,name,slug', 'user:id,name'])),
            'Booking berhasil dibuat dan menunggu persetujuan'
        );
    }

    public function show(string $id): JsonResponse
    {
        $booking = Booking::with([
            'user', 'room.category', 'room.facilities', 'room.images',
            'approval.approver:id,name', 'logs.user:id,name',
        ])->findOrFail($id);

        $this->authorize('view', $booking);

        return $this->success(new BookingResource($booking));
    }

    public function update(UpdateBookingRequest $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('update', $booking);

        if (! $booking->isPending()) {
            return $this->error('Booking tidak dapat diubah karena sudah diproses', 422);
        }

        $booking = $this->bookingService->updateTime($id, $request->validated());

        return $this->success(new BookingResource($booking), 'Booking berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('cancel', $booking);

        $this->bookingService->cancel($id);

        return $this->success(null, 'Booking berhasil dibatalkan');
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->getUserBookings($request->status);

        return $this->success(BookingResource::collection($bookings));
    }

    public function calendar(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);

        if ($end->diffInDays($start) > 365) {
            return $this->error('Rentang tanggal maksimal 1 tahun', 422);
        }

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
        $bookings = app(ApprovalService::class)->getPendingBookings();

        return $this->paginated(BookingResource::collection($bookings));
    }
}
