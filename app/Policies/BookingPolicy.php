<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id
            || $user->hasAnyRole(['admin', 'sekretariat']);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id && $booking->isPending();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id
            || $user->hasAnyRole(['admin', 'sekretariat']);
    }

    public function approve(User $user): bool
    {
        return $user->hasAnyRole(['sekretariat', 'admin']);
    }
}
