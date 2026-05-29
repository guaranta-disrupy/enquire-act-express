<?php

namespace App\AI\Schemas\App\Round;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class UpdateSettingsRoundSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'update_round_settings',
            description: 'Update round/test settings for a specific test. Use this when the user wants to modify test configuration settings like practice trial, main trial, randomization options, selection limits, display settings, etc. Extract the test_id from context and only include settings that the user explicitly mentions changing.',
            properties: [
                new StringSchema(
                    name: 'test_id',
                    description: 'The ID of the test/round to update settings for. This is the numeric ID of the test (e.g., 7803). CRITICAL: You MUST use the test_id value that is provided in the context/parameters. DO NOT generate or infer this - copy the exact test_id value provided.'
                ),
                new StringSchema(
                    name: 'practice_trial',
                    description: 'Set to the string "true" to enable practice trial mode, or "false" to disable. If set to "true", main_trial will be automatically set to false. If user does not mention practice trial, set this field to null. Must be exactly "true", "false", or null.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'main_trial',
                    description: 'Set to the string "true" to enable main trial mode, or "false" to disable. If set to "true", practice_trial will be automatically set to false. If user does not mention main trial, set this field to null. Must be exactly "true", "false", or null.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'random',
                    description: 'Set to the string "true" to randomize targets, or "false" to disable randomization. If user does not mention randomizing targets, set this field to null. Must be exactly "true", "false", or null.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'random_prime',
                    description: 'Set to the string "true" to randomize primes, or "false" to disable randomization. If user does not mention randomizing primes, set this field to null. Must be exactly "true", "false", or null.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'prime_as_next',
                    description: 'Set to the string "true" to treat the prime as a button to continue to the next screen, or "false" to disable. If set to "true", auto_next will be automatically set to false. If user does not mention prime as next button, set this field to null. Must be exactly "true", "false", or null.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'single_select',
                    description: 'Set to the string "true" to make targets single select, or "false" to allow multiple selection. If user does not mention single select, set this field to null. Must be exactly "true", "false", or null.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'auto_next',
                    description: 'Set to the string "true" to automatically advance to the next round after a response is selected, or "false" to disable. If set to "true", prime_as_next will be automatically set to false. If user does not mention auto next or auto advance, set this field to null. Must be exactly "true", "false", or null.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'trial_unselect',
                    description: 'Set to the string "true" to add a "None of these" target option to unselect targets in the round, or "false" to disable. If user does not mention trial unselect or unselect option, set this field to null. Must be exactly "true", "false", or null.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'trial_unselect_label',
                    description: 'Label for the trial unselect option. Only include if user explicitly mentions changing the unselect label.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'empty_selection',
                    description: 'Warning message when no target is selected. Only include if user explicitly mentions changing the empty selection message.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'limit_reach',
                    description: 'Label when the selection limit is reached. Only include if user explicitly mentions changing the limit reach message.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'limit',
                    description: 'Maximum number of targets that can be selected (0 = can select all). Only include if user explicitly mentions selection limit or maximum selections.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'display_by',
                    description: 'Number of targets to be displayed. Only include if user explicitly mentions display by or number of targets to display.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'target_delay',
                    description: 'Delay time in milliseconds before targets are displayed. Only include if user explicitly mentions target delay or display delay.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a user-friendly confirmation message about what settings will be updated. Use generic language like "Enable practice trial mode in this round?" or "Set selection limit to 3 in this round?" or "Enable auto next and disable prime as next in this round?". DO NOT mention the test_id or specific test numbers. Use phrases like "in this round", "here", or "for this test" instead. Include what is being changed. End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['test_id', 'practice_trial', 'main_trial', 'random', 'random_prime', 'prime_as_next', 'single_select', 'auto_next', 'trial_unselect', 'trial_unselect_label', 'empty_selection', 'limit_reach', 'limit', 'display_by', 'target_delay', 'message']
        );
    }
}