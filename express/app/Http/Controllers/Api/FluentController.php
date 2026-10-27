<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExportController;
use App\Models\Activity_Log;
use App\Models\Project;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Theme;
use App\Models\Record;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FluentController extends ExportController
{
    public function tests(Request $request) {
      $project = Project::where(['code' => $request->code])->first();
      $tests = $project->questionnaires;

      return response()->json($tests); 
    }
    public function createQuestionnaire(Request $request) {
        $user = User::where(['email' => $request->user['email']])->first();
        if ($user == null) {
          $user = User::create([
            'name' => $request->user['name'],
            'email' => $request->user['email'],
            'password' => Hash::make('Facts787'),
            'system_user' => 'user'
          ]);
        }
        /* return response()->json([
          "status" => true,
            "code" => $request->user
        ]);  */
        //create new team if name not found
        $team = Team::firstOrCreate(
            ['name' => $request->team['name']],
            ['user_id' => $user->id, 'personal_team' => false]
        );

        $user->current_team_id = $team->id;
        $user->save();
        //create new project if code not found
        $project = Project::firstOrCreate(
            ['code' => $request->survey['code']],
            [
                'name' => $request->survey['name'],
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]
        );
        $code = generateCode(10);
        $count = $project->questionnaires()->count();
        $setup = array (
            'design' => 
            array (
              'sb-color' => '#ededed',
              'tb-color' => '#ffffff',
              'cal-color' => '#ffffff',
              'isb-color' => '#ffffff',
              'radio-color' => '#ededed',
              'button-color' => '#e38733',
              'sb-txt-color' => '#2c3e50',
              'tb-lbl-color' => '#2c3e50',
              'tb-txt-color' => '#2c3e50',
              'cal-lbl-color' => '#2c3e50',
              'isb-txt-color' => '#2c3e50',
              'tbl-lbl-color' => '#2c3e50',
              'background-img' => '',
              'checkbox-color' => '#ededed',
              'sb-hover-color' => '#ededed',
              'tb-error-color' => '#faddd5',
              'tbl-hint-color' => '#d3e7ff',
              'cal-hover-color' => '#ededed',
              'cal-today-color' => '#ededed',
              'isb-hover-color' => '#ededed',
              'radio-lbl-color' => '#2c3e50',
              'sb-border-color' => '#dbd7d7',
              'tb-border-color' => '#dbd7d7',
              'background-color' => '#ffffff',
              'button-txt-color' => '#ffffff',
              'cal-border-color' => '#dbd7d7',
              'choice-box-color' => '#ffffff',
              'slider-lbl-color' => '#2c3e50',
              'tbl-block-color1' => '#f1f1f1',
              'tbl-block-color2' => '#ffffff',
              'tbl-border-color' => '#ededed',
              'tbl-header-color' => '#1276be',
              'sb-selected-color' => '#0c5ba8',
              'slider-line-color' => '#dbd7d7',
              'warning-msg-color' => '#f15a2a',
              'button-hover-color' => '#e17126',
              'cal-selected-color' => '#1276be',
              'checkbox-lbl-color' => '#2c3e50',
              'isb-selected-color' => '#0c5ba8',
              'question-txt-color' => '#2c3e50',
              'radio-border-color' => '#dbd7d7',
              'sb-hover-txt-color' => '#2c3e50',
              'tb-error-txt-color' => '#2c3e50',
              'tbl-hint-lbl-color' => '#1276be',
              'isb-hover-txt-color' => '#2c3e50',
              'question-margin-top' => '14vmin',
              'slider-option-color' => '#dbd7d7',
              'question-margin-left' => '8vmin',
              'radio-selected-color' => '#1276be',
              'slider-default-color' => '#dbd7d7',
              'tbl-header-lbl-color' => '#ffffff',
              'cal-disable-lbl-color' => 'rgba(0,0,0,.5)',
              'checkbox-border-color' => '#dbd7d7',
              'question-margin-right' => '8vmin',
              'sb-selected-txt-color' => '#ffffff',
              'warning-msg-txt-color' => '#ffffff',
              'isb-selected-txt-color' => '#ffffff',
              'question-margin-bottom' => '14vmin',
              'checkbox-selected-color' => '#1276be',
              'choice-box-border-color' => '#ededed',
              'warning-msg-border-color' => '#b12b01',
              'background-img-brightness' => '100%',
              'slider-default-selected-color' => '#ffffff',
            ),
            'design_id' => 1,
            'random' => 0,
            'variable' => 'q',
            'variable_start' => 1,
            'hidePrevBtn' => 1,
            'showProgressBar' => 1,
            'display_logo' => 0,
            'theme' => 0,
            'display_asterisk' => 1,
            'messages' => 
            array (
              'loading' => 'Loading...',
              'saving' => 'Saving data...',
              'nextBtn' => 'NEXT',
              'prevBtn' => 'PREVIOUS',
              'finalBtn' => 'CONTINUE',
            ),
            'footer' => 
            array (
              'enable' => 1,
              'logo' => '',
            ),
            'customCSS' => '',
            'customJS' => '',
            'modals' => 
            array (
              'invalidProject' => 
              array (
                'title' => 'Application Error',
                'content' => 'Invalid Project.',
              ),
              'surveyClosed' => 
              array (
                'title' => 'Application Error',
                'content' => 'This survey is not yet available.',
              ),
              'noIdAssigned' => 
              array (
                'title' => 'Application Error',
                'content' => 'No ID assigned.',
              ),
              'connectionLost' => 
              array (
                'title' => 'Application Error',
                'content' => 'Unable to connect with server. Please check your internet connection.',
              ),
            ),
            'redirects' => 
            array (
              'complete' => 
              array (
                'content' => 'Preparing the next part...',
                'url' => env('APP_FLUENT_URL', '').'/engine/display/complete',
                'duration' => 0,
                'passExternalVars' => 1,
                'passInternalVars' => 1,
              ),
              'screenout' => 
              array (
                'content' => 'Please wait while we load the next step.',
                'url' => env('APP_FLUENT_URL', '').'/engine/display/screenOut',
                'duration' => 0,
                'passExternalVars' => 1,
                'passInternalVars' => 1,
              ),
              'qualityControl' => 
              array (
                'content' => 'Please wait while we load the next step.',
                'url' => env('APP_FLUENT_URL', '').'/engine/display/quotaFull',
                'duration' => 0,
                'passExternalVars' => 1,
                'passInternalVars' => 1,
              ),
            ),
          );
        // O design vem do tema cadastrado (Settings > Themes); o array acima é só reserva.
        $setup['design'] = Theme::find($setup['design_id'])?->setup['design'] ?? $setup['design'];
        $setup['variable'] = chr(ord('a') + $count);
        $project->questionnaires()->create([
            'name' => $request->label,
            'user_id' => $user->id,
            'theme_id' => $setup['design_id'],
            'setup' => $setup,
            'code' => $code,
        ]);

        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "store",
            'details' => "User ".$user->name." added new questionnaire under project name ".$project->name.' from fluent routes',
        ]);

        return response()->json([
            "status" => true,
            "code" => $code
        ]); 
    }

    public function sync_teams()
    {
        $teams_map = [
            7 => 35,
            11 => 1,
            13 => 18,
            15 => 31,
            16 => 29,
            18 => 12,
            20 => 25,
            23 => 33,
            24 => 34,
            25 => 25,
            39 => 42,
            40 => 44,
            41 => 43,
            44 => 45,
            48 => 47,
            49 => 30,
        ];
        foreach ($teams_map as $key => $value) {
            $team = Team::find($key);
            $team->forceFill([
                'fluent_id' => $value
            ])->save();
        }
        return 'ok';
    }

    public function respondent_data(Request $request, $q_code)
    {
        $questionnaire = Questionnaire::whereCode($q_code)->first(); 

        $filtered_ids = Str::of($request->ids)->explode(',')->unique()->toArray();
        
        $merge_records = Record::whereIn('id', $filtered_ids)->where(['questionnaire_id' => $questionnaire->id, 'status' => 'cp'])->get();

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

        $results = [];
        $answer = $request->answer_key == 1 ? ["Answer"] : [];
        $answer_key = $request->answer_key == 1 ? ["Key","Question","Matrix","Value","Description"] : [];
        
        $filtered = collect($records);
        $tmp_data = $this->exportData($filtered,[$questionnaire],$request->setup['display'],$request->answer_key);   
        $headers = collect(['Respondent ID', 'Device', 'Browser', 'IP', 'Start', 'End', 'Duration'])->merge($tmp_data['headers'])->toArray();
        $results = collect([$tmp_data['results'],$answer,$answer_key,$tmp_data['list_quest']])->prepend($headers)->toArray(); 

        return response()->json([
            'data' => $results
        ],200);
    }

    public function summmary_data(Request $request, $q_code) {
        $qnrCode = Questionnaire::whereCode($q_code)->first();
        $questionnaires = Questionnaire::whereProjectId($qnrCode->project->id)->orderBy('id', 'desc')->get(['name', 'code']);
        $filter_ids = $request->ids;

        $raws = Record::whereQuestionnaireId($qnrCode->id)->where('status' ,'cp')->where('participant_id','not like','%test%')->when($filter_ids, function($query) use($filter_ids) {
            return $query->whereIn('participant_id',$filter_ids);
        })->get();
        $raws = $raws->unique('participant_id');
        $rawSO = Record::whereQuestionnaireId($qnrCode->id)->where('status','so')->where('participant_id','not like','%test%')->when($filter_ids,function($query) use($filter_ids){
            return $query->whereIn('participant_id',$filter_ids);
        })->get();
        $allSample = collect($raws)->count();
        $totalScreenout = collect($rawSO)->count();
        // dd($raws);
        // $questions = Question::whereQuestionnaireId($qnrCode->id)->orderBy('order','asc')->get()->toArray();
        $questions = Question::whereQuestionnaireId($qnrCode->id)->orderBy('order','asc')->get();
        $answerCounter = [];
        $answerCount =[];
        $percentageCalc=[];


        //fill answers with empty array
        foreach(generator($questions) as $q){
            $answerCounter[$q['id']] = [];
            switch($q->question_type_id){
                case 2:
                case 3:
                    foreach($q['choices'] as $key=>$c){
                        $answerCounter[$q['id']][$key]=0;
                        if(isset($c['wc'])){
                            if($c['wc']['enable'] == 1){
                                $answerCounter[$q['id']]['wc'] = [];
                                if(isset($c['wc']['q_index']))
                                    $answerCounter[$q['id']]['wc'][$c['wc']['q_index']] = [];
                            }
                        }
        
                    }
                    break;
                case 4:
                case 6:
                    $answerCounter[$q['id']] = [
                        "answers" => [],
                        "ids" => []
                    ];
                    break;
                case 5:
                case 7:
                case 8:
                case 10:
                    foreach($q['items'] as $key=>$c){
                        // dd($q['setup']['size']);
                        if(isset($q['setup']['size']))
                            $answerCounter[$q['id']][$key] =  array_fill(0,$q['setup']['size'],0);
                        else
                            $answerCounter[$q['id']][$key]=[];
                    }
                    break;
                case 9:
                    
                        foreach($q['items'] as $k=>$i){
                            $answerCounter[$q['id']][$k] = [];
                            foreach($q['items'] as $k2=>$i2){
                                // dd($q->setup['limit']);
                                // if($q->setup['limit'] > 0 && $q->setup['limit'] < $k - 1)
                                //     continue;
                                $answerCounter[$q['id']][$k][$k2] = 0;
                            }
                        }
                    break;
              
                case 11:
                    $rows = isset($q['setup']['displayByColumn']) ?  ($q['setup']['displayByColumn'] == 1 ? $q['columns'] : $q['rows']) : $q['rows'];
                    $columns = isset($q['setup']['displayByColumn']) ? ($q['setup']['displayByColumn'] == 1 ? $q['rows'] : $q['columns']) : $q['columns'];
               
                    foreach( $rows  as $r_key=>$r){
                        $answerCounter[$q['id']][$r_key] = [];
                        foreach( $columns as $c_key=>$c){
                            $answerCounter[$q['id']][$r_key][$c_key]=0;
                        }
                    }
                    break;
                case 12:
                        foreach($q['rows'] as $r_key=>$r){
                            $answerCounter[$q['id']][$r_key] = [];
                            foreach($q['items'] as $i_key=>$i){
                                $answerCounter[$q['id']][$r_key][$i_key]=[];
                                foreach($i['column'] as $c_key=>$c){
                                    switch($c['type']){ 
                                        case 1:
                                            foreach($c['items'] as $i2_key=>$i2){
                                                $answerCounter[$q['id']][$r_key][$i_key][$i2_key] = 0;
                                            }
                                            break;
                                        case 2:
                                            $answerCounter[$q['id']][$r_key][$i_key][$c_key] = 0;
                                            break;
                                        case 3:
                                            $answerCounter[$q['id']][$r_key][$i_key] = [
                                                "answers" => [],
                                                "ids" => []
                                            ];  
                                            break;
                                    }
                                }
                                
                            }
                        }
                        break;
                case 14:
                    foreach($q['item1'] as $key=>$c){
                        if($key == 0)
                            $answerCounter[$q['id']][0] = [];
                            $answerCounter[$q['id']][0][$key]=0;
                    }
                    foreach($q['item2'] as $key=>$c){
                        if($key == 0)
                            $answerCounter[$q['id']][1] = [];
                            $answerCounter[$q['id']][1][$key]=0;
                    }
                    break;
                
            }
        }
        // dd( $answerCounter);
       foreach(generator($raws) as $key=>$items){
            foreach(generator($items->responses) as $rkey=>$res){
                try {
                    $ans = (object)$res;
                    // $current_q = (object) collect($questions)->where('id',$ans->question_id)->first();
                    $current_q = collect($questions)->where('id',$ans->question_id)->first();

                    // $setup = $current_q->setup;
                    $i = explode('_',$ans->TAG);
                    // $setup = (object) $current_q->setup;

                    // if(isset($current_q->question_type_id)){
                    if(isset($current_q['question_type_id'])){

                    
                    switch($current_q->question_type_id){
                        case 2:
                        case 3:
                            // dd($ans);
                            // if($setup->selectMultiple == 1){
                            if($current_q->setup['selectMultiple'] == 1){
                                if($ans->value == 1 & isset($i[1])){
                                    
                                    // print_r( $answerCounter[$ans->question_id]);
                                    if(isset( $answerCounter[$ans->question_id][$i[1]-1]))
                                        $answerCounter[$ans->question_id][$i[1]-1] += 1; 
                                    // if(isset($ans->wc)){
                                    //     print("<pre>");
                                    //     print_r($i);  
                                       
                                    // }
                                    if(isset($ans->wc)){
                                        // print_r($answerCounter[$ans->question_id]);
                                        if(!isset($answerCounter[$ans->question_id]['wc'][$i[1]]))
                                            $answerCounter[$ans->question_id]['wc'][$i[1]] = [];
                                        array_push($answerCounter[$ans->question_id]['wc'][$i[1]],$ans->wc);
                                        
                                    }
                                   
                                }
                                    
                            }else{
                                $i = array_search($ans->text, array_column($current_q->choices, 'name'));
                                // print_r($current_q);
                                // if($current_q->order == 15)
                                    // print_r($ans);
                                $answerCounter[$ans->question_id][$i] += 1;
                                if(isset($ans->wc)){
                                    array_push($answerCounter[$ans->question_id]['wc'][$i],$ans->wc);
                                    
                                }
                            }
                            break;
                        case 4:
                        case 6:
                            // dd($items);
                            array_push($answerCounter[$ans->question_id]['answers'],$ans->text);
                            array_push($answerCounter[$ans->question_id]['ids'],$items->participant_id);
                            break;
                        case 5:
                        case 7:
                            array_push($answerCounter[$ans->question_id][$i[1]-1],$ans->text);
                            break;
                        // case 8:
                        case 9:
                            // $av = $current_q->setup['mode'] == 0 ? $ans->value : $ans->value - 1;//ans->value
                            $av = $ans->value;
                            // print(" - $av - ");
                            // print('<pre>');
                            // dd( $answerCounter[$ans->question_id]);
                            // print_r($current_q->setup);
                            if($av-1 >= 0)
                                $answerCounter[$ans->question_id][$i[1]-1][$av-1] += 1;
                            break;
                        case 10:
                            $answerCounter[$ans->question_id][$i[1]-1][$ans->value - 1] += 1;
                            break;
                        case 11:
                            // if($setup->selectMultiple == 1){
                              
                            if($current_q->setup['selectMultiple'] == 1){
                                if($ans->value == 1 && isset($i[2]))
                                    $answerCounter[$ans->question_id][$i[1]-1][$i[2]-1] += 1;    
                            }else{
                                // dd($answerCounter[$ans->question_id][$i[1]-1]);
                                // print_r($ans);
                                if($ans->value != "" && isset($answerCounter[$ans->question_id][$i[1]-1][$ans->value - 1]))
                                    $answerCounter[$ans->question_id][$i[1]-1][$ans->value - 1] +=1;
                            }
                            break;
                        case 12:
                            // print_r($ans);
                            $col = (object) $current_q->items[$i[2]-1];
                            $type = $col->column[0]['type'];
                            switch($type){
                                case 1:
                                case 2:
                                    if($ans->value != "")
                                        $answerCounter[$ans->question_id][$i[1]-1][$i[2]-1][$ans->value - 1] +=1;
                                    break;
                                case 3:
                                    array_push($answerCounter[$ans->question_id][$i[1]-1][$i[2]-1]["answers"],$ans->value);
                                    array_push($answerCounter[$ans->question_id][$i[1]-1][$i[2]-1]["ids"],$items->participant_id);
                                    break;
                            }
                            break;
                        
                        case 14:
                            // $answerCounter[$q['id']][2]
                            $meta = (object) $ans->meta;
                            // dd($meta->item_list);
                            $answerCounter[$ans->question_id][$meta->item_list - 1][$i[1]-1] += 1;   
                            break;
                        default:
                            $answerCounter[$ans->question_id] = $current_q->question_type_id == 13 ? 'memory test descriptive' : 'descriptive';
                            break;
                    }
                }
                    
                } catch (\Throwable $th) {
                    dd($th);
                }
            }
        } 
    //     print("<pre>");
    //    print_r($answerCounter);
        if($allSample > 0)
        {
            foreach(generator($answerCounter) as $ac_key => $ac_value){
                $percentageCalc[$ac_key] = [];
                if(is_array($ac_value)){
                    foreach($ac_value as $av_key => $av_value){
                        if(is_array($av_value)){
                            if(is_numeric($av_key)){
                                foreach($av_value as $avv_key => $avv_value){              
                                    if(is_array($avv_value)){;
                                        foreach($avv_value as $mm_key => $mm_value){
                                            if(is_numeric($mm_key)){
                                                    $percentageCalc[$ac_key][$av_key][$avv_key][$mm_key] = (int) round(($mm_value/$allSample) * 100);    
                                            }
                                        }
                                    }else
                                        
                                        $percentageCalc[$ac_key][$av_key][$avv_key] = !is_numeric($avv_value) ?  0 :  (int) round(($avv_value/$allSample) * 100);    
                                }
                            }else{
                                
                            }
                                
                            
                        }else{
                            $percentageCalc[$ac_key][$av_key] = (int) round(($av_value/$allSample) * 100);
                        }
                    }
                }
            }
        }

        $answerCount = $answerCounter;
        $summaryOutput = collect(['complete' => $allSample, 'screenout' => $totalScreenout, 'answercount' => $answerCount, 'anspercentage' => $percentageCalc,'questions' => $questions, 'project' => $qnrCode->project, 'questionnaire' => $qnrCode]);
       
      
        return response()->json([
            'completecount' => $allSample,
            'screenoutcount' => $totalScreenout,
            'data' => $summaryOutput,
            'project' => $qnrCode->project,
            'questionnaire' => $qnrCode,
            'questionnaires' => $questionnaires,
            'questions' => $questions,
            'selected_filter'=>$request->filter_id
        ],200);
    }
}
