<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // 'neo4j' => [
    //     'url' => env('NEO4J_URL', 'bolt://localhost:7474'),
    //     'username' => env('NEO4J_USERNAME', 'neo4j'),
    //     'password' => env('NEO4J_PASSWORD', 'neo4j')
    // ]
    
    'neo4j' => [
        'url' => env('NEO4J_URL', 'neo4j+s://bolt.neo4j.connoisseur-suite.co.uk:443'),
        'username' => env('NEO4J_USERNAME', 'neo4j'),
        'password' => env('NEO4J_PASSWORD', '8u_09Hj78_ash2')
    ],

    'ai_helpbot' => [
        'url' => env('AI_HELPBOT_API_URL'),
    ],
];
