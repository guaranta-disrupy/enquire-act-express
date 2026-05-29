<?php

namespace App\Services;

use App\Models\User;
use Doctrine\DBAL\Schema\Schema;
use Illuminate\Support\Str;
use Prism\Prism\Schema\ObjectSchema;
use App\AI\Repositories\Contracts\SchemaRepositoryInterface;
use Illuminate\Http\Request;
use App\Services\Neo4JService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PromptService {

    protected $actions = [
        'express' => [
            'question' => [
                'delete' => 'delete_express_question'
            ],
            'descriptive' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'descriptive\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. '
            ],
            'video_player' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'video player\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. '
            ],
            'text_selection' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'text selection\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. '
            ],
            'image_selection' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create an \'image selection\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. '
            ],
            'single_textbox' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'single textbox\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. '
            ],
            'multiple_textbox' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'multiple textbox\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. If the user has NOT specified textboxes, you MUST generate 2-5 appropriate textbox items based on the question context. Each textbox should have a meaningful label that relates to the question topic.'
            ],
            'open_ended' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'open ended\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. '
            ],
            'pie_chart' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'pie chart\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. If the user has NOT specified chart_items, you MUST generate 3-6 appropriate chart items based on the question context. Each item should have a meaningful label that relates to the question topic.'
            ],
            'bar_chart' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'bar chart\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. If the user has NOT specified chart_items, you MUST generate 3-6 appropriate chart items based on the question context. Each item should have a meaningful label that relates to the question topic.'
            ],
            'slider' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'slider\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. CRITICAL FOR SLIDER QUESTIONS: Understand the distinction between \'items\' and \'choices\'. \'Items\' are the SPECIFIC SCENARIOS/SITUATIONS/ASPECTS being rated (what respondents evaluate). Items must be coherent, related, and form a logical set (e.g., for influence: "When I see messaging on signage", "When staff speaks to me"; for satisfaction: "Atmosphere of the party", "Quality of food"). Generate 2-6 related items that make sense together. \'Choices\' are the SCALE LABELS/VALUES (the rating scale itself). Choices must be contextually appropriate scale labels that match the question\'s intent (e.g., for influence: "Very unlikely to be influenced" to "Highly likely to be influenced"; for satisfaction: "Very dissatisfied" to "Very satisfied"). Generate 3-7 scale labels that form a coherent progression. PREFER TEXT-BASED LABELS over numeric values. DO NOT confuse these - scale labels should NEVER be in \'items\', they belong in \'choices\'. '
            ],
            'ranking' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'ranking\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. If the user has NOT specified items, you MUST generate 3-6 appropriate ranking items based on the question context. Each item should have a meaningful label that relates to the question topic.'
            ],
            'star_rating' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'star rating\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. If the user has NOT specified items, you MUST generate 3-6 appropriate items based on the question context. Each item should be a descriptive statement that represents a specific level or state related to the question topic. For example, if the question is about wellness, items might be "I feel unwell most of the time", "I often struggle with my well-being", "My well-being is manageable but not optimal", "I generally feel well with minor issues", "I feel very healthy, balanced, and well". The items should be contextually relevant and describe how the respondent feels or their situation. DO NOT use generic satisfaction labels like "Very Satisfied", "Satisfied", "Neutral", "Dissatisfied", or "Very Dissatisfied" as these are not appropriate for star rating questions.'
            ],
            'button_rating' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'button rating\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. If the user has NOT specified items, you MUST generate 3-6 appropriate items based on the question context. If the user has NOT specified choices, you MUST generate 3-5 appropriate rating choices with meaningful labels and colors based on the question context.'
            ],
            'segmentation' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'segmentation\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. If the user has NOT specified statements, you MUST generate 3-6 appropriate statement pairs based on the question context. If the user has NOT specified choices, you MUST generate 3-5 appropriate rating choices with meaningful labels and colors based on the question context.'
            ], 
            'matrix' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'matrix\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. CRITICAL FOR MATRIX QUESTIONS: Understand the distinction between \'rows\' and \'columns\'. \'Rows\' are the STATEMENTS/ASPECTS being evaluated (what respondents rate). Rows must be coherent, related statements or aspects that form a logical set (e.g., for satisfaction: "Product quality", "Customer service", "Price value"). Generate 3-8 related rows that make sense together. \'Columns\' are the ANSWER CHOICES/OPTIONS (the rating scale itself). Columns must be contextually appropriate answer options that match the question\'s intent (e.g., for satisfaction: "Very Satisfied" to "Very Dissatisfied"; for agreement: "Strongly Agree" to "Strongly Disagree"). Generate 3-7 columns that form a logical set of answer options appropriate for rating the rows. ALWAYS generate both rows and columns. DO NOT confuse these - answer options should NEVER be in \'rows\', they belong in \'columns\'. '
            ],

            //need to consult this to the analysis or devs
            'pairwise_comparison' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'pairwise comparison\' question. CRITICAL: For pairwise comparison questions, you must FIRST identify 3-5 base options/items from the user\'s topic. Then generate ALL pairwise combinations of these base options. For n base options, you must create exactly n*(n-1)/2 comparison items. Each base option must be assigned a unique sequential value (1, 2, 3, etc.) and the same base option must always use the same value across all items. Statements must be wrapped in <p> tags. Index must start at 1 and increment sequentially. Example: For 3 base options "A", "B", "C" (values 1, 2, 3), generate: (A vs B), (A vs C), (B vs C). IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. '
            ],
            'best-worst_scale' => [
                'create' => 'You are an AI assistant for a survey platform. The user wants to create a \'best-worst scale\' question. IMPORTANT: Always generate a meaningful, concise title (3-8 words) for the question based on its content if the user has not specified one. The title should summarize what the question is about. If the user has NOT specified statements, you MUST generate 6-10 appropriate statements based on the question context. Each statement should be meaningful and relate to the question topic.'
            ]
        ]
    ];

    public string $action;


    public function __construct(
        public $user,
        public string $platform,
        public string $scope,
        public string $intent,
        public array $params = [],
        public mixed $data = null,
        public Neo4JService $neo4jService
    )
    {
        $this->action = $this->platform . '_' . $this->scope . '_' . $this->intent;
    }

    private function getConfigSchema(): ?ObjectSchema
    {
        $repository = app(SchemaRepositoryInterface::class);
        return $repository->getSchema($this->platform, $this->scope, $this->intent);
    }

    public function getSchema(): ObjectSchema | bool | null
    {
        // return $this->isForConversation() ? false : $this->getConfigSchema();
        return $this->isForConversation() || $this->isForGuide() 
            ? false 
            : $this->getConfigSchema();
    }

    public function isForConversation(): bool
    {
        return $this->scope === 'conversation' || $this->intent === 'conversation';
    }

    public function isForGuide(): bool
    {
        // guide only; treat legacy "documentation" as guide (merged)
        return in_array($this->intent, ['guide', 'documentation'], true) || $this->scope === 'guide';
    }

    public function setData(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getSystemPrompt(): string
    {
        if ($this->platform === 'express') {
            $actions = $this->actions[$this->platform][$this->scope][$this->intent] ?? '';
            return $actions;
        }
        
        return '';
    }

    public function getPrompt(Request $request): string
    {
        if ($this->platform === 'express') {
            return "User Request: {$request->message}";
        }
        return "User Request: {$request->message}";
    }

    public static function getActionableId(): string
    {
        return 'ai_action_' . (string) Str::uuid();
    }

    // Removed Qdrant context usage; Neo4J context is accessed via $this->neo4jService
    public function updateContext(string $scope, string $intent, array $params = []): void
    {
        $this->scope = $scope;
        $this->intent = $intent;
        $this->params = $params;
        $this->action = $this->scope . '_' . $this->intent;
    }

    public function checkMissingParameters(array $userData): ?JsonResponse
    {      
        $requiredParams = [];

        $action = $this->action ?? null;

        if (!isset($requiredParams[$action])) {
            return null;
        }

        $missing = [];

        foreach($requiredParams[$action] as $param => $rules) {
            if ($rules['type'] === 'associative array') {
                if (!isset($userData[$param]) || !is_array($userData[$param]) || count($userData[$param]) < ($rules['minItems'] ?? 1)) {
                    $missing[] = $param;
                }
            } else {
                if (!isset($userData[$param]) || trim((string)$userData[$param]) === ''){
                    $missing[] = $param;
                }
            }
        }

        if (!empty($missing) && $missing[0] === 'names') {
            $missing[0] = 'name';
        }

        info($missing);
        if (!empty($missing)) {
            if (count($missing) == 1) {
                return response()->json([
                    'message' => "I'm sorry, I'm missing some information! Could you provide a {$missing[0]} for the {$this->scope} to {$this->intent}? Or would you like me to make one for you?"
                ]);
            }
            if (count($missing) == 2){
                return response()->json([
                    'message' => "I'm sorry, I'm missing some information! Could you provide a {$missing[0]} and {$missing[1]} for the {$this->scope} to {$this->intent}? Or would you like me to make them for you?"
                ]);
            }
            if (count($missing) > 2) {
                $allButLast = array_slice($missing, 0, -1);
                $last = end($missing);

                $missingString = implode(', ', $allButLast) . ' and ' . $last;

                return response()->json([
                    'message' => "I'm sorry, I'm missing some information! Could you provide a {$missingString} for the {$this->scope} to {$this->intent}? Or would you like me to make them for you?"
                ]);
            }   
        }
        
        return null;
    }

    public function checkSubscription(array $data): ?JsonResponse 
    {
        $user = $this->user;
        // if ($this->action === 'app_component_create' && !$user->hasComponent($data['type'])) {
        //     return response()->json(['message' => 'I\'m sorry, you do not have access to this component type! Please upgrade your subscription.']);
        // }
        return null;
    }
}