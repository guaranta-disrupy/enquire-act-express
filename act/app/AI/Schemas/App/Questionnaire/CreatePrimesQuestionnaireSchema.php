<?php

namespace App\AI\Schemas\App\Questionnaire;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CreatePrimesQuestionnaireSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'create_questionnaire_primes',
            description: 'Add new primes to a test/round. Use this when the user wants to add new primes to an existing test. Supports both single and multiple prime creation. CRITICAL: When the user mentions multiple primes (e.g., "add Option A, Option B, and Option C as primes", "add Happy, Sad, and Angry as primes"), you MUST extract ALL primes separately into the primes array, NOT just the first one. Split on commas and "and" to extract all mentioned primes. Examples: "add Option E as prime" creates one prime. "add Option A, Option B, and Option C as primes" creates three primes.',
            properties: [
                new StringSchema(
                    name: 'test_id',
                    description: 'The ID of the test/round to add primes to. This is the numeric ID of the test (e.g., 7803).'
                ),
                new ArraySchema(
                    name: 'primes',
                    description: 'Array of new primes to add. CRITICAL: When user mentions multiple primes (e.g., "add Option A, Option B, and Option C as primes"), split on commas and "and" to extract each prime separately. Create a separate prime object for EACH mentioned prime. Include ALL primes the user wants to add - never skip any. Examples: "add Option A, Option B, and Option C as primes" creates 3 primes. "add Option E as prime" creates 1 prime.',
                    items: new ObjectSchema(
                        name: 'prime_item',
                        description: 'A single prime to add',
                        properties: [
                            new StringSchema(
                                name: 'prime',
                                description: 'The prime text content. HTML is allowed. Extract the exact text the user mentions (e.g., "Option A", "Option B", "Happy", "Sad").'
                            ),
                            new StringSchema(
                                name: 'equivalent',
                                description: 'Plain text equivalent of the prime. Use the same text as the prime field. For multi-word primes like "Option A", use the same text as the prime field.'
                            ),
                        ],
                        requiredFields: ['prime', 'equivalent']
                    )
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a user-friendly confirmation message about what primes will be added. Use generic language like "Add prime \'Love\' in this round?" or "Add primes \'Apple\' and \'Banana\' in this round?". DO NOT mention the test_id or specific test numbers. Use phrases like "in this round", "here", or "to this test" instead. Include what is being added. End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['test_id', 'primes', 'message']
        );
    }
}
