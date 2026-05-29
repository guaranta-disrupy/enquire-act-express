<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use App\AI\Enums\Units;

use Prism\Prism\Schema\EnumSchema;

class CreateUnitsSchema
{
    public function getSchema(): EnumSchema
    {
        return new EnumSchema(
            name: 'units',
            description: 'The units to be used for edit layout width. If a unit is not specified, use "percentage". Choose "percentage" for percentage, "vmin" for viewport minimum, "vw" for viewport width, and "auto" for automatic sizing.',
            options: Units::values(),
        );
    }
}