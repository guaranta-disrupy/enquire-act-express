<?php

namespace App\Http\Controllers\Api;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Record;
use App\Models\Record_Raw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;


class EngineController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
        try {
            //get questionnaire_id
            $questionnaire = Questionnaire::with("questions")
                ->whereCode($request->code)
                ->first();

            $agent = new Agent();
            //record basic data for analysis
            $is_stored = Record::create([
                "participant_id" => $request->participant_id,
                "questionnaire_id" => $questionnaire->id,
                "status" => $request->status,
                "agent" => [
                    "ip" => $request->ip(),
                    "browser" =>
                    $agent->browser() .
                        " " .
                        $agent->version($agent->browser()),
                    "device" => ($agent->isDesktop() ? "Desktop " : "Mobile ") .
                        $agent->device(),
                    "platform" =>
                    $agent->platform() .
                        " " .
                        $agent->version($agent->platform()),
                ],
                "url_data" => $request->url_data,
                "responses" => $request->responses,
                "history" => $request->history,
                "start_time_at" => $request->started_at,
            ]);
            //record whole json for back up purposes
            $details = Record_Raw::create([
                "record_id" => $is_stored->id,
                "details" => $request->all(),
            ]);
            return response()->json([
                "status" => 1,
                "message" => "data recorded successfully",
                "RID" => $is_stored->id,
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "status" => 0,
                "message" => "an error occured while saving",
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($questionnaire_code)
    {
        $questionnaire = Questionnaire::with("questions")
            ->whereCode($questionnaire_code)
            ->first();
        $questionnaire["started_at"] = now();
        return response()->json(
            [
                "questionnaire" => $questionnaire,
            ],
            200
        );
    }

    /**
     * show2 records function
     *
     * @param string $questionnaire_code
     * @return \Illuminate\Http\Response
     */
    public function show2(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_data = null;
        $questionnaire = Questionnaire::with("questions")
            ->whereCode($request->questionnaire_code)
            ->first();

        if ($questionnaire->parent_survey->is_not_live && !$request->has('preview')) {
            return response()->json([
                "success" => false,
                "message" => "Survey already completed or paused.",
            ], 403);
        }
        if ($request->participant_id !== "id-test") {
            $user_data = Record::where("participant_id", $request->participant_id)
                ->where("questionnaire_id", $questionnaire->id)
                ->first();
        }

        if (!$user_data) {
            $user_data = Record::create([
                "participant_id" => $request->participant_id,
                "questionnaire_id" => $questionnaire->id,
                "status" => "og",
                "agent" => parse_user_agent_info($request),
                "url_data" => "",
                "config_data" => "",
                "responses" => "",
                "history" => "",
                "start_time_at" => now(),
            ]);
        }
        return response()->json(
            [
                "questionnaire" => $questionnaire,
                "temp_data" => $user_data,
            ],
            200
        );
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
        $update = Record::find($id);
        $questionnaire = $update->questionnaire;
        if ($questionnaire->parent_survey->is_not_live && !$request->has('preview')) {
            return response()->json([
                "success" => false,
                "message" => "Survey already completed or paused.",
            ], 403);
        }
        $update->update([
            "status" => $request->status,
            "url_data" => $request->url_data,
            "config_data" => $request->config_data,
            "responses" => $request->responses,
            "history" => $request->history,
        ]);
        return response()->json([
            "status" => 1,
            "message" => "data recorded successfully",
            "RID" => $update->id,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
