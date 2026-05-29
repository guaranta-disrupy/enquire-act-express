<?php

namespace App\AI\Enums;

enum Scope: string
{
    case Guide = 'guide';
    case Project = 'project';
    case Folder = 'folder';
    case Survey = 'survey';
    case Component = 'component';
    case Question = 'question';
    case Conversation = 'conversation';
    
    
    // Question types
    case Descriptive = 'descriptive';
    case Round = 'round';
   
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