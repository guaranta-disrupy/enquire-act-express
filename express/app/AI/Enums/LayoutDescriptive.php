<?php

namespace App\AI\Enums;

enum LayoutDescriptive: string
{
    case Default = 'default';
    case ImageLeft = 'image left';
    case ImageRight = 'image right';
    case ImageTop = 'image top';

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