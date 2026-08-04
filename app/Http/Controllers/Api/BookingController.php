<?php

namespace App\Http\Controllers\Api;

use App\DTOs\BookingDTO;
use App\DTOs\CalendarFilterDTO;
use App\DTOs\ManualBookingDTO;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\DTOs\RecurringBookingDTO;
use App\DTOs\RecurringPreviewDTO;
use App\Http\Requests\Api\PreviewRecurringBookingRequest;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Http\Requests\Api\StoreManualBookingRequest;
use App\Http\Requests\Api\StoreRecurringBookingRequest;
use App\Http\Requests\Api\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Response\ApiResponse;
use App\Models\Booking;
use App\Services\ApprovalService;
use App\Services\BookingService;
use App\Services\CalendarService;
use App\Support\Pagination;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private BookingService $bookingService,
        private CalendarService $calendarService,
        private ApprovalService $approvalService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isPrivileged = $user->hasAnyRole(['p2', 'pastor', 'it_admin', 'sekretariat']);

        $bookings = Booking::with(['user:id,name', 'room:id,name,slug'])
            ->when(! $isPrivileged, fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->room_id, fn ($q, $r) => $q->where('room_id', $r))
            ->when($request->date, fn ($q, $d) => $q->where('booking_date', $d))
            ->when($request->date_from, fn ($q, $d) => $q->where('booking_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('booking_date', '<=', $d))
            ->orderBy('created_at', 'desc')
            ->paginate(Pagination::perPage($request->per_page, 15));

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

    public function storeManual(StoreManualBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new ManualBookingDTO(
            userId: $request->user()->id,
            roomId: $validated['room_id'],
            title: $validated['title'],
            bookingDate: $validated['booking_date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            status: $validated['status'],
            description: $validated['description'] ?? null,
            contactPerson: $validated['contact_person'] ?? null,
            expectedAttendees: $validated['expected_attendees'] ?? null,
        );

        $booking = $this->bookingService->createManual($dto);

        return $this->created(
            new BookingResource($booking->load(['room:id,name,slug', 'user:id,name'])),
            'Booking manual berhasil ditambahkan'
        );
    }

    public function previewRecurring(PreviewRecurringBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new RecurringPreviewDTO(
            roomId: $validated['room_id'],
            firstDate: $validated['first_date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            pattern: $validated['pattern'],
            durationMonths: $validated['duration_months'],
        );

        return $this->success(['dates' => $this->bookingService->previewRecurring($dto)]);
    }

    public function storeRecurring(StoreRecurringBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new RecurringBookingDTO(
            roomId: $validated['room_id'],
            title: $validated['title'],
            dates: $validated['dates'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            pattern: $validated['pattern'],
            description: $validated['description'] ?? null,
            purposeType: $validated['purpose_type'] ?? null,
            expectedAttendees: $validated['expected_attendees'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        $result = $this->bookingService->createRecurring($dto);

        return $this->created([
            'booking' => new BookingResource($result->booking->load(['room:id,name,slug', 'user:id,name'])),
            'skipped' => $result->skipped,
            'skipped_count' => count($result->skipped),
        ], 'Jadwal rutin berhasil diajukan');
    }

    public function show(string $id): JsonResponse
    {
        $booking = Booking::with([
            'user', 'room.category', 'room.facilities', 'room.images',
            'approvals.approver:id,name', 'logs.user:id,name',
        ])->findOrFail($id);

        $this->authorize('view', $booking);

        return $this->success(new BookingResource($booking));
    }

    public function update(UpdateBookingRequest $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('update', $booking);

        $user = $request->user();
        $isOwner = $user->id === $booking->user_id;
        $isResubmit = $isOwner && in_array($booking->status, [
            BookingStatus::REVISION_SEKRETARIAT->value,
            BookingStatus::REVISION_ADMIN->value,
        ]);

        if ($isResubmit) {
            $booking = $this->approvalService->resubmit($id, $user, $request->validated());

            return $this->success(new BookingResource($booking), 'Booking berhasil diperbarui dan diajukan ulang');
        }

        $isStaffRealloc = $user->hasRole('sekretariat') && $booking->status === BookingStatus::SEKRETARIAT_REVIEW->value;
        $isOwnerEditableStatus = $isOwner && in_array($booking->status, [
            BookingStatus::PENDING->value,
            BookingStatus::SEKRETARIAT_REVIEW->value,
        ]);

        if (! $isStaffRealloc && ! $isOwnerEditableStatus) {
            return $this->error('Booking tidak dapat diubah karena sudah diproses', 422);
        }

        $canChangeRoomOrDate = $isStaffRealloc || $isOwnerEditableStatus;
        $validated = $request->validated();
        if (! $canChangeRoomOrDate && (isset($validated['room_id']) || isset($validated['booking_date']))) {
            return $this->error('Anda tidak berhak mengubah ruangan/tanggal booking ini', 422);
        }

        $booking = $this->bookingService->updateTime($id, $validated);

        return $this->success(new BookingResource($booking), 'Booking berhasil diperbarui');
    }

    public function updateRecurringDate(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('editRecurringDate', $booking);

        $validated = $request->validate([
            'old_date' => 'required|date_format:Y-m-d',
            'new_date' => 'required|date_format:Y-m-d',
        ]);

        $booking = $this->bookingService->updateRecurringDate($id, $validated['old_date'], $validated['new_date']);

        return $this->success(new BookingResource($booking->load(['user', 'room', 'approvals', 'logs.user'])), 'Tanggal booking rutin berhasil diubah');
    }

    public function deleteRecurringDate(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('editRecurringDate', $booking);

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $booking = $this->bookingService->deleteRecurringDate($id, $validated['date']);

        return $this->success(new BookingResource($booking->load(['user', 'room', 'approvals', 'logs.user'])), 'Tanggal booking rutin berhasil dihapus');
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
        $bookings = $this->bookingService->getUserBookings(
            $request->status,
            $request->integer('page') ?: 1,
            $request->search,
            Pagination::perPage($request->per_page, 10),
        );

        return $this->paginated(BookingResource::collection($bookings));
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

    public function pending(Request $request): JsonResponse
    {
        $bookings = $this->approvalService->getPendingBookings($request->user());

        return $this->paginated(BookingResource::collection($bookings));
    }
}
