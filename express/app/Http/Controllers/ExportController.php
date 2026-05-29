<?php

namespace App\Http\Controllers;

use App\Models\Questionnaire;
use App\Models\Filter;
use App\Models\Export;
use App\Models\Project;
use App\Models\Activity_Log;
use App\Models\Record;
use App\Models\Question;

use App\Exports\CsvExport;

use Inertia\Inertia;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ExportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('DataAnalysis/Summary');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $questionnaire_code)
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first(); 

        $filtered_ids = [];
        if ($request->setup['export_filter'] == 0) {
            if ($request->setup['includeID'] != null) {
                $filtered_ids = Str::of($request->setup['includeID'])->explode(',');
            }
        }elseif ($request->setup['export_filter'] == 1) {
            foreach (generator($request->setup['filters']) as $key => $filter_id) {
                $filter = Filter::find($filter_id);
                $filtered_ids = array_merge($filtered_ids, Str::of($filter->filtered_ids)->explode(',')->unique()->toArray());
            }
        }

        if (count($request->setup['merge']) > 0) {
            $questionnaire_ids = Questionnaire::whereIn('code', collect($request->setup['merge'])->push($questionnaire_code))->get('id')->pluck('id');
            $questionnaires = Questionnaire::whereIn('id', $questionnaire_ids)->get();
            
            $merge_records = collect([]);
            foreach (generator($questionnaires) as $qs) {
                if ($request->setup['export_filter'] == 0 || $request->setup['export_filter'] == 1) {
                    $tmp_records = Record::where('questionnaire_id', $qs->id)->where('status','cp')
                    ->whereIn('participant_id', $filtered_ids)
                    // ->groupBy('participant_id')
                    ->get();
                }else {
                    $tmp_records = Record::where('questionnaire_id', $qs->id)->where('status','cp')
                    // ->groupBy('participant_id')
                    ->get();
                }
                
                $merge_records = $merge_records->merge($tmp_records);
            }
            
        }else {
            $questionnaires = Questionnaire::whereIn('id', [$questionnaire->id])->get();
            $merge_records = Record::where(['questionnaire_id' => $questionnaire->id, 'status' => 'cp'])
            // ->groupBy('participant_id')
            ->get();
        }
        
        $records = [];
        $i = 0;
        foreach ($merge_records->groupBy('participant_id') as $record) {
            $internal = [];
            $responses = [];
            $records[$i] = $record[0]->toArray();//for applying changes toArray
            foreach ($record as $item) {
                $internal = array_merge($internal, $item->url_data['internal']);
                $responses = array_merge($responses, $item->responses);
            }
            $records[$i]['url_data']['internal'] = $internal;
            $records[$i]['responses'] = $responses;
            $i++;
        }

        $user = Auth::user();

        $filtered_ids = [];
        $results = [];
        $answer = $request->answer_key == 1 ? ["Answer"] : [];
        $answer_key = $request->answer_key == 1 ? ["Key","Question","Matrix","Value","Description"] : [];
        if ($request->setup['export_filter'] == 0) {
           
            if ($request->setup['includeID'] != null) {
                $filtered_ids = Str::of($request->setup['includeID'])->explode(',');
            }
            $filtered = collect($records)->whereIn('participant_id', $filtered_ids);
            $tmp_data = $this->exportData($filtered,$questionnaires,$request->setup['display'],$request->answer_key);
            $headers = collect(['Respondent ID', 'Device', 'Browser', 'IP', 'Start', 'End', 'Duration'])->merge($tmp_data['headers'])->toArray();
            // $results = collect($tmp_data['results'])->prepend($headers)->toArray();
            $results = collect([$tmp_data['results'],$answer,$answer_key,$tmp_data['list_quest']])->prepend($headers)->toArray(); 

            $questionnaire->exports()->create([
                'user_id' => $user->id,
                'name' => $request->setup['name'] == "" ? Str::random(10) : $request->setup['name'],
                'path' => '/some/path',
                'setup' => $request->setup,
                'data' => $results,
            ]);
        }elseif ($request->setup['export_filter'] == 1) {
            foreach (generator($request->setup['filters']) as $key => $filter_id) {
          
                $filter = Filter::find($filter_id);
                $filtered_ids = Str::of($filter->filtered_ids)->explode(',')->unique();

                $filtered = collect($records)->whereIn('participant_id', $filtered_ids);
                $tmp_data = $this->exportData($filtered,$questionnaires,$request->setup['display'],$request->answer_key);
                $headers = collect(['Respondent ID', 'Device', 'Browser', 'IP', 'Start', 'End', 'Duration'])->merge($tmp_data['headers'])->toArray();
                // $results = collect($tmp_data['results'])->prepend($headers)->toArray();
                $results = collect([$tmp_data['results'],$answer,$answer_key,$tmp_data['list_quest']])->prepend($headers)->toArray(); 

                $questionnaire->exports()->create([
                    'user_id' => $user->id,
                    'name' => $filter->name,
                    'path' => '/some/path',
                    'setup' => $request->setup,
                    'data' => $results,
                ]);
            }
        }else { //All data
            $filtered = collect($records);
            $tmp_data = $this->exportData($filtered,$questionnaires,$request->setup['display'],$request->answer_key);   
            $headers = collect(['Respondent ID', 'Device', 'Browser', 'IP', 'Start', 'End', 'Duration'])->merge($tmp_data['headers'])->toArray();
            $results = collect([$tmp_data['results'],$answer,$answer_key,$tmp_data['list_quest']])->prepend($headers)->toArray(); 
            $questionnaire->exports()->create([
                'user_id' => $user->id,
                'name' => 'All Data',
                'path' => '/some/path',
                'setup' => $request->setup,
                'data' => $results,
            ]);
        }
        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "add",
            'details' => "User ".$user->name." added new export in ". $questionnaire->name. " qustionnaire"
        ]);

        return Redirect::back();
    }

    public function get_Question($quest){
        $tmp_question = [];
        $var = $quest->setup;
        $count=0;
        $storage = []; 
        $array3 = [];
        $questions = Question::where(['questionnaire_id' => $quest->id])->get();
        $questions = $questions->sortBy(function ($question, $key) {
            return $question->order;
        });
        foreach ($questions as $question) { 
            $tmp_question = [$var['variable'] .(string)($count+1),strip_tags($question->question)];  
            array_push($storage,$this->question_type($question,$tmp_question,$var,$count,$question->question_type_id)); 
            $count++;
        }   
        foreach ($storage as $store) { 
            foreach ($store as $array1) { 
                array_push($array3,$array1);
            }
          
        }
        // dd($questions);
        return $array3;
    }
    
    public function question_type($question,$tmp_question,$var,$count,$q_type_id){ 
        $storage = [];
        $countC = 0;  
        $tmp_value = ["","","","Text"]; 
        if($q_type_id == 1){ //Descriptive
            array_push($storage,$tmp_question,["","","","Descriptive"]);
        }else if($q_type_id == 2 || $q_type_id == 3){//Multiple choice & Image selection
            array_push($storage,$tmp_question);
            if(!empty($question->choices)){ 
                foreach ($question->choices as $choice) { 
                    // $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "","",$countC+1, is_null($choice['name']) ? $choice['src'] : $choice['name']]; 
                    $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "","",$countC+1, $choice['name'] ?? '']; 
                    array_push($storage, $tmp_value); 
                    $countC++;
                } 
            }
        }else if($q_type_id == 4){//Single textbox
            array_push($storage,$tmp_question, ["","","",$this->textBox($question->setup['validate']['type'])]);
        }else if($q_type_id == 5){//Multiple textbox
            array_push($storage,$tmp_question);
            if(!empty($question->items)){ 
                foreach ($question->items as $item) { 
                    $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "","",$this->textBox($item['validate']['type']), $item['name']]; 
                    array_push($storage, $tmp_value); 
                    $countC++;
                } 
            }
        }else if($q_type_id == 6){//Open ended
            array_push($storage,$tmp_question, $tmp_value);
        }else if($q_type_id == 7){//Constant sum
            array_push($storage,$tmp_question);
            if(!empty($question->items)){ 
                foreach ($question->items as $item) { 
                    $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "","","Number", $item['name']]; 
                    array_push($storage, $tmp_value); 
                    $countC++;
                } 
            }
        }else if($q_type_id == 8){//Slider
            array_push($storage,$tmp_question);
            if(!empty($question->items)){ 
                foreach ($question->items as $item) { 
                    $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "","", $item['name']]; 
                    array_push($storage,$tmp_value);
                    if(!empty($question->values)){ 
                        foreach ($question->values as $value) { 
                            array_push($storage,["","","","",$value['name']]);
                            
                        }
                     
                    }
                $countC++;
                } 
            }
        }else if($q_type_id == 9 || $q_type_id == 10){//Ranking & Star rating
            array_push($storage,$tmp_question);
            if(!empty($question->items)){ 
                foreach ($question->items as $item) { 
                    if($q_type_id == 10){
                        $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "","","Start rating", $item['name']]; 
                    }else{
                        $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "","","Ranking", $item['name']]; 
                    }
                    
                    array_push($storage, $tmp_value); 
                    $countC++;
                } 
            }
        }else if($q_type_id == 11){//Matrix
            $countR = 0;  
            array_push($storage,$tmp_question);
            $displayByColumn = isset($question->setup['displayByColumn']) ? $question->setup['displayByColumn'] : 0;
            if ( $displayByColumn == 1) {
                if(!empty($question->columns)){ 
                    foreach ($question->columns as $column) { 
                        $tmp_value_col = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "", $column['name']]; 
                        array_push($storage,$tmp_value_col);
                        if(!empty($question->rows)){ 
                            foreach ($question->rows as $row) { 
                                $tmp_value_row = ["","","",$countR+1, $row['placeholder'] ?? $row['name']];
                                array_push($storage, $tmp_value_row);
                                $countR++;
                            }
                            $countR = 0; 
                        }
                    $countC++;
                    } 
                }
            }else {
                if(!empty($question->rows)){ 
                    foreach ($question->rows as $row) { 
                        $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "", $row['placeholder'] ?? $row['name']]; 
                        array_push($storage,$tmp_value);
                        if(!empty($question->columns)){ 
                            foreach ($question->columns as $column) { 
                                array_push($storage,["","","",$countR+1,$column['name']]);
                                $countR++;
                            }
                            $countR = 0; 
                        }
                    $countC++;
                    } 
                }
            }
            
        }else if($q_type_id == 12){//Matrix multi
            $countR = 0;  
            array_push($storage,$tmp_question);
            if(!empty($question->rows)){ 
                foreach ($question->rows as $row) { 
                    $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "", $row['name']]; 
                    array_push($storage,$tmp_value);
                    if(!empty($question->items)){ 
                        foreach ($question->items as $item) { 
                            array_push($storage,["","","",$countR+1,$item['title']]);
                            $countR++;
                        }
                        $countR = 0; 
                    }
                $countC++;
                } 
            }
        }else if($q_type_id == 13){//Memory test descriptive
            array_push($storage,$tmp_question);
            if(!empty($question->items)){ 
                foreach ($question->items as $item) { 
                    $tmp_value = [$var['variable'] .(string)($count+1) ."." .(string)($countC+1), "","","Memory test descriptive", $item['name']]; 
                    array_push($storage, $tmp_value); 
                    $countC++;
                } 
            }
        }
        else if($q_type_id == 14){//Memory Comparison
            array_push($storage,$tmp_question,["","","","Memory Comparison"]);
        }
        
        return $storage;
    }

    public function textBox($type){ 
        $list = array("Text","Whole Number","Decimal Number","Email Address","Date","Time","Date Time");
        $index =  (int)$type - 1; 
        return $list[$index];
    }
    public function multiMetrixType($type){
        $list = array("Dropdown","Radio","Textbox");
        $index =  (int)$type - 1; 
        return $list[$index];
    }
     
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($questionnaire_code, $id)
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        $export = Export::whereQuestionnaireId($questionnaire->id)->whereId($id)->first();
        $export->delete();

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "delete",
            'details' => "User ".Auth::user()->name." deleted export: ". $export->name. " in " . $questionnaire->name." questionnaire",
        ]);

        return Redirect::back();
    }
   
   
    public function exportData($records,$questionnaires,$display,$answer_key) 
    {
        $tmp_data = [];
        $headers = collectHeaders(collect($records)->pluck('url_data.internal'))->unique()->toArray();
        foreach (generator($records) as $record) {
            $tmp = [
                $record['participant_id'],
                $record['agent']['device'] ?? '-',
                $record['agent']['browser'] ?? '-',
                $record['agent']['ip'] ?? '-',
                $record['start_time_at'] != null ? Carbon::parse($record['start_time_at'])->toDateTimeString() : Carbon::parse($record['created_at'])->toDateTimeString(),
                Carbon::parse($record['updated_at'])->toDateTimeString(),
                date('H:i:s',
                    strtotime(Carbon::parse($record['updated_at'])->diff(Carbon::parse($record['updated_at']))->format('%H:%I:%S'))
                )//Duration
            ];
            foreach (generator($headers) as $header) {
                // $tmp[] = $record['url_data']['internal'][$header] ?? '-';
                $response = collect($record['responses'])->firstWhere('VAR', $header);
                $tmp[] = $response[$display] ?? $record['url_data']['internal'][$header] ?? '-';
            }
            $tmp_data[] = $tmp;
        }

        $list_question = [];
        if($answer_key == 1){
            foreach (generator($questionnaires) as $questionnaire) {
                $list_question = array_merge($list_question, $this->get_Question($questionnaire));  
            }
        } 
        return [
            'list_quest' => $list_question,
            'results' => $tmp_data,
            'headers' => $headers
        ];
    }

    

    public function download(Request $request, $questionnaire_code, $id) 
    {
        $to_export = Export::find($id);

        return Excel::download(CsvExport::new($to_export->data), $to_export->name.$to_export->setup['extension']);
    }
}
