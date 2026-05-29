<?php

namespace App\AI\Schemas\App\Questionnaire;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CreateTargetsQuestionnaireSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'create_questionnaire_targets',
            description: 'Add new targets to a test/round. Use this when the user wants to add new targets to an existing test. Supports both single and multiple target creation. CRITICAL: When the user mentions multiple targets (e.g., "add Option A, Option B, and Option C as targets", "add Red, Blue, and Green as targets"), you MUST extract ALL targets separately into the targets array, NOT just the first one. Split on commas and "and" to extract all mentioned targets. Examples: "add Love as target" creates one target. "add Option A, Option B, and Option C as targets" creates three targets.',
            properties: [
                new StringSchema(
                    name: 'test_id',
                    description: 'The ID of the test/round to add targets to. This is the numeric ID of the test (e.g., 7803).'
                ),
                new ArraySchema(
                    name: 'targets',
                    description: 'Array of new targets to add. CRITICAL: When user mentions multiple targets (e.g., "add Option A, Option B, and Option C as targets"), split on commas and "and" to extract each target separately. Create a separate target object for EACH mentioned target. Include ALL targets the user wants to add - never skip any. Examples: "add Option A, Option B, and Option C as targets" creates 3 targets. "add Love as target" creates 1 target.',
                    items: new ObjectSchema(
                        name: 'target_item',
                        description: 'A single target to add',
                        properties: [
                            new StringSchema(
                                name: 'target',
                                description: 'The target text content. HTML is allowed. Extract the exact text the user mentions (e.g., "Option A", "Option B", "Red", "Blue").'
                            ),
                            new StringSchema(
                                name: 'equivalent',
                                description: 'Plain text equivalent of the target. Use the same text as the target field. For multi-word targets like "Option A", use the same text as the target field.'
                            ),
                        ],
                        requiredFields: ['target', 'equivalent']
                    )
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a user-friendly confirmation message about what targets will be added. Use generic language like "Add target \'Red\' in this round?" or "Add targets \'Red\' and \'Blue\' in this round?". DO NOT mention the test_id or specific test numbers. Use phrases like "in this round", "here", or "to this test" instead. Include what is being added. End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['test_id', 'targets', 'message']
        );
    }
}