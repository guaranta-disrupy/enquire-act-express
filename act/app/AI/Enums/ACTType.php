<?php

namespace App\AI\Enums;

enum ACTType: string
{
    case Descriptive = 'descriptive';
    
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