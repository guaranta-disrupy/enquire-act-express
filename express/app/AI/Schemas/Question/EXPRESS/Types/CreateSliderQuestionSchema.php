<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\StringSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateSliderItemSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateSliderChoiceSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;

class CreateSliderQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_slider_question',
            description: 'The slider question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'items',
                    description: 'The list of items/scenarios that the respondent will rate using the slider. Generate 2-6 coherent, related items that form a logical set. Items should be specific scenarios, situations, or aspects that relate to the question topic. Examples: For a question about "influence in fuel choice", items could be: "When I see messaging on signage as I approach the gas station", "When I see messaging as I drive up to the pump", "When the Gas Station Staff speaks to me". For a question about "satisfaction with a party", items could be: "Atmosphere of the party", "Quality of food and drinks", "Entertainment provided". Ensure all items are contextually related and form a coherent set that makes sense together. Each item should be a complete phrase or statement describing what is being rated.',
                    items: (new CreateSliderItemSchema())->getSchema()
                ),
                new ArraySchema(
                    name: 'choices',
                    description: 'The scale labels/values for the slider (the rating scale itself). Generate 3-7 contextually appropriate scale labels that match the question\'s intent. Use human-readable words with spaces only—never underscores (e.g. "very low", "very high", not "very_low", "very_high"). The scale should form a coherent progression from low to high. Examples: For influence: "Very unlikely to be influenced", "Unlikely", "Neutral", "Likely", "Highly likely to be influenced". For satisfaction: "Very dissatisfied", "Dissatisfied", "Neutral", "Satisfied", "Very satisfied". For quality: "Poor", "Fair", "Good", "Very Good", "Excellent". PREFER TEXT-BASED LABELS over numeric values. The scale labels must make sense for rating the items provided.',
                    items: (new CreateSliderChoiceSchema())->getSchema(),
                ), 
                (new CreateRequiredSchema())->getSchema(),
                new \Prism\Prism\Schema\BooleanSchema(
                    name: 'random_items',
                    description: 'This defines if the items should be presented in a random order. If this has not been specified, use false.'
                ), 
                (new CreateEditLayoutWidthSchema())->getSchema(),
                new StringSchema(
                    name: 'set_default_slider_position',
                    description: 'This specifies the position of the slider, prior to the respondent selecting an answer. THIS SHOULD BE SPECIFIED BY THE USER. If not specified, use 3',
                )
            ],
            requiredFields: ['title_and_question_text', 'items', 'choices', 'required', 'random_items', 'edit_layout_width', 'set_default_slider_position']
        );
    }
}