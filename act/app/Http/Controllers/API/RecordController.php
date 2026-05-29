<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Record;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Survey;

class RecordController extends Controller
{
    
    public function save(Request $request)
    {
        $request->validate([
            'url' => 'required',
            'survey_id' => 'required|exists:surveys,id',
            'responses' => 'required',
            'started_at' => 'required',
            'ended_at' =>  'required'
        ]);
        parse_str(parse_url($request->input('url'), PHP_URL_QUERY), $url_array);
        if (!isset($url_array['preview']) && $url_array['id'] !== 'test') {
            $survey = Survey::find($request->survey_id);
            if ($survey->parent_survey->is_not_live) {
                return response()->json(['success' => false, 'message' => 'Survey already completed or paused. Responses not recorded.'], 403);
            }
            
            Record::create([
                'survey_id' => $request->survey_id,
                'responses' => $request->responses,
                'participant_id' => $url_array['id'],
                'info' => getClientInfo($request)->toArray(),
                'started_at' => Carbon::parse($request->started_at),
                'ended_at' => Carbon::parse($request->ended_at)
            ]);
            // if (count($record->responses[0]['primes']) !== 5) {
            //     info('saving record: ' . count($record->responses[0]['primes']));
            // }
        }
        return response()->json(['result' => 'ok', 'message' => 'Responses successfully recorded']);
    }

    public function getRecord(Request $request)
    {
        if ($request->has('survey_id') && $request->has('id')) {
            $record = Record::where('survey_id', $request->survey_id)->where('participant_id', $request->id)->first();
            if ($record) {
                return response()->json($record->toArray(), 200);
            } else {
                return response()->json([], 200);
            }
        }
    }
}
