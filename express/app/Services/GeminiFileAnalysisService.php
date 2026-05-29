<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\ValueObjects\Media\Document; // Document API for file attachments
use App\AI\Enums\AIModel;
use App\Services\Schemas\ScriptQuestionExtractionSchema;

class GeminiFileAnalysisService
{
    /**
     * Analyzes uploaded script file and identifies EXPRESS parts
     */
    public function analyzeScript($file): array
    {
        try {
            $filePath = $file->getRealPath();
            $mimeType = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            $systemPrompt = $this->getAnalysisSystemPrompt();
            // $userPrompt = "Analyze this survey script and identify all parts that belongs to EXPRESS. This is a table format in docs/pdf file. Look for 'EXPRESS' in the upper top right in each of the tables. In each header row, check the UPPER RIGHT field/cell - if it says 'EXPRESS', that question belongs to EXPRESS. Then look at the LEFT side of the same header row to find the Part identifier (like 'a2', 'A2', 'f2', 'F2'). Extract the Part letter (a/A = Part A, f/F = Part F, etc.) and return unique Part identifiers. The format is always: Part letter + number in the header (e.g., 'A2 - Home Responsibilities' means Part A, question 2). Extract all unique Part letters that have 'EXPRESS' label in the top right corner of the table.";
            //$userPrompt = "Analyze this survey script and identify all parts that belong to EXPRESS. Look for tables with header rows. In each header row, check the UPPER RIGHT field/cell - if it says 'EXPRESS', that question belongs to EXPRESS. Then look at the LEFT side of the same header row to find the Part identifier (like 'a2', 'A2', 'f2', 'F2'). Extract the Part letter (a/A = Part A, f/F = Part F, etc.) and return unique Part identifiers. The format is always: Part letter + number in the header (e.g., 'A2 - Home Responsibilities' means Part A, question 2). Extract all unique Part letters that have questions with 'EXPRESS' in the platform field.";
            // $userPrompt = "Analyze the survey script structure. Identify all Main Parts (A, B, C, D, E, F) where the corresponding Platform field explicitly states 'EXPRESS'. For each match, extract the Part identifier (e.g., 'A', 'B', 'F') and return it in the strict format 'Part [Letter]' (e.g., 'Part A', 'Part B', 'Part F'). Return a list of only these unique Part identifiers.";
           // v3 -stable $userPrompt = "As an expert Research Survey Script Analyzer, your task is to identify and extract only the unique Part letters designated for the 'EXPRESS' platform. Scan the header row of every table in the document; if the word 'EXPRESS' appears anywhere in that header—whether in its own cell or combined with other text—that section belongs to the EXPRESS platform. Once an EXPRESS label is identified, look at the Part identifier in the same header (such as 'a2', 'B1', or 'f2') and extract only the primary Part letter. You must treat uppercase and lowercase letters as identical and ignore any trailing numbers or specific question IDs. Your final output should be a simple, consolidated list of the unique Part letters found (e.g., Part A, Part B, Part F), without listing individual question numbers like A1 or B2.";
            // v4 - with validation - stable $userPrompt = "As an expert Research Survey Script Analyzer, your task is to identify and extract only the unique Part letters that are designated for the 'EXPRESS' platform and contain valid question data. Scan the header row of every table in the document; if the word 'EXPRESS' appears anywhere in that header—whether in its own cell or combined with other text—you must then verify that the table is a functional question property table by checking for the presence of fields such as 'Question Type', 'Answer Type', or 'Question Title'. Only if both the 'EXPRESS' label and these specific question data fields are present should you extract the primary Part letter from the identifier (e.g., extracting 'A' from 'a2' or 'F' from 'f1'). If a Part is mentioned in an overview but lacks subsequent property tables, such as the missing data for Part B in the provided script, it must be excluded from your results. You must treat uppercase and lowercase letters as identical, ignore any trailing numbers or specific question IDs, and provide a final, simple consolidated list of only the unique, validated Part letters (e.g., Part A, Part F).";
            // v5 - with correction and validation
            $userPrompt = "As an expert Research Survey Script Analyzer, your task is to identify and extract unique Part letters by treating the individual question property tables as the sole source of truth for platform designation. You must scan the header row of every functional question table throughout the document; if the word 'EXPRESS' appears anywhere in that header—regardless of whether the initial overview or 'Respondent Journey' table categorizes it differently—that specific question is confirmed as an EXPRESS component. For every table where 'EXPRESS' is found, verify it is a valid data table by the presence of fields like 'Question Type', 'Answer Type', or 'Question Title'. Only when these fields and the 'EXPRESS' label coexist should you extract the primary Part letter from the identifier (e.g., extracting 'B' from 'b1' or 'F' from 'f2'), treating uppercase and lowercase as identical and ignoring trailing numbers. If a Part has valid EXPRESS property tables, include it in your results even if an earlier summary table claims it belongs to a different platform; conversely, if a Part is listed as EXPRESS in a summary but has no actual property tables, it must be excluded. Provide a final, simple consolidated list of only the unique, validated Part letters.";

            $document = $this->createDocumentFromFile($filePath, $mimeType, $extension); // Create Document object for Prism file attachment

            $schema = new ObjectSchema(
                name: 'script_analysis',
                description: 'Analysis result identifying EXPRESS parts in the script',
                properties: [
                    new ArraySchema(
                        name: 'parts',
                        description: 'List of unique part identifiers that belong to EXPRESS. Extract Part letters from table headers where the platform field says "EXPRESS". Format: ["Part A", "Part B", "Part C", "Part F", etc.]',
                        items: new StringSchema(
                            name: 'part',
                            description: 'Part identifier in format "Part [Letter]" where Letter is extracted from the header (e.g., "a2" or "A2" → "Part A", "f2" or "F2" → "Part F"). Must be uppercase letter format like "Part A", "Part B", "Part C", etc.'
                        )
                    )
                ],
                requiredFields: ['parts']
            );

            $response = Prism::structured()
                ->using(...AIModel::getSummaryConfig())
                ->withSchema($schema)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt, $document ? [$document] : []) // Attach file document to prompt
                ->usingTemperature(0.1)
                ->withMaxTokens(8192)
                ->asStructured();

            $result = $response->structured ?? [];
            
            Log::info('[GeminiFileAnalysis] Script analysis completed', [
                'parts_found' => count($result['parts'] ?? []),
                'parts' => $result['parts'] ?? []
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('[GeminiFileAnalysis] Failed to analyze script', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Extracts question data for a specific part from the script
     */
    public function getPartQuestionData($file, string $partIdentifier): array
    {
        try {
            $filePath = $file->getRealPath();
            $mimeType = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            $systemPrompt = $this->getQuestionExtractionSystemPrompt();
            //$userPrompt = "Extract ALL complete question data objects from the section identified by '{$partIdentifier}' in this survey script. IMPORTANT: This section may span multiple pages and contain numerous questions. Process the content **sequentially, question by question**, using the question codes (e.g., A1, A2, B1, F1) as clear delimiters to identify the start of a new question. Return data as an array of question objects. For each question, extract: QuestionTitle (REQUIRED string - extract the value from the 'Question Title' field/row in the properties table. This is NOT the question code/header, but the value *after* the 'Question Title' label), QuestionType (optional string like 'Multiple Choice', 'Text', 'Rating', 'Matrix', 'Ranking'), AnswerType (optional string like 'Single Answer', 'Multiple Answer', 'Single Selection', 'Multiple Selection'), QuestionText (REQUIRED string - the question text from the 'Main Language' column in the question details table. CRITICAL: Preserve ALL spacing and text styling (Bold, Italic, Underline) exactly as shown. **Preserve line breaks**—do NOT convert them to single spaces. Do not modify the text in any other way), ProgrammingInstructions (optional string), Labels (optional array of strings - numerical labels/values from the 'Label' column that correspond to each choice, preserve order), Choices (REQUIRED array of strings - these are the answer option texts from the 'Main Language' column after the question text. Extract ALL choice rows from the table, preserving their order from top to bottom), Items (optional array of strings - use JSON strings for complex items), Values (optional array of strings - use JSON strings for complex values). Extract ONLY these fields - be concise and efficient.";
            $userPrompt = "As an expert Data Extractor, your task is to retrieve all complete question data objects for '{$partIdentifier}' from the uploaded script. Scan the document sequentially and use question codes (e.g., A1, A2, B1) as delimiters to identify the start and end of each question, ensuring you continue extraction across page breaks until the next code or Part begins. For each question, extract the 'QuestionTitle' strictly from the 'Question Title' row value in the properties table and the 'QuestionText' from the 'Main Language' column. To ensure speed and prevent system timeouts, convert line breaks in the question text to single spaces while keeping essential bold, italic, or underline styling. You must capture the 'Choices' from the 'Main Language' column; if a question spans multiple tables or pages—as seen in the extensive lists for A10 and A12—you must aggregate all choice rows into a single array before moving to the next question . Additionally, extract the 'QuestionType', 'AnswerType', 'ProgrammingInstructions', and 'Labels' (numerical values from the 'Label' column). Return the final data as a concise array of question objects, focusing on raw data accuracy over complex text normalization to maintain high performance.";

            $document = $this->createDocumentFromFile($filePath, $mimeType, $extension); // Create Document object for Prism file attachment

            $schemaBuilder = new ScriptQuestionExtractionSchema();
            $schema = $schemaBuilder->getSchema();

            $response = Prism::structured()
                ->using(...AIModel::getSummaryConfig())
                ->withSchema($schema)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt, $document ? [$document] : []) 
                ->usingTemperature(0.1)
                ->withMaxTokens(8192)
                ->asStructured();

            $result = $response->structured ?? [];
            
            // Log raw extracted data
            Log::info('[GeminiFileAnalysis] Raw extracted data from script', [
                'part' => $partIdentifier,
                'raw_data' => $result,
                'questions_count' => count($result['questions'] ?? [])
            ]);
            
            // Transform the array format to flat questions array for compatibility
            $questions = $this->transformQuestionData($result, $partIdentifier);
            
            Log::info('[GeminiFileAnalysis] Part question data extracted and transformed', [
                'part' => $partIdentifier,
                'questions_count' => count($questions),
                'transformed_questions' => $questions
            ]);

            return ['questions' => $questions];
        } catch (\Throwable $e) {
            Log::error('[GeminiFileAnalysis] Failed to extract part question data', [
                'part' => $partIdentifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Creates a Document object from uploaded file for Prism
     * Currently only supports PDF files
     */
    private function createDocumentFromFile(string $filePath, string $mimeType, string $extension): ?Document
    {
        try {
            // Only allow PDF files
            if ($extension !== 'pdf' && $mimeType !== 'application/pdf') {
                throw new \Exception("Only PDF files are supported. Received file type: {$mimeType} (extension: {$extension})");
            }
            
            return Document::fromLocalPath(
                path: $filePath,
                title: 'Survey Script'
            );
        } catch (\Throwable $e) {
            Log::error('[GeminiFileAnalysis] Failed to create document from file', [
                'error' => $e->getMessage(),
                'mimeType' => $mimeType,
                'extension' => $extension
            ]);
            throw new \Exception('Failed to process file: ' . $e->getMessage());
        }
    }

    /**
     * Extracts the single part letter from a part identifier (e.g. "Part A" -> "A", "Part B" -> "B").
     */
    private function getPartLetterFromIdentifier(string $partIdentifier): ?string
    {
        $partIdentifier = trim($partIdentifier);
        if (preg_match('/^Part\s+([A-Za-z])$/i', $partIdentifier, $m)) {
            return strtoupper($m[1]);
        }
        // Fallback: take first letter if identifier looks like "Part X" with optional spaces
        if (preg_match('/Part\s+([A-Za-z])/i', $partIdentifier, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    /**
     * Returns whether the script's AnswerType indicates multiple selection (e.g. "Multiple Answer", "Multiple Selection").
     */
    private function isMultipleAnswerFromScript(?string $answerType): bool
    {
        if ($answerType === null || $answerType === '') {
            return false;
        }
        $normalized = strtolower(trim($answerType));
        return in_array($normalized, ['multiple answer', 'multiple selection'], true);
    }

    /**
     * Transforms extracted question data to match the expected format for question creation
     */
    private function transformQuestionData(array $result, string $partIdentifier): array
    {
        $questions = [];
        $expectedPartLetter = $this->getPartLetterFromIdentifier($partIdentifier);

        if (!isset($result['questions']) || !is_array($result['questions'])) {
            return $questions;
        }

        // Transform each question entry to expected format; keep only questions that belong to the selected part
        foreach ($result['questions'] as $index => $questionData) {
            if (!is_array($questionData)) {
                Log::warning('[GeminiFileAnalysis] Skipping invalid question data', [
                    'part' => $partIdentifier,
                    'index' => $index,
                    'data' => $questionData
                ]);
                continue;
            }

            // Filter by part: only include questions whose code matches the selected part (e.g. Part A -> A1, A2, ...)
            if ($expectedPartLetter !== null) {
                $questionCode = $questionData['QuestionCode'] ?? $questionData['question_code'] ?? '';
                $codeLetter = $questionCode !== '' ? strtoupper(substr(trim((string) $questionCode), 0, 1)) : null;
                if ($codeLetter !== null && $codeLetter !== $expectedPartLetter) {
                    Log::info('[GeminiFileAnalysis] Filtering out question from different part', [
                        'part' => $partIdentifier,
                        'expected_letter' => $expectedPartLetter,
                        'question_code' => $questionCode,
                        'index' => $index,
                    ]);
                    continue;
                }
            }

            // Log raw question data before transformation
            Log::info('[GeminiFileAnalysis] Processing question', [
                'part' => $partIdentifier,
                'question_index' => $index,
                'raw_question_data' => $questionData
            ]);
            
            // Normalize question type based on QuestionType and AnswerType
            $questionType = $this->determineQuestionType(
                $questionData['QuestionType'] ?? null,
                $questionData['AnswerType'] ?? null,
                $questionData['AnswerType'] ?? null // Fallback to old AnswerType field
            );
            
            Log::info('[GeminiFileAnalysis] Question type determined', [
                'part' => $partIdentifier,
                'question_index' => $index,
                'question_type' => $questionData['QuestionType'] ?? null,
                'answer_type' => $questionData['AnswerType'] ?? null,
                'determined_type' => $questionType
            ]);
            
            // Process items - handle JSON strings or simple strings
            $items = $this->processItems($questionData['Items'] ?? [], $partIdentifier, $index);
            
            // Process values - handle JSON strings or simple strings
            $values = $this->processValues($questionData['Values'] ?? [], $partIdentifier, $index);
            
            // Process choices - ensure they're strings, preserve order
            $choices = $this->processChoices($questionData['Choices'] ?? [], $partIdentifier, $index);
            
            // Process labels - these are usually numerical labels (like 5, 4, 3, 2, 1) that correspond to choices
            $labels = $this->processLabels($questionData['Labels'] ?? [], $partIdentifier, $index);
            
            // If we have both choices and labels, map them together (labels are usually the values for choices)
            // For text_selection questions, labels become the values associated with choices
            if (!empty($choices) && !empty($labels) && count($choices) === count($labels)) {
                Log::info('[GeminiFileAnalysis] Mapping labels to choices', [
                    'part' => $partIdentifier,
                    'question_index' => $index,
                    'choices_count' => count($choices),
                    'labels_count' => count($labels)
                ]);
                // Labels will be used as values for choices in text_selection questions
                // Store them separately - the mapping will be handled by the question creation logic
            }
            
            Log::info('[GeminiFileAnalysis] Processed question components', [
                'part' => $partIdentifier,
                'question_index' => $index,
                'items_count' => count($items),
                'values_count' => count($values),
                'choices_count' => count($choices),
                'labels_count' => count($labels),
                'has_label_choice_mapping' => (!empty($choices) && !empty($labels) && count($choices) === count($labels))
            ]);
            
            // Build the transformed question data matching executeCreateSurvey format
            // Use QuestionTitle directly as 'name' - it's the question identifier/title from the script
            $questionTitle = $questionData['QuestionTitle'] ?? null;
            if (empty($questionTitle)) {
                Log::warning('[GeminiFileAnalysis] QuestionTitle is missing', [
                    'part' => $partIdentifier,
                    'question_index' => $index
                ]);
            }
            
            // Derive select_multiple for text_selection / image selection from Answer Type (Single Answer vs Multiple Answer)
            $selectMultiple = $this->isMultipleAnswerFromScript($questionData['AnswerType'] ?? null);

            $transformed = [
                'question_type' => $questionType,
                'title_and_question_text' => [
                    'name' => $questionTitle, // Use QuestionTitle from script, not a generated name
                    'question_text' => $questionData['QuestionText'] ?? ''
                ]
            ];

            if ($questionType === 'text_selection' || $questionType === 'image_selection') {
                $transformed['select_multiple'] = $selectMultiple;
            }

            // Add optional fields only if they have values
            if (!empty($questionData['ProgrammingInstructions'])) {
                $transformed['programming_instructions'] = $questionData['ProgrammingInstructions'];
            }
            
            // For text_selection questions, map labels to choices as values
            // The labels (like 5, 4, 3, 2, 1) are the values for the choices
            if ($questionType === 'text_selection' && !empty($choices)) {
                // Create choice objects matching text_selection schema format
                // Schema expects: text, equivalent, value, points, write_in, random_choice, etc.
                $mappedChoices = [];
                foreach ($choices as $idx => $choice) {
                    $labelValue = null;
                    // Map label to choice if labels exist and match count
                    if (!empty($labels) && count($labels) === count($choices)) {
                        $labelValue = $labels[$idx] ?? null;
                    }
                    
                    $choiceText = is_string($choice) ? trim($choice) : (is_array($choice) ? ($choice['text'] ?? json_encode($choice)) : (string)$choice);
                    
                    $mappedChoices[] = [
                        'text' => $choiceText,
                        'equivalent' => $choiceText, // Plain text version (same as text for now)
                        'value' => $labelValue, // The label value (like "5", "4", "3", etc.) or null if no labels
                        'points' => 0, // Default points
                        'write_in' => ['enabled' => false],
                        'random_choice' => false,
                        'select_all_choice' => false,
                        'unselect_all_choice' => false
                    ];
                }
                $transformed['text_options'] = $mappedChoices; // text_selection uses 'text_options' not 'choices'
                Log::info('[GeminiFileAnalysis] Mapped choices for text_selection', [
                    'part' => $partIdentifier,
                    'question_index' => $index,
                    'choices_count' => count($choices),
                    'labels_count' => count($labels ?? []),
                    'mapped_choices' => $mappedChoices
                ]);
            } else {
                // Use choices and labels separately for other question types
                if (!empty($labels)) {
                    $transformed['labels'] = $labels;
                }
                
                if (!empty($choices)) {
                    $transformed['choices'] = $choices;
                }
            }
            
            if (!empty($items)) {
                $transformed['items'] = $items;
            }
            
            if (!empty($values)) {
                $transformed['values'] = $values;
            }
            
            // Log transformed question data
            Log::info('[GeminiFileAnalysis] Question transformed', [
                'part' => $partIdentifier,
                'question_index' => $index,
                'transformed_data' => $transformed
            ]);
            
            $questions[] = $transformed;
        }
        
        return $questions;
    }
    
    /**
     * Determines question type based on QuestionType and AnswerType from script
     */
    private function determineQuestionType(?string $questionType, ?string $answerType, ?string $fallbackAnswerType): string
    {
        // Normalize inputs
        $questionTypeNormalized = $questionType ? strtolower(trim($questionType)) : '';
        $answerTypeNormalized = $answerType ? strtolower(trim($answerType)) : '';
        $fallbackNormalized = $fallbackAnswerType ? strtolower(trim($fallbackAnswerType)) : '';
        
        // Replace spaces and hyphens with underscores
        $questionTypeNormalized = str_replace([' ', '-'], '_', $questionTypeNormalized);
        $answerTypeNormalized = str_replace([' ', '-'], '_', $answerTypeNormalized);
        $fallbackNormalized = str_replace([' ', '-'], '_', $fallbackNormalized);
        
        Log::info('[GeminiFileAnalysis] Determining question type', [
            'question_type' => $questionType,
            'question_type_normalized' => $questionTypeNormalized,
            'answer_type' => $answerType,
            'answer_type_normalized' => $answerTypeNormalized,
            'fallback' => $fallbackAnswerType,
            'fallback_normalized' => $fallbackNormalized
        ]);
        
        // Check for Multiple Choice + Single Answer combination
        if (
            (in_array($questionTypeNormalized, ['multiple_choice', 'multiplechoice', 'choice']) &&
             in_array($answerTypeNormalized, ['single_answer', 'singleanswer', 'single_selection', 'singleselection', 'one_answer', 'oneanswer'])) ||
            (in_array($questionTypeNormalized, ['multiple_choice', 'multiplechoice', 'choice']) &&
             in_array($fallbackNormalized, ['single_answer', 'singleanswer', 'single_selection', 'singleselection', 'one_answer', 'oneanswer']))
        ) {
            Log::info('[GeminiFileAnalysis] Mapped to text_selection (Multiple Choice + Single Answer)');
            return 'text_selection';
        }
        
        // Check for Multiple Choice + Multiple Answer combination
        if (
            (in_array($questionTypeNormalized, ['multiple_choice', 'multiplechoice', 'choice']) &&
             in_array($answerTypeNormalized, ['multiple_answer', 'multipleanswer', 'multiple_selection', 'multipleselection', 'multiple_answers'])) ||
            (in_array($questionTypeNormalized, ['multiple_choice', 'multiplechoice', 'choice']) &&
             in_array($fallbackNormalized, ['multiple_answer', 'multipleanswer', 'multiple_selection', 'multipleselection', 'multiple_answers']))
        ) {
            Log::info('[GeminiFileAnalysis] Mapped to text_selection (Multiple Choice + Multiple Answer)');
            return 'text_selection';
        }
        
        // Map based on QuestionType if available
        if (!empty($questionTypeNormalized)) {
            $typeMapping = [
                'multiple_choice' => 'text_selection',
                'multiplechoice' => 'text_selection',
                'choice' => 'text_selection',
                'text' => 'single_textbox',
                'textbox' => 'single_textbox',
                'single_textbox' => 'single_textbox',
                'textarea' => 'open_ended',
                'open_ended' => 'open_ended',
                'rating' => 'star_rating',
                'star_rating' => 'star_rating',
                'scale' => 'slider',
                'slider' => 'slider',
                'matrix' => 'matrix',
                'ranking' => 'ranking',
                'pairwise' => 'pairwise_comparison',
                'pairwise_comparison' => 'pairwise_comparison',
                'best_worst' => 'best_worst_scale',
                'best_worst_scale' => 'best_worst_scale',
                'button_rating' => 'button_rating',
            ];
            
            if (isset($typeMapping[$questionTypeNormalized])) {
                Log::info('[GeminiFileAnalysis] Mapped by QuestionType', [
                    'mapped_type' => $typeMapping[$questionTypeNormalized]
                ]);
                return $typeMapping[$questionTypeNormalized];
            }
        }
        
        // Fallback to AnswerType mapping if QuestionType not found
        if (!empty($fallbackNormalized)) {
            $answerTypeMapping = [
                'multiple_choice' => 'text_selection',
                'single_choice' => 'text_selection',
                'radio' => 'text_selection',
                'checkbox' => 'text_selection',
                'dropdown' => 'text_selection',
                'text' => 'single_textbox',
                'textarea' => 'open_ended',
                'rating' => 'star_rating',
                'scale' => 'slider',
                'matrix' => 'matrix',
                'ranking' => 'ranking',
                'pairwise' => 'pairwise_comparison',
                'best_worst' => 'best_worst_scale',
            ];
            
            if (isset($answerTypeMapping[$fallbackNormalized])) {
                Log::info('[GeminiFileAnalysis] Mapped by AnswerType fallback', [
                    'mapped_type' => $answerTypeMapping[$fallbackNormalized]
                ]);
                return $answerTypeMapping[$fallbackNormalized];
            }
        }
        
        // Default to descriptive if nothing matches
        Log::info('[GeminiFileAnalysis] Using default type: descriptive');
        return 'descriptive';
    }
    
    /**
     * Normalizes AnswerType to match expected question type format (deprecated - use determineQuestionType instead)
     */
    private function normalizeAnswerType(string $answerType): string
    {
        $normalized = strtolower(trim($answerType));
        $normalized = str_replace([' ', '-'], '_', $normalized);
        
        // Map common variations to expected types
        $typeMapping = [
            'multiple_choice' => 'text_selection',
            'single_choice' => 'text_selection',
            'radio' => 'text_selection',
            'checkbox' => 'text_selection',
            'dropdown' => 'text_selection',
            'text' => 'single_textbox',
            'textarea' => 'open_ended',
            'rating' => 'star_rating',
            'scale' => 'slider',
            'matrix' => 'matrix',
            'ranking' => 'ranking',
            'pairwise' => 'pairwise_comparison',
            'best_worst' => 'best_worst_scale',
        ];
        
        return $typeMapping[$normalized] ?? $normalized;
    }
    
    /**
     * Processes items array, handling JSON strings or simple strings, preserving order based on numbers
     */
    private function processItems(array $items, string $partIdentifier, int $index): array
    {
        $processed = [];
        $itemsWithOrder = [];
        
        Log::info('[GeminiFileAnalysis] Processing items', [
            'part' => $partIdentifier,
            'question_index' => $index,
            'raw_items' => $items,
            'items_count' => count($items)
        ]);
        
        // First pass: collect items with their order
        foreach ($items as $itemIndex => $item) {
            $order = $itemIndex + 1; // Default order based on position
            
            if (is_string($item)) {
                // Try to decode JSON string, otherwise use as-is
                $decoded = json_decode($item, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Extract order from decoded item if present
                    $order = $decoded['order'] ?? $decoded['number'] ?? $order;
                    $itemsWithOrder[] = ['item' => $decoded, 'order' => $order];
                    Log::debug('[GeminiFileAnalysis] Decoded JSON item', [
                        'part' => $partIdentifier,
                        'question_index' => $index,
                        'item_index' => $itemIndex,
                        'order' => $order,
                        'decoded' => $decoded
                    ]);
                } else {
                    // Simple string item
                    $itemsWithOrder[] = ['item' => ['text' => $item], 'order' => $order];
                }
            } elseif (is_array($item)) {
                // Extract order if present
                $order = $item['order'] ?? $item['number'] ?? $order;
                $itemsWithOrder[] = ['item' => $item, 'order' => $order];
            }
        }
        
        // Sort by order to preserve numerical sequence
        usort($itemsWithOrder, function($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });
        
        // Extract items in sorted order
        foreach ($itemsWithOrder as $itemData) {
            $processed[] = $itemData['item'];
        }
        
        Log::info('[GeminiFileAnalysis] Items processed', [
            'part' => $partIdentifier,
            'question_index' => $index,
            'processed_items' => $processed,
            'order_preserved' => true
        ]);
        
        return $processed;
    }
    
    /**
     * Processes values array, handling JSON strings or simple strings, preserving order based on numbers
     */
    private function processValues(array $values, string $partIdentifier, int $index): array
    {
        $processed = [];
        $valuesWithOrder = [];
        
        Log::info('[GeminiFileAnalysis] Processing values', [
            'part' => $partIdentifier,
            'question_index' => $index,
            'raw_values' => $values,
            'values_count' => count($values)
        ]);
        
        // First pass: collect values with their order
        foreach ($values as $valueIndex => $value) {
            $order = $valueIndex + 1; // Default order based on position
            
            if (is_string($value)) {
                // Try to decode JSON string, otherwise use as-is
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Extract order from decoded value if present
                    $order = $decoded['order'] ?? $decoded['number'] ?? $decoded['value'] ?? $order;
                    $valuesWithOrder[] = ['value' => $decoded, 'order' => $order];
                    Log::debug('[GeminiFileAnalysis] Decoded JSON value', [
                        'part' => $partIdentifier,
                        'question_index' => $index,
                        'value_index' => $valueIndex,
                        'order' => $order,
                        'decoded' => $decoded
                    ]);
                } else {
                    // Simple string value - try to parse as number
                    if (is_numeric($value)) {
                        $order = (float)$value;
                        $valuesWithOrder[] = ['value' => ['text' => $value, 'value' => (float)$value], 'order' => $order];
                    } else {
                        $valuesWithOrder[] = ['value' => ['text' => $value], 'order' => $order];
                    }
                }
            } elseif (is_array($value)) {
                // Extract order if present
                $order = $value['order'] ?? $value['number'] ?? $value['value'] ?? $order;
                $valuesWithOrder[] = ['value' => $value, 'order' => $order];
            }
        }
        
        // Sort by order to preserve numerical sequence (descending for labels like 5,4,3,2,1)
        usort($valuesWithOrder, function($a, $b) {
            return ($b['order'] ?? 999) <=> ($a['order'] ?? 999); // Descending order
        });
        
        // Extract values in sorted order
        foreach ($valuesWithOrder as $valueData) {
            $processed[] = $valueData['value'];
        }
        
        Log::info('[GeminiFileAnalysis] Values processed', [
            'part' => $partIdentifier,
            'question_index' => $index,
            'processed_values' => $processed,
            'order_preserved' => true
        ]);
        
        return $processed;
    }
    
    /**
     * Processes choices array, ensuring they're strings and preserving order
     */
    private function processChoices(array $choices, string $partIdentifier, int $index): array
    {
        $processed = [];
        
        Log::info('[GeminiFileAnalysis] Processing choices', [
            'part' => $partIdentifier,
            'question_index' => $index,
            'raw_choices' => $choices,
            'choices_count' => count($choices)
        ]);
        
        // Preserve order - process in the order they appear
        foreach ($choices as $choiceIndex => $choice) {
            if (is_string($choice)) {
                $processed[] = $choice;
            } elseif (is_array($choice)) {
                // If it's an array, extract text/equivalent, preserve order if specified
                $choiceText = $choice['text'] ?? $choice['equivalent'] ?? $choice['name'] ?? json_encode($choice);
                $processed[] = $choiceText;
            } else {
                $processed[] = (string)$choice;
            }
        }
        
        Log::info('[GeminiFileAnalysis] Choices processed', [
            'part' => $partIdentifier,
            'question_index' => $index,
            'processed_choices' => $processed,
            'order_preserved' => true
        ]);
        
        return $processed;
    }
    
    /**
     * Processes labels array, ensuring they're strings
     */
    private function processLabels(array $labels, string $partIdentifier, int $index): array
    {
        $processed = [];
        
        Log::info('[GeminiFileAnalysis] Processing labels', [
            'part' => $partIdentifier,
            'question_index' => $index,
            'raw_labels' => $labels,
            'labels_count' => count($labels)
        ]);
        
        foreach ($labels as $labelIndex => $label) {
            if (is_string($label)) {
                $processed[] = $label;
            } elseif (is_array($label)) {
                $processed[] = $label['text'] ?? $label['name'] ?? json_encode($label);
            } else {
                $processed[] = (string)$label;
            }
        }
        
        Log::info('[GeminiFileAnalysis] Labels processed', [
            'part' => $partIdentifier,
            'question_index' => $index,
            'processed_labels' => $processed
        ]);
        
        return $processed;
    }

    /**
     * Gets system prompt for script analysis
     */
    private function getAnalysisSystemPrompt(): string
    {
        $promptPath = config_path('ai_scriptprompt.php');
        if (file_exists($promptPath)) {
            return include $promptPath;
        }
        return "You are an expert at analyzing survey scripts for the EXPRESS survey platform. Look for tables with header rows. In each header row, check the UPPER RIGHT field/cell - if it says 'EXPRESS', that question belongs to EXPRESS. Then look at the LEFT side of the same header row to find the Part identifier (like 'a2', 'A2', 'f2', 'F2'). Extract the Part letter (a/A = Part A, f/F = Part F, etc.) and return unique Part identifiers in format 'Part [Letter]' (e.g., 'Part A', 'Part F'). Return only the part identifiers that have 'EXPRESS' in the platform field.";
    }

    /**
     * Gets system prompt for question extraction
     */
    private function getQuestionExtractionSystemPrompt(): string
    {
        return "You are an expert at extracting survey question data from scripts. Extract ONLY the essential question data from the specified part. For each question, extract: QuestionTitle (REQUIRED - this is the VALUE in the 'Question Title' field/row in the question properties table. Look for a row labeled 'Question Title' and extract the text/value in the RIGHT column. Example: 'Survey is Enjoyable'. This is NOT the header like 'F2 - Survey is Enjoyable', but the actual Question Title field value), QuestionType (like 'Multiple Choice', 'Text', 'Rating'), AnswerType (like 'Single Answer', 'Multiple Answer'), QuestionText (REQUIRED - the question text from the 'Main Language' column in the question details table. CRITICAL: Preserve ALL spacing between words and characters, and preserve text styling formatting such as Bold, Italic, and Underline exactly as shown. However, do NOT preserve line breaks - convert line breaks to single spaces. Do not modify or normalize the text in any other way), ProgrammingInstructions (if present), Labels (numerical labels/values from the 'Label' column that correspond to each choice, preserve order), Choices (REQUIRED - these are the answer option texts that appear as table rows under the 'Main Language' column AFTER the question text row. Each table row represents one choice option. Extract ALL choice rows from the table, preserving their order from top to bottom. The choices appear in a table format with columns 'Main Language', 'Translation', and 'Label' - extract the text from the 'Main Language' column for each choice row), Items (if applicable, preserve order), Values (if applicable, preserve order). IMPORTANT: Choices appear in a table format. Look for rows under the 'Main Language' column after the question text - these are the choices. Preserve the order of Choices, Items, and Values based on their position in the table/script. Do not extract any other data. Be concise and efficient.";
       // return "You are an expert at extracting survey data. For the specified Part Letter, locate and extract every question (e.g., A1 through A18) by scanning the document until the next Part starts. For each question, extract the 'QuestionTitle' from the 'Question Title' row value and the 'QuestionText' from the 'Main Language' column, converting line breaks to spaces while keeping bold/italic tags. You must capture all 'Choices' from the 'Main Language' column following the question; if a list of choices is interrupted by page breaks or logos—as seen in the extensive brand lists for A10 and A12—continue extracting them until a new Question ID header appears . Also, include 'QuestionType', 'AnswerType', 'ProgrammingInstructions', and the 'Labels' for each choice. Do not perform any complex text normalization or styling preservation beyond basic HTML-like tags, and output the data in a clean, sequential list to prevent processing delays.";
    }
}