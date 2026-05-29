<?php

namespace App\AI\Schemas;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use App\AI\Schemas\Contracts\SchemaInterface;

class ConversationSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'conversation',
            description: 'Schema for a general AI conversation including reply, intent, and memory signals.',
            properties: [
                new StringSchema(
                    name: 'reply',
                    description: 'The assistant\'s reply to the user.'
                ),
                new StringSchema(
                    name: 'intent',
                    description: 'The inferred intent of the user message.'
                ),
                new BooleanSchema(
                    name: 'memory_update_needed',
                    description: 'Whether this exchange should be remembered and summarized.'
                ),
                new StringSchema(
                    name: 'memory_summary',
                    description: 'A summary of the conversation to store in memory.',
                    nullable: true
                ),
                new ArraySchema(
                    name: 'tags',
                    description: 'List of tags associated with this exchange.',
                    items: new StringSchema(
                        name: 'tag',
                        description: 'A single tag keyword.'
                    )
                )
            ],
            requiredFields: ['reply', 'intent', 'memory_update_needed']
        );
    }
}

