<?php

namespace App\AI\Enums;

enum Intent: string
{
    case Create = 'create';
    //case Delete = 'delete';
    case Update = 'update';
    case Copy = 'copy';
    case Move = 'move';
    case Conversation = 'conversation';
    case Documentation = 'documentation';
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