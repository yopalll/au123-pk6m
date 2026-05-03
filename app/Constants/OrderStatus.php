<?php

namespace App\Constants;

/**
 * Single source of truth for order statuses.
 *
 * Used across controllers, Filament resources, widgets, and Blade views
 * to eliminate hardcoded status strings.
 *
 * Spelling: American English — "canceled" (single L).
 */
class OrderStatus
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const SUCCESS = 'success';
    public const CANCELED = 'canceled';

    /**
     * All valid statuses, useful for validation rules and enum checks.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::SUCCESS,
            self::CANCELED,
        ];
    }
}
