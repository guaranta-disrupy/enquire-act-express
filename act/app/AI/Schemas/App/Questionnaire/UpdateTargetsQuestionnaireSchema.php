<?php

namespace App\AI\Schemas\App\Questionnaire;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class UpdateTargetsQuestionnaireSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'update_questionnaire_targets',
            description: 'Update existing targets in a test/round. Use this when the user wants to modify existing target text or properties. Supports both single and multiple target updates. CRITICAL: When the user mentions updating targets by name (e.g., "change Option A target to Option B", "update Red target as Blue"), you MUST identify the target by its equivalent text/name from the CURRENT TARGETS list, NOT by index. Examples: "change Option A target to Option B" - find target with equivalent "Option A" and update to "Option B". "update Red and Blue targets to Green and Yellow" - update both targets separately.',
            properties: [
                new StringSchema(
                    name: 'test_id',
                    description: 'The ID of the test/round to update. This is the numeric ID of the test (e.g., 7803).'
                ),
                new ArraySchema(
                    name: 'targets',
                    description: 'Array of targets to update. CRITICAL: When user mentions updating by name (e.g., "change Option A target to Option B"), use the identifier field with the OLD target name (e.g., "Option A") to find the target, then set the new target text. When user mentions multiple updates, create separate objects for each. Include ALL targets the user wants to update. Examples: "change Option A target to Option B" creates [{"identifier": "Option A", "target": "Option B"}]. "update Red and Blue targets to Green and Yellow" creates [{"identifier": "Red", "target": "Green"}, {"identifier": "Blue", "target": "Yellow"}].',
                    items: new ObjectSchema(
                        name: 'target_update',
                        description: 'A single target update operation',
                        properties: [
                            new StringSchema(
                                name: 'identifier',
                                description: 'The identifier to find the target to update. Can be the equivalent text/name (e.g., "Option A", "Red", "Blue") or a numeric index as a string (e.g., "1", "2", "3"). CRITICAL: When user mentions a target by name (e.g., "change Option A target"), use the exact equivalent text from the CURRENT TARGETS list. When user mentions by number, use the index as a string. Prefer equivalent text over index when the user mentions a name.'
                            ),
                            new StringSchema(
                                name: 'target',
                                description: 'The new target text content. HTML is allowed. This is what the target will be changed to.'
                            ),
                            new StringSchema(
                                name: 'equivalent',
                                description: 'The new plain text equivalent of the target. Use the same text as the target field. For multi-word targets like "Option A", use the same text as the target field.'
                            ),
                        ],
                        requiredFields: ['identifier', 'target', 'equivalent']
                    )
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a user-friendly confirmation message about what targets will be updated. Use generic language like "Change target \'Option A\' to \'Option B\' in this round?" or "Update targets \'Red\' and \'Blue\' to \'Green\' and \'Yellow\' in this round?". DO NOT mention the test_id or specific test numbers. Use phrases like "in this round", "here", or "to this test" instead. Include what is being changed (old name to new name). End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['test_id', 'targets', 'message']
        );
    }
}