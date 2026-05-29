<?php

return [
    'daily_token_limit' => env('AI_DAILY_TOKEN_LIMIT', 25000),
    
    'bulk_limit' => env('AI_BULK_LIMIT', 10),

    'off_topic_phrases' => [
        'can only help with questions related to EXPRESS',
        'can only help with EXPRESS',
        'only have knowledge about EXPRESS',
        'only know information related to EXPRESS',
        'can only assist with EXPRESS',
        'not sure how to help with that',
        'don\'t have information about',
        'cannot help with',
        'can\'t help with',
        'outside my knowledge',
        'not related to EXPRESS',
    ],
];

