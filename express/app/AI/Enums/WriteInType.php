<?php

namespace App\AI\Enums;

enum WriteInType: string
{
    case characterLength = 'character length';
    case lettersOnly = 'letters only';
    case alphanumeric = 'alphanumeric';
    case wholeNumber = 'whole number';
    case decimalNumber = 'decimal number';
    case emailAddress = 'email address';
    case date = 'date';
    case time = 'time';
    case dateTime = 'date time';
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