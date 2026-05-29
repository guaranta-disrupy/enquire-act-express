<?php

namespace App\AI\Schemas\App\Round;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class MoveRoundSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'move_round',
            description: 'Move one or more test rounds to a different survey, or reorder rounds within the same survey. System will enforce limits based on user subscription.',
            properties: [
                new ArraySchema(
                    name: 'moves',
                    description: "Array of round move operations. Include ALL moves the user requests. The system will automatically enforce action limits based on the user's subscription level.",
                    items: new ObjectSchema(
                        name: 'move_operation',
                        description: 'A single round move operation',
                        properties: [
                            new StringSchema(
                                name: 'round_name',
                                description: 'The name or position of the round to move. If user says "first", "second", "third", "last", etc., use that exact word as the round_name (e.g., "first", "second", "last"). For named rounds, use the exact name.'
                            ),
                            new StringSchema(
                                name: 'source_survey_code',
                                description: 'CRITICAL: The questionnaire_code of the source survey (where the round currently exists). You MUST use the questionnaire_code value that is provided in the context/parameters.'
                            ),
                            new StringSchema(
                                name: 'target_survey_code',
                                description: 'The questionnaire_code of the target survey (where to move the round). If moving within the same survey (reordering), use the same code as source_survey_code. Extract from user message or surveys list.'
                            ),
                            new StringSchema(
                                name: 'target_position',
                                description: 'Optional target position for reordering within the same survey. Use "first", "second", "third", "last", etc. if user specifies where to move it. Only used when source and target survey are the same.',
                                nullable: true
                            ),
                        ],
                        requiredFields: ['round_name', 'source_survey_code', 'target_survey_code', 'target_position']
                    ),
                    nullable: false
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a confirmation message about what will be moved. Examples: "Move the practice round to survey X?" or "Move the last round to the first position?" or "Reorder the last round to become first?". Include what is being moved and where. End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['moves', 'message']
        );
    }
}
