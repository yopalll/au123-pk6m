<?php

namespace App\Constants;

/**
 * Single source of truth for user roles (SEC-08).
 *
 * Used in middleware, Filament panel access, and canAccessPanel()
 * to eliminate hardcoded role strings and prevent typo-based bypass.
 */
class UserRole
{
    public const CUSTOMER    = 'customer';
    public const SALON_OWNER = 'salon_owner';
    public const ADMIN       = 'admin';

    /**
     * All valid roles, useful for validation rules and enum checks.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CUSTOMER,
            self::SALON_OWNER,
            self::ADMIN,
        ];
    }
}
