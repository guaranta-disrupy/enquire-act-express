<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\StringSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;

class CreateSliderChoiceSchema extends CreateInputSchema
{
    protected function getExtraProperties(): array
    {   
        $properties = [
            new StringSchema(
                name: 'value',
                description: 'The label for the slider value/choice. PREFER TEXT-BASED SCALE LABELS over numeric values. Use human-readable words with spaces only—never underscores. Correct: "very low", "very high", "Very low", "Very high". Wrong: "very_low", "very_high". Common examples: "Not at all", "Slightly", "Moderately", "Very", "Extremely" OR "Strongly Disagree", "Disagree", "Neutral", "Agree", "Strongly Agree" OR "Poor", "Fair", "Good", "Very Good", "Excellent" OR "Never", "Rarely", "Sometimes", "Often", "Always". Use numeric values ("1", "2", "3", "4", "5") only if text labels are not suitable. Create 3-7 values that form a coherent rating scale appropriate for the question topic.',
                nullable: false
            ),
            new StringSchema(
                name: 'colour',
                description: 'The hexadecimal colour for the choice. Generate appropriate color gradients that match the scale intensity: darker red (#b71c1c or #FF0000) for negative/low values (e.g., "Not at all", "Poor", "Never"), orange/yellow (#ff9800, #ef5350, or #FFA500) for neutral/middle values (e.g., "Moderately", "Neutral", "Sometimes"), green/teal (#8bc34a, #009688, or #00FF00) for positive/high values (e.g., "Extremely", "Excellent", "Always"). Create a smooth visual gradient from negative (red) to positive (green) across the scale.',
                nullable: false
            )
        ];

        $requiredFields = ['value', 'colour'];

        return [$properties, $requiredFields];
    }
}