<?php

namespace App\Helpers;

/**
 * Static storage for the decoded JWT payload.
 * JwtFilter stores the payload here after successful token validation.
 * ResponseTrait::getUserId() reads from here.
 */
class JwtPayload
{
    private static ?object $payload = null;

    public static function set(object $payload): void
    {
        self::$payload = $payload;
    }

    public static function get(): ?object
    {
        return self::$payload;
    }

    public static function getUserId(): int
    {
        if (self::$payload === null) {
            return 0;
        }
        return (int) (self::$payload->sub ?? 0);
    }
}
