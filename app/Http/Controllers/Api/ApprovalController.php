<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApproveActionRequest;
use App\Http\Requests\Api\RejectActionRequest;
use App\Http\Resources\BookingResource;
use App\Http\Response\ApiResponse;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;

class ApprovalController extends Controller
{
    use ApiResponse;

    public function __construct(private ApprovalService $approvalService) {}

    public function approve(ApproveActionRequest $request, string $bookingId): JsonResponse
    {
        $booking = $this->approvalService->approve(
            bookingId: $bookingId,
            approverId: auth()->id(),
            notes: $request->notes,
        );

        return $this->success(
            new BookingResource($booking->load(['user:id,name,email', 'room:id,name'])),
            'Booking berhasil disetujui'
        );
    }

    public function reject(RejectActionRequest $request, string $bookingId): JsonResponse
    {
        $booking = $this->approvalService->reject(
            bookingId: $bookingId,
            approverId: auth()->id(),
            reason: $request->reason,
        );

        return $this->success(
            new BookingResource($booking->load(['user:id,name,email', 'room:id,name'])),
            'Booking ditolak'
        );
    }
}
