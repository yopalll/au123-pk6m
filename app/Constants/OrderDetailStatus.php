<?php

namespace App\Constants;

/**
 * Single source of truth for order_detail statuses (BUG-A18).
 *
 * Domain is separate from OrderStatus — order_detail has its own lifecycle:
 * pending → in_progress → completed (or canceled).
 *
 * Used in BookingController and owner panel.
 */
class OrderDetailStatus
{
    public const PENDING     = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED   = 'completed';
    public const CANCELED    = 'canceled';

    /**
     * All valid statuses, useful for validation rules and enum checks.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::CANCELED,
        ];
    }
}
