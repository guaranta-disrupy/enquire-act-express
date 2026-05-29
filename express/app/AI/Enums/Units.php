<?php 

namespace App\AI\Enums;

enum Units: string
{
    case Percentage = 'percentage';
    case Vmin = 'vmin';
    case Vw = 'vw';
    case Auto = 'auto';
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