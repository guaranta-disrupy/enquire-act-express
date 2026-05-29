<?php

return [
    // Instruction-seeking question patterns (user asking for guidance, not giving a command)
    'instruction_patterns' => [
        'how do i',
        'how can i',
        'how to',
        'how could i',
        'how should i',
        'how would i',
        'what\'s the way to',
        'whats the way to',
        'what is the way to',
        'can you tell me how',
        'could you tell me how',
        'what do i need to do to',
        'where do i',
        'where can i',
        'what are the steps',
        'what is the process',
        'show me how',
        'guide me',
        'steps to',
        'process for',
        'way to',
        'explain how',
        'teach me',
        'instructions for',
        'instructions to',
    ],

    // Command patterns (used to differentiate from instruction questions)
    'command_patterns' => [
        'create a',
        'make a',
        'add a',
        'build a',
        'generate a',
        'produce a',
        'construct a',
        'design a',
        'i want',
        'i need',
        'i would like',
        'please create',
        'please make',
        'please add',
    ],

    // Question words for detection
    'question_words' => ['what', 'how', 'where', 'when', 'why', 'which', 'who'],

    // Specific question patterns
    'specific_question_patterns' => [
        '/^what is a? .+ question/i',
        '/^what are .+ questions/i',
        '/^what does .+ mean/i',
        '/^what is the difference between/i',
        '/^can you explain/i',
        '/^tell me about/i',
        '/^describe/i',
        '/can i ask what is a? .+ question/i',
        '/can i ask what are .+ questions/i',
        '/can you tell me what is a? .+ question/i',
        '/can you explain what is a? .+ question/i',
        '/^can i create/i',
        '/^can i make/i',
        '/^can i add/i',
        '/^can i build/i',
        '/^can i generate/i',
        '/^can i design/i',
        '/^is it possible to create/i',
        '/^is it possible to make/i',
        '/^is it possible to add/i',
        '/^am i able to create/i',
        '/^am i able to make/i',
        '/^am i able to add/i',
        '/^do you support.*create/i',
        '/^do you support.*make/i',
        '/^do you support.*add/i',
    ],

    // Question type patterns for detection
    'question_patterns' => [
        '/^what is a? (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale)/i',
        '/^what is (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale) question/i',
        '/^what are (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale)/i',
        '/^explain (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale)/i',
        '/^tell me about (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale)/i',
        '/^how does (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale)/i',
        '/^can you explain (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale)/i',
        '/^how about a? (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale)/i',
        '/^what about (multiple choice|text selection|image selection|descriptive|video player|single textbox|multiple textbox|open ended|pie chart|bar chart|slider|ranking|star rating|button rating|segmentation|matrix|pairwise comparison|best.worst scale)/i'
    ],

    // Question type map for response detection
    'question_type_map' => [
        // Text selection variations
        'text selection' => 'text_selection',
        'text selection please' => 'text_selection',
        'its a text selection' => 'text_selection',
        'i want text selection' => 'text_selection',
        'i need text selection' => 'text_selection',
        'text selection question' => 'text_selection',
        
        // Image selection variations
        'image selection' => 'image_selection',
        'image selection please' => 'image_selection',
        'its an image selection' => 'image_selection',
        'i want image selection' => 'image_selection',
        'i need image selection' => 'image_selection',
        'image selection question' => 'image_selection',
        
        // Descriptive variations
        'descriptive' => 'descriptive',
        'descriptive please' => 'descriptive',
        'its descriptive' => 'descriptive',
        'i want descriptive' => 'descriptive',
        'i need descriptive' => 'descriptive',
        'descriptive question' => 'descriptive',
        
        // Video player variations
        'video player' => 'video_player',
        'video player please' => 'video_player',
        'its a video player' => 'video_player',
        'i want video player' => 'video_player',
        'i need video player' => 'video_player',
        'video player question' => 'video_player',
        
        // Single textbox variations
        'single textbox' => 'single_textbox',
        'single textbox please' => 'single_textbox',
        'its a single textbox' => 'single_textbox',
        'i want single textbox' => 'single_textbox',
        'i need single textbox' => 'single_textbox',
        'single textbox question' => 'single_textbox',
        
        // Multiple textbox variations
        'multiple textbox' => 'multiple_textbox',
        'multiple textbox please' => 'multiple_textbox',
        'its a multiple textbox' => 'multiple_textbox',
        'i want multiple textbox' => 'multiple_textbox',
        'i need multiple textbox' => 'multiple_textbox',
        'multiple textbox question' => 'multiple_textbox',
        
        // Open ended variations
        'open ended' => 'open_ended',
        'open ended please' => 'open_ended',
        'its open ended' => 'open_ended',
        'i want open ended' => 'open_ended',
        'i need open ended' => 'open_ended',
        'open ended question' => 'open_ended',
        
        // Chart variations
        'pie chart' => 'pie_chart',
        'pie chart please' => 'pie_chart',
        'its a pie chart' => 'pie_chart',
        'i want pie chart' => 'pie_chart',
        'i need pie chart' => 'pie_chart',
        'pie chart question' => 'pie_chart',
        
        'bar chart' => 'bar_chart',
        'bar chart please' => 'bar_chart',
        'its a bar chart' => 'bar_chart',
        'i want bar chart' => 'bar_chart',
        'i need bar chart' => 'bar_chart',
        'bar chart question' => 'bar_chart',
        
        // Rating variations
        'slider' => 'slider',
        'slider please' => 'slider',
        'its a slider' => 'slider',
        'i want slider' => 'slider',
        'i need slider' => 'slider',
        'slider question' => 'slider',
        
        'ranking' => 'ranking',
        'ranking please' => 'ranking',
        'its a ranking' => 'ranking',
        'i want ranking' => 'ranking',
        'i need ranking' => 'ranking',
        'ranking question' => 'ranking',
        
        'star rating' => 'star_rating',
        'star rating please' => 'star_rating',
        'its a star rating' => 'star_rating',
        'i want star rating' => 'star_rating',
        'i need star rating' => 'star_rating',
        'star rating question' => 'star_rating',
        
        'button rating' => 'button_rating',
        'button rating please' => 'button_rating',
        'its a button rating' => 'button_rating',
        'i want button rating' => 'button_rating',
        'i need button rating' => 'button_rating',
        'button rating question' => 'button_rating',
        
        'segmentation' => 'segmentation',
        'segmentation please' => 'segmentation',
        'its a segmentation' => 'segmentation',
        'i want segmentation' => 'segmentation',
        'i need segmentation' => 'segmentation',
        'segmentation question' => 'segmentation',
        
        // Matrix variations
        'matrix' => 'matrix',
        'matrix please' => 'matrix',
        'its a matrix' => 'matrix',
        'i want matrix' => 'matrix',
        'i need matrix' => 'matrix',
        'matrix question' => 'matrix',
        
        // Pairwise comparison variations
        'pairwise comparison' => 'pairwise_comparison',
        'pairwise comparison please' => 'pairwise_comparison',
        'its a pairwise comparison' => 'pairwise_comparison',
        'i want pairwise comparison' => 'pairwise_comparison',
        'i need pairwise comparison' => 'pairwise_comparison',
        'pairwise comparison question' => 'pairwise_comparison',
        
        // Best-worst scale variations
        'best-worst scale' => 'best-worst_scale',
        'best worst scale' => 'best-worst_scale',
        'best-worst scale please' => 'best-worst_scale',
        'best worst scale please' => 'best-worst_scale',
        'its a best-worst scale' => 'best-worst_scale',
        'its a best worst scale' => 'best-worst_scale',
        'i want best-worst scale' => 'best-worst_scale',
        'i want best worst scale' => 'best-worst_scale',
        'i need best-worst scale' => 'best-worst_scale',
        'i need best worst scale' => 'best-worst_scale',
        'best-worst scale question' => 'best-worst_scale',
        'best worst scale question' => 'best-worst_scale',
        
        // Question categories that need clarification - route to question scope
        'multiple choice' => 'question',
        'its a multiple choice' => 'question',
        'i want multiple choice' => 'question',
        'i need multiple choice' => 'question',
        'multiple choice question' => 'question',
        
        'textbox' => 'question',
        'its a textbox' => 'question',
        'i want textbox' => 'question',
        'i need textbox' => 'question',
        'textbox question' => 'question',
        
        'charts' => 'question',
        'its a charts' => 'question',
        'i want charts' => 'question',
        'i need charts' => 'question',
        'charts question' => 'question',
        
        'rating' => 'question',
        'its a rating' => 'question',
        'i want rating' => 'question',
        'i need rating' => 'question',
        'rating question' => 'question',
        
        'description' => 'question',
        'its a description' => 'question',
        'i want description' => 'question',
        'i need description' => 'question',
        'description question' => 'question',
    ],

    // Confirmation response patterns
    'confirmation_patterns' => [
        'yes',
        'yes please',
        'yes, please',
        'yes please!',
        'yes, please!',
        'sure',
        'sure please',
        'sure, please',
        'ok',
        'ok please',
        'ok, please',
        'okay',
        'okay please',
        'okay, please',
        'proceed',
        'go ahead',
        'go for it',
        'create it',
        'create the question',
        'yes, create it',
        'yes create it',
        'yes please create it',
        'yes, please create it',
        'sure, create it',
        'sure create it',
        'ok, create it',
        'ok create it',
        'do it',
        'do it please',
        'do it, please',
        'please do it',
        'please proceed',
        'please go ahead',
        'please create it',
        'please create the question',
        'that sounds good',
        'that sounds great',
        'looks good',
        'looks great',
        'perfect',
        'perfect!',
        'sounds good',
        'sounds great',
    ],

    // Bulk creation detection
    'bulk_creation' => [
        'verbs' => [
            'create', 'make', 'add', 'generate', 'build', 'produce', 
            'duplicate', 'copy', 'spawn', 'make me', 'design', 'construct'
        ],
        'targets' => [
            'project', 'projects', 'survey', 'surveys', 'folder', 'folders', 
            'component', 'components', 'question', 'questions', 'item', 'items'
        ],
        'number_words' => [
            'zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 
            'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 
            'ten' => 10, 'eleven' => 11, 'twelve' => 12, 'thirteen' => 13, 
            'fourteen' => 14, 'fifteen' => 15, 'sixteen' => 16, 'seventeen' => 17, 
            'eighteen' => 18, 'nineteen' => 19, 'twenty' => 20, 'thirty' => 30, 
            'forty' => 40, 'fifty' => 50, 'sixty' => 60, 'seventy' => 70, 
            'eighty' => 80, 'ninety' => 90, 'hundred' => 100, 'thousand' => 1000
        ],
        'mass_patterns' => ['hundreds', 'thousands'],
    ],

    // Creation question patterns
    'creation_question_patterns' => [
        '/(?:how|what|where).*create.*(?:question|survey|folder|component)/i',
        '/(?:how|what|where).*make.*(?:question|survey|folder|component)/i',
        '/(?:how|what|where).*add.*(?:question|survey|folder|component)/i',
    ],

    // Explicit question type keywords
    'explicit_question_types' => [
        'text selection', 'image selection', 'descriptive', 'video player',
        'single textbox', 'multiple textbox', 'open ended', 'pie chart', 'bar chart',
        'slider', 'ranking', 'star rating', 'button rating', 'segmentation',
        'matrix', 'pairwise comparison', 'best-worst scale',
        'description', 'multiple choice', 'textbox', 'charts', 'rating'
    ],

    'greeting_patterns' => [
        'hi',
        'hi!',
        'hi there',
        'hiya',
        'hey',
        'hey!',
        'hey there',
        'hello',
        'hello!',
        'hello there',
        'hey hello',
        'hi hello',
        'greetings',
        'greetings!',
        'good morning',
        'good afternoon',
        'good evening',
        'good day',
        'howdy',
        'sup',
        'yo',
        'what\'s up',
        'whats up',
        'thanks',
        'thank you',
    ],
];

