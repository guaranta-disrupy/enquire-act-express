<?php

namespace App\AI\Schemas\App\Questionnaire;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class UpdateInstructionsQuestionnaireSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'update_questionnaire_instructions',
            description: 'Update the main header text or bottom text in a test round. Use this when the user wants to change instruction text. CRITICAL MAPPING: When user says "change the header" or "update the header", map to main_header field. When user says "change the bottom" or "update the bottom", map to bottom_text field.',
            properties: [
                new StringSchema(
                    name: 'test_id',
                    description: 'The ID of the test round to update. This is the numeric ID of the test.'
                ),
                new StringSchema(
                    name: 'main_header',
                    description: 'The new main header text that appears at the top of the test. HTML is allowed. CRITICAL: When user says "change the header to X" or "update the header to X", set this field to X. Set to null if user is not updating the header.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'bottom_text',
                    description: 'The new bottom text that appears at the bottom of the test. HTML is allowed. CRITICAL: When user says "change the bottom to X" or "update the bottom to X", set this field to X. Set to null if user is not updating the bottom text.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a confirmation message about what will be updated. Use generic language like Update bottom text to this is all a trial only in this round. Do not mention test_id. End with a question mark.'
                ),
            ],
            requiredFields: ['test_id', 'main_header', 'bottom_text', 'message']
        );
    }
}