<?php

namespace App\AI\Enums;

enum Platform: string
{
    case ACT = 'act';
    // case ACT = 'act';
    // case FAST = 'fast';
    // case IMPACT = 'impact';
    // case IMPRESS = 'impress';
    // case IMPRINT = 'imprint';

    /**
     * Get all enum values as an array of strings
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}