<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TestConfig implements ValidationRule
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
            'title',
            'main_trial',
            'random',
            'prime_header',
            'main_header',
            'empty_selection',
            'main_header_timeout',
            'type',
            'single_select',
            'limit',
            'auto_next',
            'display_by',
            'random_prime',
            'random_prime_subsets',
            'prime_as_next',
            'bottom_text',
            'conditions',
            'macros',
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
