<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Response\ApiResponse;
use App\Models\Booking;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ServiceBookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private NotificationService $notificationService,
        private AuditService $auditService,
    ) {}

    public function store(StoreServiceBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $equipment = $validated['equipment'] ?? [];
        if (! empty($validated['other_equipment'])) {
            $equipment[] = $validated['other_equipment'];
        }

        $serviceTypeLabels = [
            'ibadah_minggu' => 'Ibadah Minggu',
            'pernikahan' => 'Pernikahan',
            'baptisan' => 'Baptisan',
            'pemakaman' => 'Pemakaman',
            'natal' => 'Natal',
            'paskah' => 'Paskah',
            'syukuran' => 'Syukuran',
            'lainnya' => 'Lainnya',
        ];

        $booking = DB::transaction(function () use ($validated, $equipment, $serviceTypeLabels) {
            $typeLabel = $serviceTypeLabels[$validated['service_type']] ?? $validated['service_type'];

            $booking = Booking::create([
                'user_id' => auth()->id(),
                'room_id' => $validated['room_id'],
                'title' => 'Pelayanan: '.$typeLabel,
                'description' => null,
                'booking_date' => $validated['booking_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'purpose_type' => 'ibadah',
                'expected_attendees' => null,
                'contact_person' => $validated['contact'],
                'status' => BookingStatus::PENDING->value,
                'notes' => $validated['notes'],
                'service_details' => [
                    'service_type' => $validated['service_type'],
                    'service_type_label' => $typeLabel,
                    'contact' => $validated['contact'],
                    'equipment' => $equipment,
                    'dynamic_fields' => $validated['dynamic_fields'] ?? [],
                ],
            ]);

            $booking->load(['room:id,name,slug', 'user:id,name']);

            $this->auditService->log('service_booking.created', $booking);
            $this->notificationService->bookingCreated($booking);

            return $booking;
        });

        return $this->created(new BookingResource($booking), 'Permohonan pelayanan gereja berhasil dikirim');
    }
}
