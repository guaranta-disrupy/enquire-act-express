<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProjectConfig implements ValidationRule
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
            'development',
            'redirects',
            'progress',
            'buttons',
            'texts',
            'records',
            'theme',
            'js',
            'css',
        ];

        $nestedKeys = [
            'redirects' => [
                'complete',
                'quality_control',
                'pass_variable',
            ],
            'progress' => [
                'show',
                'text',
                'continue',
            ],
            'buttons' => [
                'next',
                'final',
            ],
            'texts' => [
                'saving',
                'redirecting',
                'error_start',
                'error_saving',
            ],
            'records' => [
                'show_completes',
            ],
        ];

        // Validate top-level keys
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $value)) {
                $fail("The {$attribute} must contain the key '{$key}'.");
                return;
            }
        }

        // Validate nested keys
        foreach ($nestedKeys as $parentKey => $subKeys) {
            foreach ($subKeys as $key) {
                if (!array_key_exists($key, $value[$parentKey])) {
                    $fail("The {$attribute}->{$parentKey} must contain the key '{$key}'.");
                    return;
                }
            }
        }
    }
}
