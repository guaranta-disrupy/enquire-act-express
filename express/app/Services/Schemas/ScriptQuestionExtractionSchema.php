<?php

namespace App\Services\Schemas;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;

class ScriptQuestionExtractionSchema
{
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'question_data',
            description: 'Extracted question data from script part',
            properties: [
                new ArraySchema(
                    name: 'questions',
                    description: 'Array of question objects with essential fields',
                    items: new ObjectSchema(
                        name: 'question',
                        description: 'Question object with essential fields only',
                        properties: [
                            new StringSchema(
                                name: 'QuestionCode',
                                description: 'REQUIRED - The question code from the header (e.g. A1, A2, B1, F3). Used to ensure only questions from the requested Part are included. Part A = codes starting with A; Part B = codes starting with B; etc.'
                            ),
                            new StringSchema(
                                name: 'QuestionTitle',
                                description: 'Question title/identifier (REQUIRED - this is the value in the "Question Title" field/row in the question properties table. Look for a row labeled "Question Title" and extract the value/text in the right column of that row. Example: "Survey is Enjoyable". This is NOT the header "F2 - Survey is Enjoyable", but rather the value in the Question Title field itself.)'
                            ),
                            new StringSchema(
                                name: 'QuestionType',
                                description: 'The type of question (e.g., Multiple Choice, Text, Rating, Matrix, Ranking)',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'AnswerType',
                                description: 'The type of answer allowed (e.g., Single Answer, Multiple Answer, Single Selection, Multiple Selection)',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'QuestionText',
                                description: 'The question text (REQUIRED - this is the question text that appears in the "Main Language" column in the question details table, typically in a row labeled "Question". CRITICAL: Preserve ALL spacing between words and characters, and preserve text styling formatting such as Bold, Italic, and Underline exactly as they appear in the script. However, do NOT preserve line breaks - convert line breaks to single spaces. Do not modify or normalize the text in any other way.)'
                            ),
                            new StringSchema(
                                name: 'ProgrammingInstructions',
                                description: 'Programming instructions or logic if present',
                                nullable: true
                            ),
                            new ArraySchema(
                                name: 'Labels',
                                description: 'Array of label strings if applicable',
                                items: new StringSchema(name: 'label', description: 'Label text')
                            ),
                            new ArraySchema(
                                name: 'Choices',
                                description: 'REQUIRED array of choice option strings for multiple choice questions. These are the answer option texts that appear as table rows under the Main Language column after the question text. Extract the text from the Main Language column for each choice row (e.g., "Very enjoyable to complete", "Quite enjoyable to complete"). Preserve order from top to bottom.',
                                items: new StringSchema(name: 'choice', description: 'Choice text from Main Language column')
                            ),
                            new ArraySchema(
                                name: 'Items',
                                description: 'Array of item strings for matrix/pairwise questions (can be JSON strings for complex items)',
                                items: new StringSchema(name: 'item', description: 'Item text or JSON string')
                            ),
                            new ArraySchema(
                                name: 'Values',
                                description: 'Array of value strings for slider/rating questions (can be JSON strings for complex values)',
                                items: new StringSchema(name: 'value', description: 'Value text or JSON string')
                            )
                        ],
                        requiredFields: ['QuestionCode', 'QuestionText', 'Choices']
                    )
                )
            ],
            requiredFields: ['questions']
        );
    }
}

