<?php

return [
    // EXPRESS question type messages
    'question_type_messages' => [
        'description' => 'There are two different question types in the Description question category! Would you like it to be a descriptive or video player question?',
        'multiple choice' => 'There are two different question types in the Multiple Choice question category! Would you like it to be a text selection or image selection question?',
        'textbox' => 'There are three different question types in the Textbox question category! Would you like it to be a single textbox, multiple textbox or open ended question?',
        'charts' => 'There are two different question types in the Charts question category! Would you like it to be a pie chart or bar chart question?',
        'rating' => 'There are five different question types in the Rating question category! Would you like it to be a slider, ranking, star rating, button rating or segmentation question?',
    ],

    // Question type to scope mapping
    'question_type_to_scope' => [
        'descriptive' => 'descriptive',
        'video player' => 'video_player',
        'text selection' => 'text_selection',
        'image selection' => 'image_selection',
        'single textbox' => 'single_textbox',
        'multiple textbox' => 'multiple_textbox',
        'open ended' => 'open_ended',
        'pie chart' => 'pie_chart',
        'bar chart' => 'bar_chart',
        'slider' => 'slider',
        'ranking' => 'ranking',
        'star rating' => 'star_rating',
        'button rating' => 'button_rating',
        'segmentation' => 'segmentation',
        'matrix' => 'matrix',
        'pairwise comparison' => 'pairwise_comparison',
        'best-worst scale' => 'best-worst_scale'
    ],

    // Question type to action mapping
    'question_type_to_action' => [
        'multiple choice' => ['action' => 'express_multiple_choice_create', 'scope' => 'multiple_choice'],
        'text selection' => ['action' => 'express_text_selection_create', 'scope' => 'text_selection'],
        'image selection' => ['action' => 'express_image_selection_create', 'scope' => 'image_selection'],
        'descriptive' => ['action' => 'express_descriptive_create', 'scope' => 'descriptive'],
        'video player' => ['action' => 'express_video_player_create', 'scope' => 'video_player'],
        'single textbox' => ['action' => 'express_single_textbox_create', 'scope' => 'single_textbox'],
        'multiple textbox' => ['action' => 'express_multiple_textbox_create', 'scope' => 'multiple_textbox'],
        'open ended' => ['action' => 'express_open_ended_create', 'scope' => 'open_ended'],
        'pie chart' => ['action' => 'express_pie_chart_create', 'scope' => 'pie_chart'],
        'bar chart' => ['action' => 'express_bar_chart_create', 'scope' => 'bar_chart'],
        'slider' => ['action' => 'express_slider_create', 'scope' => 'slider'],
        'ranking' => ['action' => 'express_ranking_create', 'scope' => 'ranking'],
        'star rating' => ['action' => 'express_star_rating_create', 'scope' => 'star_rating'],
        'button rating' => ['action' => 'express_button_rating_create', 'scope' => 'button_rating'],
        'segmentation' => ['action' => 'express_segmentation_create', 'scope' => 'segmentation'],
        'matrix' => ['action' => 'express_matrix_create', 'scope' => 'matrix'],
        'pairwise comparison' => ['action' => 'express_pairwise_comparison_create', 'scope' => 'pairwise_comparison'],
        'best worst scale' => ['action' => 'express_best_worst_scale_create', 'scope' => 'best_worst_scale']
    ],

    // Display name mappings
    'question_type_display_names' => [
        'multiple choice' => 'multiple choice',
        'text selection' => 'text selection',
        'image selection' => 'image selection',
        'descriptive' => 'descriptive',
        'video player' => 'video player',
        'single textbox' => 'single textbox',
        'multiple textbox' => 'multiple textbox',
        'open ended' => 'open ended',
        'pie chart' => 'pie chart',
        'bar chart' => 'bar chart',
        'slider' => 'slider',
        'ranking' => 'ranking',
        'star rating' => 'star rating',
        'button rating' => 'button rating',
        'segmentation' => 'segmentation',
        'matrix' => 'matrix',
        'pairwise comparison' => 'pairwise comparison',
        'best worst scale' => 'best worst scale',
        'best-worst scale' => 'best worst scale'
    ],

    // Sidebar to page mapping
    'sidebar_to_page' => [
        'questionnaire' => 'questionnaire',
        'question-templates' => 'question_templates',
        'templates' => 'question_templates',
        'themes' => 'themes',
        'gallery' => 'gallery',
        'settings' => 'express settings',
        'advanced' => 'advanced',
        'preview' => 'preview',
        'full-preview' => 'preview',
        'questions' => 'questionnaire',
        'survey' => 'survey',
        'surveys' => 'survey',
        'projects' => 'projects',
        'project' => 'project',
        'dashboard' => 'dashboard',
        'express' => 'EXPRESS'
    ],

    // URL to page mapping
    'url_to_page' => [
        'questions' => 'questionnaire',
        'question' => 'questionnaire',
        'settings' => 'settings',
        'themes' => 'themes',
        'gallery' => 'gallery',
        'advanced' => 'advanced',
        'preview' => 'preview',
        'projects' => 'projects',
        'project' => 'project',
        'questionnaires' => 'questionnaire',
        'questionnaire' => 'questionnaire',
        'surveys' => 'survey',
        'survey' => 'survey',
        'dashboard' => 'dashboard'
    ],

    // Legacy position mapping
    'legacy_position_to_page' => [
        'express' => 'EXPRESS',
        'questions' => 'questionnaire',
        'templates' => 'question templates',
        'themes' => 'themes',
        'gallery' => 'gallery',
        'settings' => 'express settings',
        'advanced' => 'advanced',
        'preview' => 'preview',
        'express/questionnaires' => 'questionnaire',
        'express/questionnaire' => 'questionnaire',
        'express/questions' => 'questionnaire',
        'express/question' => 'questionnaire',
        'express/surveys' => 'survey',
        'express/survey' => 'survey',
        'express/templates' => 'question templates',
        'express/themes' => 'themes',
        'express/gallery' => 'gallery',
        'express/settings' => 'express settings',
        'express/advanced' => 'advanced',
        'questionnaire' => 'questionnaire',
        'express-engine' => 'preview',
    ],
];

