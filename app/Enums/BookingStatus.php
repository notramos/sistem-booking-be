<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case SEKRETARIAT_REVIEW = 'sekretariat_review';
    case REVISION_SEKRETARIAT = 'revision_sekretariat';
    case ADMIN_REVIEW = 'admin_review';
    case REVISION_ADMIN = 'revision_admin';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Review',
            self::SEKRETARIAT_REVIEW => 'Sedang Direview Sekretariat',
            self::REVISION_SEKRETARIAT => 'Revisi (Sekretariat)',
            self::ADMIN_REVIEW => 'Sedang Direview Admin',
            self::REVISION_ADMIN => 'Revisi (Admin)',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
            self::COMPLETED => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::SEKRETARIAT_REVIEW => 'blue',
            self::REVISION_SEKRETARIAT, self::REVISION_ADMIN => 'orange',
            self::ADMIN_REVIEW => 'purple',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
            self::COMPLETED => 'blue',
        };
    }

    /**
     * Status non-final: masih bisa berpindah tahap (belum approved/rejected/cancelled/completed).
     */
    public static function nonFinal(): array
    {
        return [
            self::PENDING->value,
            self::SEKRETARIAT_REVIEW->value,
            self::REVISION_SEKRETARIAT->value,
            self::ADMIN_REVIEW->value,
            self::REVISION_ADMIN->value,
        ];
    }
}
