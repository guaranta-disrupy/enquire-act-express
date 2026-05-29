<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CreateBaseQuestionSchema
{
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'title_and_question_text',
            description: 'The name and question text to be used in various question types.',
            properties: [
                new StringSchema(
                    name: 'name',
                    description: 'The name of the question. DO NOT WRITE YOUR OWN IF THE USER HAS NOT SPECIFIED ONE.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'question_text',
                    description: 'The question text to be displayed to respondents. Ensure that the text entered here is formatted correctly - correct the grammar that the user has entered if it is not correct.',
                    nullable: true
                )
                ],
                requiredFields: ['name', 'question_text']
        );
    }
}