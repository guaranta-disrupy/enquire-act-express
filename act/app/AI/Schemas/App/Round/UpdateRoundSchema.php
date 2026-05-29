<?php

namespace App\AI\Schemas\App\Round;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class UpdateRoundSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'update_round',
            description: 'Update one or more test rounds. System will enforce limits based on user subscription.',
            properties: [
                new ArraySchema(
                    name: 'updates',
                    description: "Array of round update operations. Include ALL updates the user requests. The system will automatically enforce action limits based on the user's subscription level.",
                    items: new ObjectSchema(
                        name: 'update_round',
                        description: 'A single round update operation',
                        properties: [
                            new StringSchema(
                                name: 'round_name',
                                description: 'The name or position of the round to update. If user says "first", "second", "third", "last", etc., use that exact word as the round_name (e.g., "first", "second", "last"). For named rounds, use the exact name.'
                            ),
                            new StringSchema(
                                name: 'survey_code',
                                description: 'CRITICAL: You MUST use the questionnaire_code value that is provided in the context/parameters. DO NOT generate or infer this - copy the exact questionnaire_code value provided.'
                            ),
                            new StringSchema(
                                name: 'new_name',
                                description: 'The new name for the round. Only include if user explicitly wants to rename the round.',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'main_header',
                                description: 'The new main header text for the round. Extract from user input when they mention updating or changing the header. Format as HTML paragraph tag with the text inside. Only include if user explicitly mentions updating the header.',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'bottom_text',
                                description: 'The new bottom instruction text for the round. Extract from user input when they mention updating or changing the "bottom", "bottom instruction", "bottom text", or "footer". HTML is allowed. Only include if user explicitly mentions updating the bottom text/instruction.',
                                nullable: true
                            ),
                            new EnumSchema(
                                name: 'round_type',
                                description: 'The type to convert the round to: "practice" for practice rounds, "main" for main rounds, "descriptive" for descriptive rounds. Only include if user explicitly wants to convert/change the round type (e.g., "convert practice round to main round").',
                                options: ['practice', 'main', 'descriptive'],
                                nullable: true
                            ),
                        ],
                        requiredFields: ['round_name', 'survey_code', 'new_name', 'main_header', 'bottom_text', 'round_type']
                    ),
                    nullable: false
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a confirmation message about what will be updated. Examples: "Update the last round header to \'Practice Round about Apples\'?" or "Rename the first round to \'Introduction\'?" or "Convert the practice round to a main round?". Include what is being changed. End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['updates', 'message']
        );
    }
}