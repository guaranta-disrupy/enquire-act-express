<?php

namespace App\AI\Enums;

enum Intent: string
{
    case Create = 'create';
    // case Delete = 'delete';
    case Duplicate = 'duplicate';
    case Move = 'move';
    case Update = 'update';
    case Conversation = 'conversation';
    case Guide = 'guide';
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
