<?php

namespace App\AI\Schemas\App\Round;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CreateRoundSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'create_round',
            description: 'Create one or more test rounds (e.g., descriptive, practice, main rounds) in a survey. System will enforce limits based on user subscription.',
            properties: [
                new ArraySchema(
                    name: 'rounds',
                    description: "Array of round/test creation operations. Include ALL rounds the user requests. The system will automatically enforce action limits based on the user's subscription level. Each item should contain the round name and target survey information.",
                    items: new ObjectSchema(
                        name: 'round_data',
                        description: 'A single round/test creation data',
                        properties: [
                            new StringSchema(
                                name: 'name',
                                description: 'The custom name/title for the round. Extract from user input: if they say "about X", use "X"; if they say "named Y", use "Y". Examples: "create main round about blasphemy" -> name: "Main Round about blasphemy", "create practice round named Intro" -> name: "Intro". If no specific name/topic given, use default: "Practice Round", "Main Round", or "Descriptive Round".',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'round_type',
                                description: 'The type of round: "practice" for practice rounds, "main" for main rounds, "descriptive" for descriptive rounds. This determines the configuration.',
                                nullable: false
                            ),
                            new StringSchema(
                                name: 'survey_code',
                                description: 'CRITICAL: You MUST use the questionnaire_code value that is provided in the context/parameters. Do NOT generate or infer this - copy the exact questionnaire_code value provided (e.g., if questionnaire_code is "3757", use "3757" here).',
                                nullable: true
                            ),
                        ],
                        requiredFields: ['name', 'round_type', 'survey_code']
                    ),
                    nullable: false
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a simple confirmation message. Examples: "Create a Main Round?" or "Create a Practice Round named \'What is love\'?" or "Create a Descriptive Round?". CRITICAL RULES: 1) NEVER mention survey name, 2) NEVER include "in survey", 3) Keep it simple with just the round type and optional custom name.',
                    nullable: false
                ),
            ],
            requiredFields: ['rounds', 'message']
        );
    }
}
