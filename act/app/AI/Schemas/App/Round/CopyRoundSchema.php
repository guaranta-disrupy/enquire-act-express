<?php

namespace App\AI\Schemas\App\Round;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CopyRoundSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'copy_round',
            description: 'Copy/duplicate one or more test rounds to the same or different survey. System will enforce limits based on user subscription.',
            properties: [
                new ArraySchema(
                    name: 'copies',
                    description: "Array of round copy operations. Include ALL copies the user requests. The system will automatically enforce action limits based on the user's subscription level.",
                    items: new ObjectSchema(
                        name: 'copy_operation',
                        description: 'A single round copy operation',
                        properties: [
                            new StringSchema(
                                name: 'round_name',
                                description: 'The name or position of the round to copy. If user says "first", "second", "third", "last", etc., use that exact word as the round_name (e.g., "first", "second", "last"). For named rounds, use the exact name.'
                            ),
                            new StringSchema(
                                name: 'source_survey_code',
                                description: 'CRITICAL: The questionnaire_code of the source survey (where the round currently exists). You MUST use the questionnaire_code value that is provided in the context/parameters.',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'target_survey_code',
                                description: 'The questionnaire_code of the target survey (where to copy the round). If copying within the same survey, use the same code as source_survey_code. If user doesn\'t specify, use the current survey code.',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'new_name',
                                description: 'Optional new name for the copied round. If not specified, will use the original name with " (Copy)" appended.',
                                nullable: true
                            ),
                        ],
                        requiredFields: ['round_name', 'source_survey_code', 'target_survey_code', 'new_name']
                    ),
                    nullable: false
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a confirmation message about what will be copied. Examples: "Copy the practice round to survey X?" or "Duplicate the last round?" or "Copy the first round to another survey?". Include what is being copied. End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['copies', 'message']
        );
    }
}
