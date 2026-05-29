<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\StringSchema;

class CreateEmailProviderSchema 
{
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'email_provider',
            description: 'Properties for email provider. THESE SHOULD ONLY BE FILLED IN IF SELECT TYPE IS SET TO EMAIL ADDRESS, OTHERWISE LEAVE AS NULL',
            properties: [
                new BooleanSchema(
                    name: 'email_provider_enabled',
                    description: 'Specifies if only certain domains for email addresses can be used. If not specified, use false as default'
                ),
                new StringSchema(
                    name: 'accepted_domain',
                    description: 'Specifies the domain for email address that can be used. If not specified, use null as default',
                    nullable: true
                )
                ],
            requiredFields: ['email_provider_enabled', 'accepted_domain']
        );
    }
}