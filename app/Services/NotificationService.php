<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CongregationService;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingCreated;
use App\Notifications\BookingForwardedToAdmin;
use App\Notifications\BookingMovedToAdminReview;
use App\Notifications\BookingRejected;
use App\Notifications\CongregationServiceApproved;
use App\Notifications\CongregationServiceRejected;
use App\Notifications\RecurringBookingCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotificationService
{
    public function bookingCreated(Booking $booking): void
    {
        $this->safely(function () use ($booking) {
            $sekretariat = User::role('sekretariat')->get();
            Notification::send($sekretariat, new BookingCreated($booking));
        });
    }

    public function bookingApproved(Booking $booking): void
    {
        $this->safely(fn () => $booking->user->notify(new BookingApproved($booking)));
    }

    public function bookingRejected(Booking $booking): void
    {
        $this->safely(fn () => $booking->user->notify(new BookingRejected($booking)));
    }

    public function recurringBookingCreated(Booking $booking, int $skippedCount): void
    {
        $this->safely(function () use ($booking, $skippedCount) {
            $sekretariat = User::role('sekretariat')->get();
            Notification::send($sekretariat, new RecurringBookingCreated($booking, $skippedCount));
        });
    }

    /**
     * Sekretariat menyetujui tahap pertama: admin diminta bertindak, dan pemohon
     * diberi kabar kemajuan supaya tidak merasa pengajuannya mengambang.
     */
    public function bookingMovedToAdminReview(Booking $booking): void
    {
        $this->safely(function () use ($booking) {
            $admins = User::role(['p2', 'pastor', 'it_admin'])->get();
            Notification::send($admins, new BookingMovedToAdminReview($booking));
        });

        $this->safely(fn () => $booking->user->notify(new BookingForwardedToAdmin($booking)));
    }

    public function congregationServiceApproved(CongregationService $service): void
    {
        $this->safely(fn () => $service->user?->notify(new CongregationServiceApproved($service)));
    }

    public function congregationServiceRejected(CongregationService $service): void
    {
        $this->safely(fn () => $service->user?->notify(new CongregationServiceRejected($service)));
    }

    public function bookingCancelled(Booking $booking): void
    {
        $this->safely(function () use ($booking) {
            $approval = $booking->adminApproval ?? $booking->sekretariatApproval;

            if ($approval && $approval->approver_id) {
                $approver = User::find($approval->approver_id);
                if ($approver) {
                    $approver->notify(new BookingCancelled($booking));
                }
            }
        });
    }

    /**
     * Kegagalan kirim notifikasi (mis. provider mail/WA down atau kena rate limit)
     * tidak boleh menggagalkan booking-nya — panggilan ini selalu di dalam
     * DB::transaction() di BookingService/ApprovalService, jadi exception yang
     * lolos dari sini akan rollback booking yang sebetulnya valid.
     */
    private function safely(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi', ['exception' => $e]);
        }
    }
}
