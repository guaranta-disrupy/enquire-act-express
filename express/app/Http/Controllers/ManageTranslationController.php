<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Questionnaire;
use App\Models\Question;
use App\Services\AITranslationService;
use App\Jobs\TranslateQuestionGroupJob;

class ManageTranslationController extends Controller
{
    public function downloadQuestionnaire(Request $request, $questionnaire_code)
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->with('questions')->first();
        $language = $request->language ?: ' ';
        $name = $request->name;
        
        //dd($questionnaire->setup);
        if (!$questionnaire) {
            abort(404, 'Questionnaire not found');
        }
        
        $variable_start = collect($questionnaire->setup)->get('variable_start', 1);
        $questions = collect($questionnaire->questions)->sortBy('order')->toArray();

        $filename = $name ?? 'questionnaire_' . $questionnaire_code . '_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return Response::stream(function () use ($questions, $variable_start, $questionnaire, $language) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Order','Label', 'Original','Translated']);
            foreach ($questions as $question) {

                $number = $question['order'] + ($variable_start - 1);
                fputcsv($handle, [
                    'Q'.$number,
                    'Question',
                    $question['question'] ?? '',
                    $question['setup']['language'][$language]['question'] ?? '',
                ]);

                if ($question['question_type_id'] == 2 || $question['question_type_id'] == 3) {
                    foreach ($question['choices'] ?? [] as $index => $choice) {
                        fputcsv($handle, [
                            '',
                            'option', 
                            $choice['name'] ?? '',
                            $choice['language'][$language]['name'] ?? '',
                        ]);

                        if($choice['enable_wr'] == '1'){
                            fputcsv($handle, [
                                '',
                                'placeholder', 
                                $choice['wc']['setup']['placeholder'] ?? '',
                                $choice['language'][$language]['placeholder'] ?? '',
                            ]);                          
                        }
                    }
                }
                if ($question['question_type_id'] == 4) {
                    if(isset($question['setup']['prependString'])){
                        fputcsv($handle, [
                            '','before_textbox', $question['setup']['prependString'] ?? '', $question['setup']['language'][$language]['prependString'] ?? '',
                        ]);
                    }
                    if(isset($question['setup']['appendString'])){
                        fputcsv($handle, [
                            '','after_textbox', $question['setup']['appendString'] ?? '', $question['setup']['language'][$language]['appendString'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 5) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, [
                            '','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 7) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, [
                            '','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 8) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, [
                            '','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                    foreach ($question['values'] ?? [] as $index => $value) {
                        fputcsv($handle, [
                            '','value', $value['name'] ?? '', $value['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 9 || $question['question_type_id'] == 10) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, [
                            '','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 11) {
                    foreach ($question['columns'] ?? [] as $index => $column) {
                        fputcsv($handle, [
                            '','column', $column['name'] ?? '', $column['language'][$language]['name'] ?? '',
                        ]);
                    }
                    foreach ($question['rows'] ?? [] as $index => $row) {
                        fputcsv($handle, [
                            '','row', $row['name'] ?? '', $row['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 12) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, [
                            '','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 13) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, ['','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 14) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, ['','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 15) {
                    fputcsv($handle, ['','header', $question['setup']['header'] ?? '']);
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, ['','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                    foreach ($question['values'] ?? [] as $index => $value) {
                        fputcsv($handle, ['','value', $value['name'] ?? '', $value['language'][$language]['name'] ?? '',
                        ]);
                    }
                    fputcsv($handle, ['','footer', $question['setup']['footer'] ?? '', $question['setup']['language'][$language]['footer'] ?? '',
                    ]);
                }
                if ($question['question_type_id'] == 16) {
                    fputcsv($handle, ['','labelA', $question['setup']['labels']['statementA'] ?? '', $question['setup']['language'][$language]['labels']['statementA'] ?? '',
                    ]);
                    fputcsv($handle, ['','labelB', $question['setup']['labels']['statementB'] ?? '', $question['setup']['language'][$language]['labels']['statementB'] ?? '',
                    ]);

                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, ['','statementA', $item['statementA'] ?? '', $item['language'][$language]['statementA'] ?? '',
                        ]);
                        fputcsv($handle, ['','statementB', $item['statementB'] ?? '', $item['language'][$language]['statementB'] ?? '',
                        ]);

                    }
                    foreach ($question['values'] ?? [] as $index => $value) {
                        fputcsv($handle, ['','value', $value['name'] ?? '', $value['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 17) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, ['','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 18) {
                    foreach ($question['items'] ?? [] as $index => $item) {
                        fputcsv($handle, ['','item', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 19) {
                    foreach ($question['choices'] ?? [] as $index => $item) {
                        fputcsv($handle, ['','option', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                    if(isset($question['setup']['footer'])){
                        fputcsv($handle, ['','footer', $question['setup']['footer'] ?? '', $question['setup']['language'][$language]['footer'] ?? '',
                        ]);
                    }
                }
                if ($question['question_type_id'] == 20 || $question['question_type_id'] == 22) {
                    fputcsv($handle, ['','labelA', $question['setup']['bestWostScale']['labelA'] ?? '', $question['setup']['language'][$language]['labels']['statementA'] ?? '',
                    ]);
                    fputcsv($handle, ['','labelB', $question['setup']['bestWostScale']['labelB'] ?? '', $question['setup']['language'][$language]['labels']['statementB'] ?? '',
                    ]);
                    foreach ($question['choices'] ?? [] as $index => $item) {
                        fputcsv($handle, ['','option', $item['name'] ?? '', $item['language'][$language]['name'] ?? '',
                        $item['language'][$language]['name'] ?? '',
                        ]);
                    }
                }
                
            }
            //System Messages
            $messages = $questionnaire->setup['messages'] ?? [];
            fputcsv($handle, ['System Messages','',$messages['loading'] ?? '', $questionnaire->setup['language'][$language]['loading'] ?? '',
            ]);
            fputcsv($handle, ['','saving',$messages['saving'] ?? '', $questionnaire->setup['language'][$language]['saving'] ?? '',
            ]);
            fputcsv($handle, ['','next_button',$messages['nextBtn'] ?? '', $questionnaire->setup['language'][$language]['nextBtn'] ?? '',
            ]);
            fputcsv($handle, ['','prev_button',$messages['prevBtn'] ?? '', $questionnaire->setup['language'][$language]['prevBtn'] ?? '',
            ]);
            fputcsv($handle, ['','final_button',$messages['finalBtn'] ?? '', $questionnaire->setup['language'][$language]['finalBtn'] ?? '',
            ]);
            fputcsv($handle, ['','warning1',$messages['input1_warning_min'] ?? '', $questionnaire->setup['language'][$language]['input1_warning_min'] ?? '',
            ]);
            fputcsv($handle, ['','warning2',$messages['input1_warning_max'] ?? '', $questionnaire->setup['language'][$language]['input1_warning_max'] ?? '',
            ]);
            fputcsv($handle, ['','warning3',$messages['input2_warning_minmax'] ?? '', $questionnaire->setup['language'][$language]['input2_warning_minmax'] ?? '',
            ]);
            fputcsv($handle, ['','warning4',$messages['input2_warning_min'] ?? '', $questionnaire->setup['language'][$language]['input2_warning_min'] ?? '',
            ]);
            fputcsv($handle, ['','warning5',$messages['input2_warning_max'] ?? '', $questionnaire->setup['language'][$language]['input2_warning_max'] ?? '',
            ]);
            fputcsv($handle, ['','warning6',$messages['input9_warning_alphanumeric'] ?? '', $questionnaire->setup['language'][$language]['input9_warning_alphanumeric'] ?? '',
            ]);
            fputcsv($handle, ['','warning7',$messages['input8_warning_letters'] ?? '', $questionnaire->setup['language'][$language]['input8_warning_letters'] ?? '',
            ]);
            fputcsv($handle, ['','warning8',$messages['input4_warning_invalidemail'] ?? '', $questionnaire->setup['language'][$language]['input4_warning_invalidemail'] ?? '',
            ]);
            fputcsv($handle, ['','required',$messages['required_warning'] ?? '', $questionnaire->setup['language'][$language]['required_warning'] ?? '',
            ]);
            $redirects = $questionnaire->setup['redirects'] ?? [];
            fputcsv($handle, ['','Complete',$redirects['complete']['content'] ?? '', $questionnaire->setup['language'][$language]['complete'] ?? '',
            ]);
            fputcsv($handle, ['','Quality Control',$redirects['qualityControl']['content'] ?? '', $questionnaire->setup['language'][$language]['qualityControl'] ?? '',
            ]);
            fputcsv($handle, ['','Screenout',$redirects['screenout']['content'] ?? '', $questionnaire->setup['language'][$language]['screenout'] ?? '',
            ]);

            $modals = $questionnaire->setup['modals'] ?? [];
            fputcsv($handle, ['','Connection Lost Title',$modals['connectionLost']['title'] ?? '', $questionnaire->setup['language'][$language]['connectionLostTitle'] ?? '',
            ]);
            fputcsv($handle, ['','Connection Lost Content',$modals['connectionLost']['content'] ?? '', $questionnaire->setup['language'][$language]['connectionLostContent'] ?? '',
            ]);

            fclose($handle);
        }, 200, $headers);  
    }
    public function uploadTranslationCSV(Request $request, $code)
    {

        
        $questionnaire = Questionnaire::whereCode($code)->with('questions')->first();
        if (!$questionnaire) {
            abort(404, 'Questionnaire not found');
        }
        

        $csvData = $request->csvData ?? [];
       //dd($csvData);     
        if (empty($csvData)) {
            return response()->json(['error' => 'CSV data is required'], 400);
        }

        // Skip header row if present
        $dataRows = collect($csvData)->skip(1)->filter(function($row) {
            return !empty($row) && is_array($row);
        })->values()->toArray();

        $groupedData = [];
        $currentQuestion = null;
        $systemMessages = [];
        $inSystemMessages = false;


        foreach ($dataRows as $row) {
            $order = isset($row[0]) ? trim($row[0]) : '';
            $label = isset($row[1]) ? strtolower(trim($row[1])) : '';
            
            $orig = isset($row[2]) ? trim($row[2]) : '';
            $trans = isset($row[3]) ? trim($row[3]) : '';
            
            // Check if this is a new question (starts with 'Q' followed by number)
            if (!empty($order) && preg_match('/^Q\d+/i', $order)) {
                $currentQuestion = $order;
                $inSystemMessages = false;
                $groupedData[$currentQuestion] = [
                    'orig' => $orig ?? '',
                    'trans' => $trans ?? '',
                    'options' => [],
                    'items' => [],
                    'values' => [],
                    'columns' => [],
                    'rows' => []
                ];

               
            }
            // Check if this is System Messages section
            elseif (!empty($order) && stripos($order, 'System Messages') !== false) {
                $currentQuestion = null;
                $inSystemMessages = true;
                $systemMessages[] = [
                    'orig' => $orig,
                    'trans' => $trans,
                ];
            }
            // Handle system messages rows (empty order but in system messages section)
            elseif ($inSystemMessages && empty($order)) {
                    $systemMessages[] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
            }
            // Process rows for current question
            elseif ($currentQuestion !== null) {
                // Handle question text
                
                if ($label === 'question') {
                    $groupedData[$currentQuestion]['orig'] = $orig;
                    $groupedData[$currentQuestion]['trans'] = $trans;
                }
                // Handle options
                elseif ($label === 'option') {
                    $groupedData[$currentQuestion]['options'][] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                    
                }
                elseif ($label === 'placeholder') {
                    $groupedData[$currentQuestion]['options'][count($groupedData[$currentQuestion]['options'])-1]['placeholder'] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                elseif ($label === 'before_textbox') {
                    $groupedData[$currentQuestion]['prependString'] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                elseif ($label === 'after_textbox') {
                    $groupedData[$currentQuestion]['appendString'] = [  
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                // Handle items
                elseif ($label === 'item') {
                    $groupedData[$currentQuestion]['items'][] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                elseif ($label === 'column') {
                    $groupedData[$currentQuestion]['columns'][] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                elseif ($label === 'row') {
                    $groupedData[$currentQuestion]['rows'][] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                // Handle statementA
                elseif ($label === 'statementa') {
                    $groupedData[$currentQuestion]['items'][] = [
                        'statementA' => [
                            'orig' => $orig,
                            'trans' => $trans,
                         ],
                    ];
                }
                // Handle statementB
                elseif ($label === 'statementb') {
                    $groupedData[$currentQuestion]['items'][count($groupedData[$currentQuestion]['items'])-1]['statementB'] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                // Handle values
                elseif ($label === 'value') {
                    $groupedData[$currentQuestion]['values'][] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                // Handle header
                elseif ($label === 'header') {
                    $groupedData[$currentQuestion]['header'] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                // Handle footer
                elseif ($label === 'footer') {
                    $groupedData[$currentQuestion]['footer'] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                // Handle labelA
                elseif ($label === 'labela') {
                    $groupedData[$currentQuestion]['labelA'] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
                // Handle labelB
                elseif ($label === 'labelb') {
                    $groupedData[$currentQuestion]['labelB'] = [
                        'orig' => $orig,
                        'trans' => $trans,
                    ];
                }
            }
        }
        

        // [
        //     "Q1" => [
        //         "orig" => 'oiginal text',
        //         "trans" => 'translated text',
        //         "options" => [["orig"=>"original text", "trans"=>"translated text"],["orig"=>"original text", "trans"=>"translated text"]],
        //     ]
        // ]

        $variable_start = collect($questionnaire->setup)->get('variable_start', 1);
        $questions = collect($questionnaire->questions)->sortBy('order')->toArray();
        
    
        foreach ($questions as $key => $question) {
            // Ensure setup exists
            if (!isset($questions[$key]['setup'])) {
                $questions[$key]['setup'] = [];
            }
            
            // Ensure language array exists
            if (!isset($questions[$key]['setup']['language'])) {
                $questions[$key]['setup']['language'] = [];
            }
            
            // Calculate question number in CSV format (Q1, Q2, etc.)
            $questionNumber = intval($question['order']) + (intval($variable_start) - 1);
            $QN = 'Q' . $questionNumber;
            
            // Get grouped data for this question if it exists
            if (isset($groupedData[$QN])) {
                $questionData = $groupedData[$QN];
                
                // Set up language structure
                if(!isset($questions[$key]['setup']['language'][$request->language])){
                    $questions[$key]['setup']['language'][$request->language] = [];
                }
                
                // Set question text translation
                if (!empty($questionData['trans'])) {
                    $questions[$key]['setup']['language'][$request->language]['question'] = $questionData['trans'];
                    //dd($questionData);
                }
                
                // Handle options for question types 2, 3, and 20
                if (($questions[$key]['question_type_id'] == 2 || 
                     $questions[$key]['question_type_id'] == 3 || 
                     $questions[$key]['question_type_id'] == 19 ||
                     $questions[$key]['question_type_id'] == 20) && 
                    !empty($questionData['options'])) {
                    
                    // Ensure choices array exists
                    if(!isset($questions[$key]['choices']) || !is_array($questions[$key]['choices'])){
                        $questions[$key]['choices'] = [];
                    }
                    
                    foreach ($questionData['options'] as $optionIndex => $option) {
                        // Ensure the choice at this index exists
                        if(!isset($questions[$key]['choices'][$optionIndex])){
                            $questions[$key]['choices'][$optionIndex] = [];
                        }
                        
                        // Initialize language structure
                        if(!isset($questions[$key]['choices'][$optionIndex]['language'])){
                            $questions[$key]['choices'][$optionIndex]['language'] = [];
                        }
                        
                        // Assign translated name
                        if (!empty($option['trans'])) {
                            $questions[$key]['choices'][$optionIndex]['language'][$request->language] = [
                                'name' => $option['trans']
                            ];
                            
                            // Assign translated placeholder
                            if(!empty($option['placeholder'])){
                                $questions[$key]['choices'][$optionIndex]['language'][$request->language]['placeholder'] = $option['placeholder']['trans'];
                            }
                        }
                    }
                }

                // Handle prependString for question type 4
                if (!empty($questionData['prependString']) && $questions[$key]['question_type_id'] == 4) {
                    $questions[$key]['setup']['language'][$request->language]['prependString'] = $questionData['prependString']['trans'];
                }
                // Handle appendString for question type 4
                if (!empty($questionData['appendString']) && $questions[$key]['question_type_id'] == 4) {
                    $questions[$key]['setup']['language'][$request->language]['appendString'] = $questionData['appendString']['trans'];
                }
                
                // Handle items for various question types
                if (!empty($questionData['items'])) {
                    $itemQuestionTypes = [5, 7, 8, 9, 10, 12, 13, 14, 15, 16, 17, 18];
                    
                    if (in_array($questions[$key]['question_type_id'], $itemQuestionTypes)) {
                        // Ensure items array exists
                        if(!isset($questions[$key]['items']) || !is_array($questions[$key]['items'])){
                            $questions[$key]['items'] = [];
                        }
                        
                        foreach ($questionData['items'] as $itemIndex => $item) {
                            // Ensure the item at this index exists
                            if(!isset($questions[$key]['items'][$itemIndex])){
                                $questions[$key]['items'][$itemIndex] = [];
                            }
                            
                            // Initialize language structure
                            if(!isset($questions[$key]['items'][$itemIndex]['language'])){
                                $questions[$key]['items'][$itemIndex]['language'] = [];
                            }
                            
                            // Assign translated name
                            if (!empty($item['trans'])) {
                                $questions[$key]['items'][$itemIndex]['language'][$request->language] = [
                                    'name' => $item['trans']
                                ];


                            }

                            // Assign translated statementA
                            if(!empty($item['statementA'])){
                                $questions[$key]['items'][$itemIndex]['language'][$request->language]['statementA'] = $item['statementA']['trans'];
                            }
                            // Assign translated statementB
                            if(!empty($item['statementB'])){
                                $questions[$key]['items'][$itemIndex]['language'][$request->language]['statementB'] = $item['statementB']['trans'];
                            }

                        }
                    }
                }
                
                // Handle values for question types 8, 15, 16
                if (!empty($questionData['values'])) {
                    $valueQuestionTypes = [8, 15, 16];
                    
                    if (in_array($questions[$key]['question_type_id'], $valueQuestionTypes)) {
                        // Ensure values array exists
                        if(!isset($questions[$key]['values']) || !is_array($questions[$key]['values'])){
                            $questions[$key]['values'] = [];
                        }
                        
                        foreach ($questionData['values'] as $valueIndex => $value) {
                            // Ensure the value at this index exists
                            if(!isset($questions[$key]['values'][$valueIndex])){
                                $questions[$key]['values'][$valueIndex] = [];
                            }
                            
                            // Initialize language structure
                            if(!isset($questions[$key]['values'][$valueIndex]['language'])){
                                $questions[$key]['values'][$valueIndex]['language'] = [];
                            }
                            
                            // Assign translated name
                            if (!empty($value['trans'])) {
                                $questions[$key]['values'][$valueIndex]['language'][$request->language] = [
                                    'name' => $value['trans']
                                ];
                            }
                        }
                    }
                }

                // Handle choices for slider questions (type 8) - these are the scale labels/choices
                if (!empty($questionData['choices']) && $questions[$key]['question_type_id'] == 8) {
                    if(!isset($questions[$key]['choices']) || !is_array($questions[$key]['choices'])){
                        $questions[$key]['choices'] = [];
                    }
                    
                    foreach ($questionData['choices'] as $choiceIndex => $choice) {
                        if(!isset($questions[$key]['choices'][$choiceIndex])){
                            $questions[$key]['choices'][$choiceIndex] = [];
                        }
                        
                        if(!isset($questions[$key]['choices'][$choiceIndex]['language'])){
                            $questions[$key]['choices'][$choiceIndex]['language'] = [];
                        }
                        
                        if (!empty($choice['trans'])) {
                            $questions[$key]['choices'][$choiceIndex]['language'][$request->language] = [
                                'name' => $choice['trans']
                            ];
                        }
                    }
                }


                // Handle columns for question type 11
                if (!empty($questionData['columns']) && $questions[$key]['question_type_id'] == 11) {
                    if(!isset($questions[$key]['columns']) || !is_array($questions[$key]['columns'])){
                        $questions[$key]['columns'] = [];
                    }
                    
                    foreach ($questionData['columns'] as $valueIndex => $value) {
                        if(isset($questions[$key]['columns'][$valueIndex])){
                            if(!isset($questions[$key]['columns'][$valueIndex]['language'])){
                                $questions[$key]['columns'][$valueIndex]['language'] = [];
                            }
                            
                            if (!empty($value['trans'])) {
                                $questions[$key]['columns'][$valueIndex]['language'][$request->language] = [
                                    'name' => $value['trans']
                                ];
                            }
                        }
                    }
                }
                // Handle rows for question type 11
                if (!empty($questionData['rows']) && $questions[$key]['question_type_id'] == 11) {
                    if(!isset($questions[$key]['rows']) || !is_array($questions[$key]['rows'])){
                        $questions[$key]['rows'] = [];
                    }
                    
                    foreach ($questionData['rows'] as $valueIndex => $value) {
                        if(isset($questions[$key]['rows'][$valueIndex])){
                            if(!isset($questions[$key]['rows'][$valueIndex]['language'])){
                                $questions[$key]['rows'][$valueIndex]['language'] = [];
                            }
                            
                            if (!empty($value['trans'])) {
                                $questions[$key]['rows'][$valueIndex]['language'][$request->language] = [
                                    'name' => $value['trans']
                                ];
                            }
                        }
                    }
                }

                // Handle header for question type 15
                if (isset($questionData['header']) && $questions[$key]['question_type_id'] == 15) {
                    if (!isset($questions[$key]['setup']['language'][$request->language]['header'])) {
                        $questions[$key]['setup']['language'][$request->language]['header'] = $questionData['header']['trans'] ?? '';
                    }
                }
                
                // Handle footer for question type 15
                if (isset($questionData['footer']) && $questions[$key]['question_type_id'] == 15) {
                    if (!isset($questions[$key]['setup']['language'][$request->language]['footer'])) {
                        $questions[$key]['setup']['language'][$request->language]['footer'] = $questionData['footer']['trans'] ?? '';
                    }
                }
                
                // Handle labelA for question types 16 and 20
                if (isset($questionData['labelA']) && 
                    ($questions[$key]['question_type_id'] == 16 || $questions[$key]['question_type_id'] == 20 || $questions[$key]['question_type_id'] == 22)) {
                    if (!isset($questions[$key]['setup']['language'][$request->language]['labels'])) {
                        $questions[$key]['setup']['language'][$request->language]['labels'] = [];
                    }
                    $questions[$key]['setup']['language'][$request->language]['labels']['statementA'] = $questionData['labelA']['trans'] ?? '';
                }
                
                // Handle labelB for question types 16 and 20
                if (isset($questionData['labelB']) && 
                    ($questions[$key]['question_type_id'] == 16 || $questions[$key]['question_type_id'] == 20 || $questions[$key]['question_type_id'] == 22)) {
                    if (!isset($questions[$key]['setup']['language'][$request->language]['labels'])) {
                        $questions[$key]['setup']['language'][$request->language]['labels'] = [];
                    }
                    $questions[$key]['setup']['language'][$request->language]['labels']['statementB'] = $questionData['labelB']['trans'] ?? '';
                }
            }
        }
        


        //dd($questions[0]['choices'][1]['name'],$questions[0]['choices'][1]['language'][$request->language]);

        foreach ($questions as $key => $question) {
            // Get the question model instance
            $questionModel = Question::find($question['id']);
            
            if (!$questionModel) {
                continue; // Skip if question not found
            }
            
            // Prepare update data
            $updateData = [
                'updated_by' => Auth::id(),
            ];
            
            // Update setup if it has been modified
            if (isset($questions[$key]['setup'])) {
                $updateData['setup'] = $questions[$key]['setup'];
            }
            
            // Update choices if they exist and have been modified
            if (isset($questions[$key]['choices']) && is_array($questions[$key]['choices'])) {
                $updateData['choices'] = $questions[$key]['choices'];
            }
            
            // Update items if they exist and have been modified
            if (isset($questions[$key]['items']) && is_array($questions[$key]['items'])) {
                $updateData['items'] = $questions[$key]['items'];
            }

            // Update columns if they exist and have been modified
            if (isset($questions[$key]['columns']) && is_array($questions[$key]['columns'])) {
                $updateData['columns'] = $questions[$key]['columns'];
            }
            // Update rows if they exist and have been modified
            if (isset($questions[$key]['rows']) && is_array($questions[$key]['rows'])) {
                $updateData['rows'] = $questions[$key]['rows'];
            }
            
            // Update values if they exist and have been modified
            if (isset($questions[$key]['values']) && is_array($questions[$key]['values'])) {
                $updateData['values'] = $questions[$key]['values'];
            }
            
            // Update the question
            $questionModel->update($updateData);
        }
        
        // Handle system messages if they exist
        if (!empty($systemMessages)) {
            // Map system message order to keys (based on download order)
            $messageKeys = [
                'loading',
                'saving',
                'nextBtn',
                'prevBtn',
                'finalBtn',
                'input1_warning_min',
                'input1_warning_max',
                'input2_warning_minmax',
                'input2_warning_min',
                'input2_warning_max',
                'input9_warning_alphanumeric',
                'input8_warning_letters',
                'input4_warning_invalidemail',
                'required_warning',
                'complete',
                'qualityControl',
                'screenout',
                'connectionLostTitle',
                'connectionLostContent',
            ];
            
            $setup = $questionnaire->setup ?? [];
            
            
            if (!isset($setup['language'])) {
                $setup['language'] = [];
            }
            
            if (!isset($setup['language'][$request->language])) {
                $setup['language'][$request->language] = [];
            }
            
            // Process system messages (map by position to message keys)
            foreach ($systemMessages as $index => $msg) {
                // Map by position to message keys
                if (isset($messageKeys[$index]) && !empty($msg['trans'])) {
                    $setup['language'][$request->language][$messageKeys[$index]] = $msg['trans'];
                }
            }
            
            // Update questionnaire setup with translated messages
            $setup['language'][$request->language]['rtl'] = $request->languageInfo['RTL'] ?? 0;
            $questionnaire->update(['setup' => $setup]);
        }

        // return response()->json([
        //     'message' => 'Translation uploaded successfully',
        //     'questions_updated' => count($questions),
        //     'language' => $request->language,
        // ], 200);
        return redirect()->back()->with('success', 'Translation uploaded successfully');
    }

    public function aiTranslateQuestion(Request $request, $code)
    {
        $questionnaire = Questionnaire::whereCode($code)->with('questions')->first();
        if (!$questionnaire) {
            return response()->json(['error' => 'Questionnaire not found'], 404);
        }

        $language = $request->language;
        $languageName = $request->languageName ?? $language;
        $questionKeys = $request->questionKeys ?? [];
        $includeSystemMessages = $request->includeSystemMessages ?? false;
        $rtl = $request->RTL ?? 0;

        if (empty($language)) {
            return response()->json(['error' => 'Language code is required'], 400);
        }

        if (empty($questionKeys) || !is_array($questionKeys)) {
            return response()->json(['error' => 'Question keys array is required'], 400);
        }

        \Log::info('AI Translate Question Request', [
            'questionnaire_code' => $code,
            'question_keys' => $questionKeys,
            'question_count' => count($questionKeys),
            'language' => $language,
            'language_name' => $languageName,
            'include_system_messages' => $includeSystemMessages,
            'user_id' => \Auth::id(),
            'timestamp' => now()->toDateTimeString()
        ]);

        try {
            // Ensure the language is added to questionnaire setup immediately
            // This ensures it appears in the language list/dropdown right away
            $setup = $questionnaire->setup ?? [];
            if (!isset($setup['language'])) {
                $setup['language'] = [];
            }
            if (!isset($setup['language'][$language])) {
                $setup['language'][$language] = [];
                // Set RTL if provided
                if ($rtl) {
                    $setup['language'][$language]['rtl'] = $rtl;
                }
                $questionnaire->update(['setup' => $setup]);
                \Log::info('Language added to questionnaire setup', [
                    'questionnaire_code' => $code,
                    'language' => $language,
                    'rtl' => $rtl
                ]);
            }
            
            $translationService = new AITranslationService();
            $questions = $translationService->extractQuestionsByKeys($questionnaire, $questionKeys);
            
            if (empty($questions)) {
                \Log::warning('No questions found for batch translation', [
                    'questionnaire_code' => $code,
                    'requested_keys' => $questionKeys
                ]);
                return response()->json([
                    'success' => true,
                    'translatedKeys' => []
                ]);
            }

            $systemMessagesInfo = $includeSystemMessages ? [
                'questionnaireCode' => $code,
                'rtl' => $rtl
            ] : null;

            // Use async mode - jobs will save translations directly to DB
            // Frontend will receive updates via websockets when each question is translated
            $translationService->translateQuestionsInParallel($questions, $language, $languageName, $systemMessagesInfo, true);

            \Log::info('Translation jobs dispatched - returning immediately', [
                'questionnaire_code' => $code,
                'question_count' => count($questions),
                'question_keys' => $questionKeys,
                'language' => $language
            ]);

            // Return immediately - translations will be saved by jobs and broadcast via websockets
            return response()->json([
                'success' => true,
                'message' => 'Translation jobs dispatched. Updates will be broadcast via websockets.',
                'translatedKeys' => [] // Empty initially - frontend will receive updates via websockets
            ]);
        } catch (\Exception $e) {
            \Log::error('Batch question translation endpoint failed', [
                'questionnaire_code' => $code,
                'question_keys' => $questionKeys,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Batch translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function aiTranslateSystemMessages(Request $request, $code)
    {
        $questionnaire = Questionnaire::whereCode($code)->first();
        if (!$questionnaire) {
            return response()->json(['error' => 'Questionnaire not found'], 404);
        }

        $language = $request->language;
        $languageName = $request->languageName ?? $language;
  
        if (empty($language)) {
            return response()->json(['error' => 'Language code is required'], 400);
        }

        \Log::info('AI Translate System Messages Request', [
            'questionnaire_code' => $code,
            'language' => $language,
            'language_name' => $languageName,
            'user_id' => \Auth::id(),
            'RTL' => $request->RTL,
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            \App\Jobs\TranslateSystemMessagesJob::dispatch(
                $code,
                $language,
                $languageName,
                $request->RTL ?? 0
            );

            \Log::info('System messages translation job dispatched', [
                'questionnaire_code' => $code,
                'language' => $language
            ]);

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error('System messages translation endpoint failed', [
                'questionnaire_code' => $code,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retranslate a single question synchronously and persist the result
     *
     * This endpoint waits for the AI translation to complete and saves it
     * before returning, so the frontend can safely reload and reflect the
     * updated question immediately.
     */
    public function aiTranslateSingleQuestion(Request $request, $code)
    {
        $questionnaire = Questionnaire::whereCode($code)->with('questions')->first();
        if (!$questionnaire) {
            return response()->json(['error' => 'Questionnaire not found'], 404);
        }

        $language = $request->language;
        $languageName = $request->languageName ?? $language;
        $questionKey = $request->questionKey;
        $rtl = $request->RTL ?? 0;

        if (empty($language)) {
            return response()->json(['error' => 'Language code is required'], 400);
        }

        if (empty($questionKey)) {
            return response()->json(['error' => 'Question key is required'], 400);
        }

        // Ensure language exists in setup so RTL and metadata are stored
        $setup = $questionnaire->setup ?? [];
        if (!isset($setup['language'])) {
            $setup['language'] = [];
        }
        if (!isset($setup['language'][$language])) {
            $setup['language'][$language] = [];
        }
        if ($rtl) {
            $setup['language'][$language]['rtl'] = $rtl;
        }
        $questionnaire->update(['setup' => $setup]);

        try {
            $translationService = new AITranslationService();

            // Perform synchronous translation of this single question
            $translatedData = $translationService->translateSingleQuestion(
                $questionnaire,
                $questionKey,
                $language,
                $languageName
            );

            if (empty($translatedData)) {
                return response()->json([
                    'error' => 'Question not found for translation or no translation produced'
                ], 404);
            }

            // Persist the translated content directly to the question
            $this->saveQuestionTranslation(
                $questionnaire,
                $language,
                $questionKey,
                $translatedData
            );

            return response()->json([
                'success' => true,
                'message' => 'Question translated successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Single question translation failed', [
                'questionnaire_code' => $code,
                'question_key' => $questionKey,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function saveQuestionTranslation(Questionnaire $questionnaire, string $language, string $questionKey, array $questionData)
    {
        $variable_start = collect($questionnaire->setup)->get('variable_start', 1);
        $questions = collect($questionnaire->questions)->sortBy('order')->toArray();
        
        $questionNumber = intval(str_replace('Q', '', $questionKey)) - ($variable_start - 1);
        
        foreach ($questions as $key => $question) {
            if (intval($question['order']) === $questionNumber) {
                if (!isset($questions[$key]['setup'])) {
                    $questions[$key]['setup'] = [];
                }
                
                if (!isset($questions[$key]['setup']['language'])) {
                    $questions[$key]['setup']['language'] = [];
                }
                
                if (!isset($questions[$key]['setup']['language'][$language])) {
                    $questions[$key]['setup']['language'][$language] = [];
                }
                
                if (!empty($questionData['trans'])) {
                    $questions[$key]['setup']['language'][$language]['question'] = $questionData['trans'];
                }
                
                if (($questions[$key]['question_type_id'] == 2 || 
                     $questions[$key]['question_type_id'] == 3 || 
                     $questions[$key]['question_type_id'] == 20) && 
                    !empty($questionData['options'])) {
                    
                    if (!isset($questions[$key]['choices']) || !is_array($questions[$key]['choices'])) {
                        $questions[$key]['choices'] = [];
                    }
                    
                    foreach ($questionData['options'] as $optionIndex => $option) {
                        if (!isset($questions[$key]['choices'][$optionIndex])) {
                            $questions[$key]['choices'][$optionIndex] = [];
                        }
                        
                        if (!isset($questions[$key]['choices'][$optionIndex]['language'])) {
                            $questions[$key]['choices'][$optionIndex]['language'] = [];
                        }
                        
                        if (!empty($option['trans'])) {
                            $questions[$key]['choices'][$optionIndex]['language'][$language] = [
                                'name' => $option['trans']
                            ];
                        }
                    }
                }
                
                if (!empty($questionData['items'])) {
                    $itemQuestionTypes = [5, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
                    
                    if (in_array($questions[$key]['question_type_id'], $itemQuestionTypes)) {
                        if (!isset($questions[$key]['items']) || !is_array($questions[$key]['items'])) {
                            $questions[$key]['items'] = [];
                        }
                        
                        foreach ($questionData['items'] as $itemIndex => $item) {
                            if (!isset($questions[$key]['items'][$itemIndex])) {
                                $questions[$key]['items'][$itemIndex] = [];
                            }
                            
                            if (!isset($questions[$key]['items'][$itemIndex]['language'])) {
                                $questions[$key]['items'][$itemIndex]['language'] = [];
                            }
                            
                            if (!empty($item['trans'])) {
                                if ($questions[$key]['question_type_id'] == 16 && isset($item['statementB'])) {
                                    $questions[$key]['items'][$itemIndex]['language'][$language] = [
                                        'statementA' => $item['trans'],
                                        'statementB' => $item['statementB']
                                    ];
                                } else {
                                    $questions[$key]['items'][$itemIndex]['language'][$language] = [
                                        'name' => $item['trans']
                                    ];
                                }
                            }
                        }
                    }
                }
                
                if (!empty($questionData['values'])) {
                    $valueQuestionTypes = [8, 15, 16];
                    
                    if (in_array($questions[$key]['question_type_id'], $valueQuestionTypes)) {
                        if (!isset($questions[$key]['values']) || !is_array($questions[$key]['values'])) {
                            $questions[$key]['values'] = [];
                        }
                        
                        foreach ($questionData['values'] as $valueIndex => $value) {
                            if (!isset($questions[$key]['values'][$valueIndex])) {
                                $questions[$key]['values'][$valueIndex] = [];
                            }
                            
                            if (!isset($questions[$key]['values'][$valueIndex]['language'])) {
                                $questions[$key]['values'][$valueIndex]['language'] = [];
                            }
                            
                            if (!empty($value['trans'])) {
                                $questions[$key]['values'][$valueIndex]['language'][$language] = [
                                    'name' => $value['trans']
                                ];
                            }
                        }
                    }
                }

                // Handle choices for slider questions (type 8) - these are the scale labels/choices
                if (!empty($questionData['choices']) && $questions[$key]['question_type_id'] == 8) {
                    \Log::info('Processing choices for slider question in saveQuestionTranslation', [
                        'question_id' => $question['id'],
                        'question_key' => $questionKey,
                        'choices_received_count' => count($questionData['choices']),
                        'choices_received' => $questionData['choices']
                    ]);
                    
                    if (!isset($questions[$key]['choices']) || !is_array($questions[$key]['choices'])) {
                        $questions[$key]['choices'] = [];
                    }
                    
                    foreach ($questionData['choices'] as $choiceIndex => $choice) {
                        if (!isset($questions[$key]['choices'][$choiceIndex])) {
                            $questions[$key]['choices'][$choiceIndex] = [];
                        }
                        
                        if (!isset($questions[$key]['choices'][$choiceIndex]['language'])) {
                            $questions[$key]['choices'][$choiceIndex]['language'] = [];
                        }
                        
                        if (!empty($choice['trans'])) {
                            $questions[$key]['choices'][$choiceIndex]['language'][$language] = [
                                'name' => $choice['trans']
                            ];      
                        }
                    }
                }

                // Handle columns for matrix questions (type 11)
                if (!empty($questionData['columns']) && $questions[$key]['question_type_id'] == 11) {
                    if (!isset($questions[$key]['columns']) || !is_array($questions[$key]['columns'])) {
                        $questions[$key]['columns'] = [];
                    }
                    
                    foreach ($questionData['columns'] as $columnIndex => $column) {
                        if (isset($questions[$key]['columns'][$columnIndex])) {
                            if (!isset($questions[$key]['columns'][$columnIndex]['language'])) {
                                $questions[$key]['columns'][$columnIndex]['language'] = [];
                            }
                            
                            if (!empty($column['trans'])) {
                                $questions[$key]['columns'][$columnIndex]['language'][$language] = [
                                    'name' => $column['trans']
                                ];
                            }
                        }
                    }
                }

                // Handle rows for matrix questions (type 11)
                if (!empty($questionData['rows']) && $questions[$key]['question_type_id'] == 11) {
                    if (!isset($questions[$key]['rows']) || !is_array($questions[$key]['rows'])) {
                        $questions[$key]['rows'] = [];
                    }
                    
                    foreach ($questionData['rows'] as $rowIndex => $row) {
                        if (isset($questions[$key]['rows'][$rowIndex])) {
                            if (!isset($questions[$key]['rows'][$rowIndex]['language'])) {
                                $questions[$key]['rows'][$rowIndex]['language'] = [];
                            }
                            
                            if (!empty($row['trans'])) {
                                $questions[$key]['rows'][$rowIndex]['language'][$language] = [
                                    'name' => $row['trans']
                                ];
                            }
                        }
                    }
                }
                
                if (isset($questionData['header']) && $questions[$key]['question_type_id'] == 15) {
                    $questions[$key]['setup']['language'][$language]['header'] = $questionData['header']['trans'] ?? '';
                }
                
                if (isset($questionData['footer']) && $questions[$key]['question_type_id'] == 15) {
                    $questions[$key]['setup']['language'][$language]['footer'] = $questionData['footer']['trans'] ?? '';
                }
                
                if (isset($questionData['labelA']) && 
                    ($questions[$key]['question_type_id'] == 16 || $questions[$key]['question_type_id'] == 20)) {
                    if (!isset($questions[$key]['setup']['language'][$language]['labels'])) {
                        $questions[$key]['setup']['language'][$language]['labels'] = [];
                    }
                    $questions[$key]['setup']['language'][$language]['labels']['statementA'] = $questionData['labelA']['trans'] ?? '';
                }
                
                if (isset($questionData['labelB']) && 
                    ($questions[$key]['question_type_id'] == 16 || $questions[$key]['question_type_id'] == 20)) {
                    if (!isset($questions[$key]['setup']['language'][$language]['labels'])) {
                        $questions[$key]['setup']['language'][$language]['labels'] = [];
                    }
                    $questions[$key]['setup']['language'][$language]['labels']['statementB'] = $questionData['labelB']['trans'] ?? '';
                }
                
                $questionModel = Question::find($question['id']);
                if ($questionModel) {
                    $updateData = [
                        'updated_by' => Auth::id(),
                        'setup' => $questions[$key]['setup'],
                    ];
                    
                    if (isset($questions[$key]['choices']) && is_array($questions[$key]['choices'])) {
                        $updateData['choices'] = $questions[$key]['choices'];
                    }
                    
                    if (isset($questions[$key]['items']) && is_array($questions[$key]['items'])) {
                        $updateData['items'] = $questions[$key]['items'];
                    }
                    
                    if (isset($questions[$key]['values']) && is_array($questions[$key]['values'])) {
                        $updateData['values'] = $questions[$key]['values'];
                    }

                    if (isset($questions[$key]['columns']) && is_array($questions[$key]['columns'])) {
                        $updateData['columns'] = $questions[$key]['columns'];
                    }

                    if (isset($questions[$key]['rows']) && is_array($questions[$key]['rows'])) {
                        $updateData['rows'] = $questions[$key]['rows'];
                    }
                    
                    $questionModel->update($updateData);
                }
                
                break;
            }
        }
    }

    private function saveSystemMessagesTranslation(Questionnaire $questionnaire, string $language, array $systemMessages, $RTL)
    {
        $messageKeys = [
            'loading',
            'saving',
            'nextBtn',
            'prevBtn',
            'finalBtn',
            'input1_warning_min',
            'input1_warning_max',
            'input2_warning_minmax',
            'input2_warning_min',
            'input2_warning_max',
            'input9_warning_alphanumeric',
            'input8_warning_letters',
            'input4_warning_invalidemail',
            'required_warning',
            'complete',
            'qualityControl',
            'screenout',
            'connectionLostTitle',
            'connectionLostContent',
        ];
        
        $setup = $questionnaire->setup ?? [];
        
        if (!isset($setup['language'])) {
            $setup['language'] = [];
        }
        
        if (!isset($setup['language'][$language])) {
            $setup['language'][$language] = [];
        }
        
        foreach ($systemMessages as $index => $msg) {
            if (isset($messageKeys[$index]) && !empty($msg['trans'])) {
                $setup['language'][$language][$messageKeys[$index]] = $msg['trans'];
               
            }
        }
        $setup['language'][$language]['rtl'] = $RTL;
        $questionnaire->update(['setup' => $setup]);
    }

    public function aiTranslate(Request $request, $code)
    {
        $questionnaire = Questionnaire::whereCode($code)->with('questions')->first();
        if (!$questionnaire) {
            return redirect()->back()->with('error', 'Questionnaire not found');
        }

        $language = $request->language;
        $languageName = $request->languageName ?? $language;

        if (empty($language)) {
            return redirect()->back()->with('error', 'Language code is required');
        }

        try {
            $translationService = new AITranslationService();
            $translationData = $translationService->translateQuestionnaire($questionnaire, $language, $languageName);

            $groupedData = $translationData['groupedData'];
            $systemMessages = $translationData['systemMessages'];

            $this->saveTranslations($questionnaire, $language, $groupedData, $systemMessages);
            return redirect()->back()->with('success', 'Translation completed successfully');
        } catch (\Exception $e) {
            $errorMessage = 'Translation failed: ' . $e->getMessage();
            
            if (strpos($e->getMessage(), 'timed out') !== false || strpos($e->getMessage(), 'timeout') !== false) {
                $errorMessage = 'Translation request timed out. The questionnaire may be too large for AI translation. Please try uploading a CSV file instead or contact support.';
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    private function saveTranslations(Questionnaire $questionnaire, string $language, array $groupedData, array $systemMessages)
    {
        $variable_start = collect($questionnaire->setup)->get('variable_start', 1);
        $questions = collect($questionnaire->questions)->sortBy('order')->toArray();
        
        foreach ($questions as $key => $question) {
            if (!isset($questions[$key]['setup'])) {
                $questions[$key]['setup'] = [];
            }           
            if (!isset($questions[$key]['setup']['language'])) {
                $questions[$key]['setup']['language'] = [];
            }
            
            $questionNumber = intval($question['order']) + (intval($variable_start) - 1);
            $QN = 'Q' . $questionNumber;
            
            if (isset($groupedData[$QN])) {
                $questionData = $groupedData[$QN];
                
                if(!isset($questions[$key]['setup']['language'][$language])){
                    $questions[$key]['setup']['language'][$language] = [];
                }               
                if (!empty($questionData['trans'])) {
                    $questions[$key]['setup']['language'][$language]['question'] = $questionData['trans'];
                }
                
                if (($questions[$key]['question_type_id'] == 2 || 
                     $questions[$key]['question_type_id'] == 3 || 
                     $questions[$key]['question_type_id'] == 20) && 
                    !empty($questionData['options'])) {
                    
                    if(!isset($questions[$key]['choices']) || !is_array($questions[$key]['choices'])){
                        $questions[$key]['choices'] = [];
                    }
                    
                    foreach ($questionData['options'] as $optionIndex => $option) {
                        if(!isset($questions[$key]['choices'][$optionIndex])){
                            $questions[$key]['choices'][$optionIndex] = [];
                        }                     
                        if(!isset($questions[$key]['choices'][$optionIndex]['language'])){
                            $questions[$key]['choices'][$optionIndex]['language'] = [];
                        }               
                        if (!empty($option['trans'])) {
                            $questions[$key]['choices'][$optionIndex]['language'][$language] = [
                                'name' => $option['trans']
                            ];
                        }
                    }
                }
                
                if (!empty($questionData['items'])) {
                    $itemQuestionTypes = [5, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
                    
                    if (in_array($questions[$key]['question_type_id'], $itemQuestionTypes)) {
                        if(!isset($questions[$key]['items']) || !is_array($questions[$key]['items'])){
                            $questions[$key]['items'] = [];
                        }
                        
                        foreach ($questionData['items'] as $itemIndex => $item) {
                            if(!isset($questions[$key]['items'][$itemIndex])){
                                $questions[$key]['items'][$itemIndex] = [];
                            }                  
                            if(!isset($questions[$key]['items'][$itemIndex]['language'])){
                                $questions[$key]['items'][$itemIndex]['language'] = [];
                            }   
                            if (!empty($item['trans'])) {
                                if ($questions[$key]['question_type_id'] == 16 && isset($item['statementB'])) {
                                    $questions[$key]['items'][$itemIndex]['language'][$language] = [
                                        'statementA' => $item['trans'],
                                        'statementB' => $item['statementB']
                                    ];
                                } else {
                                    $questions[$key]['items'][$itemIndex]['language'][$language] = [
                                        'name' => $item['trans']
                                    ];
                                }
                            }
                        }
                    }
                }
                
                if (!empty($questionData['values'])) {
                    $valueQuestionTypes = [8, 15, 16];
                    
                    if (in_array($questions[$key]['question_type_id'], $valueQuestionTypes)) {
                        if(!isset($questions[$key]['values']) || !is_array($questions[$key]['values'])){
                            $questions[$key]['values'] = [];
                        }                       
                        foreach ($questionData['values'] as $valueIndex => $value) {
                            if(!isset($questions[$key]['values'][$valueIndex])){
                                $questions[$key]['values'][$valueIndex] = [];
                            }                           
                            if(!isset($questions[$key]['values'][$valueIndex]['language'])){
                                $questions[$key]['values'][$valueIndex]['language'] = [];
                            }                           
                            if (!empty($value['trans'])) {
                                $questions[$key]['values'][$valueIndex]['language'][$language] = [
                                    'name' => $value['trans']
                                ];
                            }
                        }
                    }
                }
                
                if (isset($questionData['header']) && $questions[$key]['question_type_id'] == 15) {
                    if (!isset($questions[$key]['setup']['language'][$language]['header'])) {
                        $questions[$key]['setup']['language'][$language]['header'] = $questionData['header']['trans'] ?? '';
                    }
                }                
                if (isset($questionData['footer']) && $questions[$key]['question_type_id'] == 15) {
                    if (!isset($questions[$key]['setup']['language'][$language]['footer'])) {
                        $questions[$key]['setup']['language'][$language]['footer'] = $questionData['footer']['trans'] ?? '';
                    }
                }               
                if (isset($questionData['labelA']) && 
                    ($questions[$key]['question_type_id'] == 16 || $questions[$key]['question_type_id'] == 20)) {
                    if (!isset($questions[$key]['setup']['language'][$language]['labels'])) {
                        $questions[$key]['setup']['language'][$language]['labels'] = [];
                    }
                    $questions[$key]['setup']['language'][$language]['labels']['statementA'] = $questionData['labelA']['trans'] ?? '';
                }                
                if (isset($questionData['labelB']) && 
                    ($questions[$key]['question_type_id'] == 16 || $questions[$key]['question_type_id'] == 20)) {
                    if (!isset($questions[$key]['setup']['language'][$language]['labels'])) {
                        $questions[$key]['setup']['language'][$language]['labels'] = [];
                    }
                    $questions[$key]['setup']['language'][$language]['labels']['statementB'] = $questionData['labelB']['trans'] ?? '';
                }
            }
        }

        foreach ($questions as $key => $question) {
            $questionModel = Question::find($question['id']);
            
            if (!$questionModel) {
                continue;
            }
            
            $updateData = [
                'updated_by' => Auth::id(),
            ];
            
            if (isset($questions[$key]['setup'])) {
                $updateData['setup'] = $questions[$key]['setup'];
            }
            
            if (isset($questions[$key]['choices']) && is_array($questions[$key]['choices'])) {
                $updateData['choices'] = $questions[$key]['choices'];
            }
            
            if (isset($questions[$key]['items']) && is_array($questions[$key]['items'])) {
                $updateData['items'] = $questions[$key]['items'];
            }
            
            if (isset($questions[$key]['values']) && is_array($questions[$key]['values'])) {
                $updateData['values'] = $questions[$key]['values'];
            }
            
            $questionModel->update($updateData);
        }
        
        if (!empty($systemMessages)) {
            $messageKeys = [
                'loading',
                'saving',
                'nextBtn',
                'prevBtn',
                'finalBtn',
                'input1_warning_min',
                'input1_warning_max',
                'input2_warning_minmax',
                'input2_warning_min',
                'input2_warning_max',
                'input9_warning_alphanumeric',
                'input8_warning_letters',
                'input4_warning_invalidemail',
                'required_warning',
                'complete',
                'qualityControl',
                'screenout',
                'connectionLostTitle',
                'connectionLostContent',
            ];
            
            $setup = $questionnaire->setup ?? [];
            
            if (!isset($setup['language'])) {
                $setup['language'] = [];
            }
            
            if (!isset($setup['language'][$language])) {
                $setup['language'][$language] = [];
            }
            
            foreach ($systemMessages as $index => $msg) {
                if (isset($messageKeys[$index]) && !empty($msg['trans'])) {
                    $setup['language'][$language][$messageKeys[$index]] = $msg['trans'];
                }
            }
            
            $questionnaire->update(['setup' => $setup]);
        }
    }

    public function deleteLanguage(Request $request, $code)
    {
        $questionnaire = Questionnaire::whereCode($code)->with('questions')->firstOrFail();

        $language = $request->language;
        $setup = $questionnaire->setup ?? [];

        if (isset($setup['language']) && array_key_exists($language, $setup['language'])) {
            unset($setup['language'][$language]);
            $questionnaire->setup = $setup;
            $questionnaire->save();
        }
        return redirect()->back()->with('success', 'Language deleted successfully');
       // return response()->json(['message' => 'Language deleted successfully'], 200);
    }

    public function cleanupSystemMessages(Request $request, $code)
    {
        $questionnaire = Questionnaire::whereCode($code)->firstOrFail();
        $language = $request->language;

        if (empty($language)) {
            return response()->json(['error' => 'Language code is required'], 400);
        }

        $setup = $questionnaire->setup ?? [];
        
        if (isset($setup['language'][$language])) {
            $messageKeys = [
                'loading',
                'saving',
                'nextBtn',
                'prevBtn',
                'finalBtn',
                'input1_warning_min',
                'input1_warning_max',
                'input2_warning_minmax',
                'input2_warning_min',
                'input2_warning_max',
                'input9_warning_alphanumeric',
                'input8_warning_letters',
                'input4_warning_invalidemail',
                'required_warning',
                'complete',
                'qualityControl',
                'screenout',
                'connectionLostTitle',
                'connectionLostContent',
            ];

            // Remove system messages but keep the language entry
            foreach ($messageKeys as $key) {
                if (isset($setup['language'][$language][$key])) {
                    unset($setup['language'][$language][$key]);
                }
            }

            // If language entry is empty (only had system messages), remove it entirely
            $languageData = $setup['language'][$language] ?? [];
            $hasOtherData = false;
            foreach ($languageData as $key => $value) {
                if ($key !== 'rtl' && !empty($value)) {
                    $hasOtherData = true;
                    break;
                }
            }

            if (!$hasOtherData) {
                unset($setup['language'][$language]);
            }

            $questionnaire->setup = $setup;
            $questionnaire->save();

            \Log::info('System messages cleaned up for cancelled translation', [
                'questionnaire_code' => $code,
                'language' => $language
            ]);
        }

        return response()->json(['success' => true, 'message' => 'System messages cleaned up']);
    }

}
