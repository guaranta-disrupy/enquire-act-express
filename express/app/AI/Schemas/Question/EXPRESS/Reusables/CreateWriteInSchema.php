<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use App\AI\Enums\WriteInType;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMinimumCharacterWriteInSchema; 
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMaximumCharacterWriteInSchema;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\StringSchema;

class CreateWriteInSchema {
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'write_in',
            description: 'The schema for write-in questions, allowing users to provide custom text input.',
            properties: [
                new BooleanSchema(
                    name: 'enabled',
                    description: 'Indicates whether the write-in option is enabled. THIS SHOULD BE SPECIFIED BY THE USER. If it is not specified, use false.'
                ),
                new EnumSchema(
                    name: 'write_in_type',
                    description: 'THIS SHOULD ONLY BE FILLED IN IF ENABLED IS SET TO TRUE, OTHERWISE SET TO NULL. The type of write-in question. THIS SHOULD BE SPECIFIED BY THE USER. If it is not specified, use "character length". Choose "character length" for a question that requires a specific number of characters, "letters only" for a question that only allows letters, "alphanumeric" for a question that allows letters and numbers, "whole number" for a question that only allows whole numbers, "decimal number for a question that only allows decimal numbers, "email address" for a question that requires an email address, "date" for a question that forces users to input a date, "time" for a question that forces users to input a time, and "date time" for a question that forces users to input a date and time.',
                    options: WriteInType::values(),
                    nullable: true
                ),
                (new CreateMinimumCharacterWriteInSchema())->getSchema(),
                (new CreateMaximumCharacterWriteInSchema())->getSchema(),
                new StringSchema(
                    name: 'placeholder',
                    description: 'THIS SHOULD ONLY BE FILLED IN IF ENABLED IS SET TO TRUE, OTHERWISE SET TO NULL. The placeholder text for the write-in question, which will be displayed prior to a user typing an answer. If this has not been specified, set to null.',
                    nullable: true
                ), 
                new NumberSchema(
                    name: 'set_max_width',
                    description: 'THIS SHOULD ONLY BE FILLED IN IF ENABLED IS SET TO TRUE, OTHERWISE SET TO NULL. The maximum width of the write-in textbox in pixels. If this has not been specified, set to null.',
                    nullable: true
                )
            ],
            requiredFields: ['enabled', 'write_in_type', 'minimum_character', 'maximum_character', 'placeholder', 'set_max_width']
        );
    }
}