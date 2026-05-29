<?php

namespace App\AI\Enums\App;

enum Components: string
{
    case EXPRESS = 'express';
    // case ACT = 'act';
    // case IMPRESS = 'impress';
    // case FAST = 'fast';
    // case IMPACT = 'impact';
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