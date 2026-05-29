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
    case VideoPlayer = 'video_player';
    case TextSelection = 'text_selection';
    case ImageSelection = 'image_selection';
    case SingleTextbox = 'single_textbox';
    case MultipleTextbox = 'multiple_textbox';
    case OpenEnded = 'open_ended';
    case PieChart = 'pie_chart';
    case BarChart = 'bar_chart';
    case Slider = 'slider';
    case Ranking = 'ranking';
    case StarRating = 'star_rating';
    case ButtonRating = 'button_rating';
    case Segmentation = 'segmentation';
    case Matrix = 'matrix';
    case PairwiseComparison = 'pairwise_comparison';
    case BestWorstScale = 'best-worst_scale';

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
