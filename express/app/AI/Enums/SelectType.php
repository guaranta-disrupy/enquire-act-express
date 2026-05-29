<?php

namespace App\AI\Enums;

enum SelectType: string
{
    case OpenText = 'open text';
    case LettersOnly = 'letters only';
    case Alphanumeric = 'alphanumeric';
    case WholeNumber = 'whole number';
    case DecimalNumber = 'decimal number';
    case EmailAddress = 'email address';
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'date time';
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