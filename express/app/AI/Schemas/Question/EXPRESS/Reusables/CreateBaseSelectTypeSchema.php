<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use App\AI\Enums\SelectType;

abstract class CreateBaseSelectTypeSchema
{
    abstract protected function getExtraProperties(): array;

    public function getSchema(): ObjectSchema
    {
        [$extraProperties, $extraRequiredFields] = $this->getExtraProperties();

        $properties = [
            new EnumSchema(
                name: 'select_type_option',
                description: 'This indicates the type of select question. THIS SHOULD BE SPECIFIED BY THE USER. If it is not specified, use "open text". Choose "open text" for a question that allows any inputs, "letters only" for a question that only allows letters, "alphanumeric" for a question that allows letters and numbers, "whole number" for a question that only allows whole numbers, "decimal number" for a question that only allows decimal numbers, "email address" for a question that requires an email address, "date" for a question that forces users to input a date, "time" for a question that forces users to input a time, and "date time" for a question that forces users to input a date and time.',
                options: SelectType::values()
            )
        ];

        $requiredFields = ['select_type_option']; 

        $properties = array_merge($properties, $extraProperties);
        $requiredFields = array_merge($requiredFields, $extraRequiredFields);

        return new ObjectSchema(
            name: 'select_type',
            description: 'Properties for select type',
            properties: $properties,
            requiredFields: $requiredFields
        );
    }
}
