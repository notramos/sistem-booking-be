<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        // Semua user login boleh melihat detail booking siapa pun (kalender sudah
        // menampilkan booking semua orang) — yang dibatasi cuma AKSI-nya (lihat
        // update/cancel/approve di bawah), bukan visibilitas.
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        $isOwner = $user->id === $booking->user_id;
        $ownerEditableStatuses = [
            BookingStatus::PENDING->value,
            BookingStatus::REVISION_SEKRETARIAT->value,
            BookingStatus::REVISION_ADMIN->value,
        ];

        if ($isOwner && in_array($booking->status, $ownerEditableStatuses)) {
            return true;
        }

        return $user->hasRole('sekretariat') && $booking->status === BookingStatus::SEKRETARIAT_REVIEW->value;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id
            || $user->hasAnyRole(['p2', 'pastor', 'it_admin', 'sekretariat']);
    }

    public function approve(User $user): bool
    {
        return $user->hasAnyRole(['sekretariat', 'p2', 'pastor', 'it_admin']);
    }
}
