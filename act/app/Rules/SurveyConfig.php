<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SurveyConfig implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Ensure the value is an array
        if (!is_array($value)) {
            $fail("The {$attribute} must be a valid array.");
            return;
        }

        // Define required keys
        $requiredKeys = [
            'css',
            'js',
            'forward_response',
            'response_var',
            'swipe',
            'left_key',
            'right_key',
            'click_instructions',
            'swipe_instructions',
            'keypress_instructions',
            'keypress_click',
            'redirect',
            'theme',
            'survey_id',
            'download_as_values',
            'buttons',
            'texts',
        ];

        // Validate each key
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $value)) {
                $fail("The {$attribute} must contain the key '{$key}'.");
                return;
            }
        }
    }
}
