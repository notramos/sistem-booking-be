<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    use ApiResponse;

    public function __construct(private ApprovalService $approvalService) {}

    public function approve(Request $request, string $bookingId): JsonResponse
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        $booking = $this->approvalService->approve(
            bookingId: $bookingId,
            approverId: auth()->id(),
            notes: $request->notes,
        );

        return $this->success(
            $booking->load(['user:id,name,email', 'room:id,name']),
            'Booking berhasil disetujui'
        );
    }

    public function reject(Request $request, string $bookingId): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $booking = $this->approvalService->reject(
            bookingId: $bookingId,
            approverId: auth()->id(),
            reason: $request->reason,
        );

        return $this->success(
            $booking->load(['user:id,name,email', 'room:id,name']),
            'Booking ditolak'
        );
    }
}
