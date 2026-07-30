<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApproveActionRequest;
use App\Http\Requests\Api\RejectActionRequest;
use App\Http\Resources\BookingResource;
use App\Http\Response\ApiResponse;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    use ApiResponse;

    public function __construct(private ApprovalService $approvalService) {}

    public function startReview(Request $request, string $bookingId): JsonResponse
    {
        $booking = $this->approvalService->startReview($bookingId);

        return $this->success(
            new BookingResource($booking->load(['user:id,name,email', 'room:id,name'])),
            'Review dimulai'
        );
    }

    public function approve(ApproveActionRequest $request, string $bookingId): JsonResponse
    {
        $booking = $this->approvalService->approve(
            bookingId: $bookingId,
            approver: $request->user(),
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
            approver: $request->user(),
            reason: $request->reason,
        );

        return $this->success(
            new BookingResource($booking->load(['user:id,name,email', 'room:id,name'])),
            'Booking ditolak'
        );
    }
}
