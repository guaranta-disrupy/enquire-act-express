<?php

namespace App\AI\Schemas\App\Questionnaire;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class UpdatePrimesQuestionnaireSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'update_questionnaire_primes',
            description: 'Update existing primes in a test/round. Use this when the user wants to modify existing prime text or properties. Supports both single and multiple prime updates. CRITICAL: When the user mentions updating primes by name (e.g., "change Love prime to Hatred", "update Secure prime as Safe"), you MUST identify the prime by its equivalent text/name from the CURRENT PRIMES list, NOT by index. Examples: "change Love prime to Hatred" - find prime with equivalent "Love" and update to "Hatred". "update Secure and Safe primes to Happy and Sad" - update both primes separately.',
            properties: [
                new StringSchema(
                    name: 'test_id',
                    description: 'The ID of the test/round to update. This is the numeric ID of the test (e.g., 7803).'
                ),
                new ArraySchema(
                    name: 'primes',
                    description: 'Array of primes to update. CRITICAL: When user mentions updating by name (e.g., "change Love prime to Hatred"), use the identifier field with the OLD prime name (e.g., "Love") to find the prime, then set the new prime text. When user mentions multiple updates, create separate objects for each. Include ALL primes the user wants to update. Examples: "change Love prime to Hatred" creates [{"identifier": "Love", "prime": "Hatred"}]. "update Secure and Safe primes to Happy and Sad" creates [{"identifier": "Secure", "prime": "Happy"}, {"identifier": "Safe", "prime": "Sad"}].',
                    items: new ObjectSchema(
                        name: 'prime_update',
                        description: 'A single prime update operation',
                        properties: [
                            new StringSchema(
                                name: 'identifier',
                                description: 'The identifier to find the prime to update. Can be the equivalent text/name (e.g., "Love", "Secure", "Safe") or a numeric index as a string (e.g., "1", "2", "3"). CRITICAL: When user mentions a prime by name (e.g., "change Love prime"), use the exact equivalent text from the CURRENT PRIMES list. When user mentions by number, use the index as a string. Prefer equivalent text over index when the user mentions a name.'
                            ),
                            new StringSchema(
                                name: 'prime',
                                description: 'The new prime text content. HTML is allowed. This is what the prime will be changed to.'
                            ),
                            new StringSchema(
                                name: 'equivalent',
                                description: 'The new plain text equivalent of the prime. Use the same text as the prime field. For multi-word primes like "Option A", use the same text as the prime field.'
                            ),
                        ],
                        requiredFields: ['identifier', 'prime', 'equivalent']
                    )
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a user-friendly confirmation message about what primes will be updated. Use generic language like "Change prime \'Love\' to \'Hatred\' in this round?" or "Update primes \'Secure\' and \'Safe\' to \'Happy\' and \'Sad\' in this round?". DO NOT mention the test_id or specific test numbers. Use phrases like "in this round", "here", or "to this test" instead. Include what is being changed (old name to new name). End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['test_id', 'primes', 'message']
        );
    }
}