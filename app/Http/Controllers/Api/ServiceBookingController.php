<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Enums\BookingStatus;
use App\Services\NotificationService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceBookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private NotificationService $notificationService,
        private AuditService $auditService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'service_type' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
            'equipment' => 'nullable|array',
            'equipment.*' => 'string',
            'other_equipment' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'dynamic_fields' => 'nullable|array',
        ]);

        $equipment = $validated['equipment'] ?? [];
        if (!empty($validated['other_equipment'])) {
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
                'title' => 'Pelayanan: ' . $typeLabel,
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

        return $this->created($booking, 'Permohonan pelayanan gereja berhasil dikirim');
    }
}
