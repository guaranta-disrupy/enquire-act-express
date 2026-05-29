<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Filter;
use App\Models\Export;
use App\Models\Record;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use Inertia\Inertia;

class AnalysisController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($questionnaire_code)
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();

        $questionnaires = Questionnaire::whereProjectId(
            $questionnaire->project->id
        )
            ->orderBy("id", "desc")
            ->get(["name", "code"]);
        $filters = Filter::whereProjectId($questionnaire->project->id)->get();
        $exports = Export::whereQuestionnaireId($questionnaire->id)->get();
        return Inertia::render("Analysis/Analysis", [
            "project" => $questionnaire->project,
            "questionnaire" => $questionnaire,
            "questionnaires" => $questionnaires,
            "questions" => $questionnaire->questions,
            "filters" => $filters,
            "exports" => $exports,
            "parts" => $questionnaires,
            "pageSTR" => "analysis.index",
            "engine_url" => env("ENGN_URL", "http://no-ENGN_URL-in.env"),
        ]);
    }

    public function getSummary(Request $request, $q_code)
    {
        /**
         * get questionnaire details
         * get questionnaires list
         * set filters ID
         * get complete records and get unique record by participant ID
         * get screen out records
         * count all sample and screen out data
         * get questions
         */
        $qnrCode = Questionnaire::whereCode($q_code)->first();
        $questionnaires = Questionnaire::whereProjectId($qnrCode->project->id)
            ->orderBy("id", "desc")
            ->get(["name", "code"]);
        $filters = Filter::find($request->filter_id);
        $filter_ids = $filters
            ? Str::of($filters->filtered_ids)
                ->explode(",")
                ->unique()
                ->toArray()
            : [];

        $raws = Record::whereQuestionnaireId($qnrCode->id)
            ->where("status", "cp")
            ->where("participant_id", "not like", "%test%")
            ->when($filter_ids, function ($query) use ($filter_ids) {
                return $query->whereIn("participant_id", $filter_ids);
            })
            ->get();
        $raws = $raws->unique("participant_id");
        $rawSO = Record::whereQuestionnaireId($qnrCode->id)
            ->where("status", "so")
            ->where("participant_id", "not like", "%test%")
            ->when($filter_ids, function ($query) use ($filter_ids) {
                return $query->whereIn("participant_id", $filter_ids);
            })
            ->get();
        $allSample = collect($raws)->count();
        $totalScreenout = collect($rawSO)->count();
        // dd($raws);
        // $questions = Question::whereQuestionnaireId($qnrCode->id)->orderBy('order','asc')->get()->toArray();
        $questions = Question::whereQuestionnaireId($qnrCode->id)
            ->orderBy("order", "asc")
            ->get();
        //fill answers with empty array
        $answerCounter = $this->fillEmptyWithArray($questions);
        $answerCount = [];
        $percentageCalc = [];
        $this->countAnswers($raws, $questions, $answerCounter);
        if ($allSample > 0) {
            $percentageCalc = $this->calculatePercentage(
                $answerCounter,
                $allSample
            );
        }

        $answerCount = $answerCounter;
        $summaryOutput = collect([
            "complete" => $allSample,
            "screenout" => $totalScreenout,
            "answercount" => $answerCount,
            "anspercentage" => $percentageCalc,
            "questions" => $questions,
            "project" => $qnrCode->project,
            "questionnaire" => $qnrCode,
        ]);

        return response()->json(
            [
                "completecount" => $allSample,
                "screenoutcount" => $totalScreenout,
                "data" => $summaryOutput,
                "project" => $qnrCode->project,
                "questionnaire" => $qnrCode,
                "questionnaires" => $questionnaires,
                "questions" => $questions,
                "filters" => $qnrCode->project->filters,
                "selected_filter" => $request->filter_id,
            ],
            200
        );
    }

    /**
     * Fill empty arrays
     * @param Array $questions
     * @return Array
     */

    private function fillEmptyWithArray($questions)
    {
        $answerCounter = [];
        foreach (generator($questions) as $q) {
            $answerCounter[$q["id"]] = [];
            switch ($q->question_type_id) {
                case 2:
                case 3:
                    foreach ($q["choices"] as $key => $c) {
                        $answerCounter[$q["id"]][$key] = 0;
                        if (isset($c["wc"])) {
                            if ($c["wc"]["enable"] == 1) {
                                $answerCounter[$q["id"]]["wc"] = [];
                                if (isset($c["wc"]["q_index"])) {
                                    $answerCounter[$q["id"]]["wc"][
                                        $c["wc"]["q_index"]
                                    ] = [];
                                }
                            }
                        }
                    }
                    break;
                case 4:
                case 6:
                    $answerCounter[$q["id"]] = [
                        "answers" => [],
                        "ids" => [],
                    ];
                    break;
                case 5:
                case 7:
                case 8:
                case 10:
                    foreach ($q["items"] as $key => $c) {
                        // dd($q['setup']['size']);
                        if (isset($q["setup"]["size"])) {
                            $answerCounter[$q["id"]][$key] = array_fill(
                                0,
                                $q["setup"]["size"],
                                0
                            );
                        } else {
                            $answerCounter[$q["id"]][$key] = [];
                        }
                    }
                    break;
                case 9:
                    foreach ($q["items"] as $k => $i) {
                        $answerCounter[$q["id"]][$k] = [];
                        foreach ($q["items"] as $k2 => $i2) {
                            // dd($q->setup['limit']);
                            // if($q->setup['limit'] > 0 && $q->setup['limit'] < $k - 1)
                            //     continue;
                            $answerCounter[$q["id"]][$k][$k2] = 0;
                        }
                    }
                    break;

                case 11:
                    $rows = isset($q["setup"]["displayByColumn"])
                        ? ($q["setup"]["displayByColumn"] == 1
                            ? $q["columns"]
                            : $q["rows"])
                        : $q["rows"];
                    $columns = isset($q["setup"]["displayByColumn"])
                        ? ($q["setup"]["displayByColumn"] == 1
                            ? $q["rows"]
                            : $q["columns"])
                        : $q["columns"];

                    foreach ($rows as $r_key => $r) {
                        $answerCounter[$q["id"]][$r_key] = [];
                        foreach ($columns as $c_key => $c) {
                            $answerCounter[$q["id"]][$r_key][$c_key] = 0;
                        }
                    }
                    break;
                case 12:
                    foreach ($q["rows"] as $r_key => $r) {
                        $answerCounter[$q["id"]][$r_key] = [];
                        foreach ($q["items"] as $i_key => $i) {
                            $answerCounter[$q["id"]][$r_key][$i_key] = [];
                            foreach ($i["column"] as $c_key => $c) {
                                switch ($c["type"]) {
                                    case 1:
                                        foreach (
                                            $c["items"]
                                            as $i2_key => $i2
                                        ) {
                                            $answerCounter[$q["id"]][$r_key][
                                                $i_key
                                            ][$i2_key] = 0;
                                        }
                                        break;
                                    case 2:
                                        $answerCounter[$q["id"]][$r_key][
                                            $i_key
                                        ][$c_key] = 0;
                                        break;
                                    case 3:
                                        $answerCounter[$q["id"]][$r_key][
                                            $i_key
                                        ] = [
                                            "answers" => [],
                                            "ids" => [],
                                        ];
                                        break;
                                }
                            }
                        }
                    }
                    break;
                case 14:
                    foreach ($q["item1"] as $key => $c) {
                        if ($key == 0) {
                            $answerCounter[$q["id"]][0] = [];
                        }
                        $answerCounter[$q["id"]][0][$key] = 0;
                    }
                    foreach ($q["item2"] as $key => $c) {
                        if ($key == 0) {
                            $answerCounter[$q["id"]][1] = [];
                        }
                        $answerCounter[$q["id"]][1][$key] = 0;
                    }
                    break;
            }
        }

        return $answerCounter;
    }

    /**
     * Counts answers
     * @param Array $raws
     * @param Array $questions
     * @param Array $answerCounter
     */

    private function countAnswers($raws, $questions, &$answerCounter)
    {
        foreach (generator($raws) as $key => $items) {
            foreach (generator($items->responses) as $rkey => $res) {
                try {
                    $ans = (object) $res;
                    // $current_q = (object) collect($questions)->where('id',$ans->question_id)->first();
                    $current_q = collect($questions)
                        ->where("id", $ans->question_id)
                        ->first();

                    // $setup = $current_q->setup;
                    $i = explode("_", $ans->TAG);
                    // $setup = (object) $current_q->setup;

                    // if(isset($current_q->question_type_id)){
                    if (isset($current_q["question_type_id"])) {
                        switch ($current_q->question_type_id) {
                            case 2:
                            case 3:
                                // dd($ans);
                                // if($setup->selectMultiple == 1){
                                if ($current_q->setup["selectMultiple"] == 1) {
                                    if (($ans->value == 1) & isset($i[1])) {
                                        // print_r( $answerCounter[$ans->question_id]);
                                        if (
                                            isset(
                                                $answerCounter[
                                                    $ans->question_id
                                                ][$i[1] - 1]
                                            )
                                        ) {
                                            $answerCounter[$ans->question_id][
                                                $i[1] - 1
                                            ] += 1;
                                        }
                                        // if(isset($ans->wc)){
                                        //     print("<pre>");
                                        //     print_r($i);

                                        // }
                                        if (isset($ans->wc)) {
                                            // print_r($answerCounter[$ans->question_id]);
                                            if (
                                                !isset(
                                                    $answerCounter[
                                                        $ans->question_id
                                                    ]["wc"][$i[1]]
                                                )
                                            ) {
                                                $answerCounter[
                                                    $ans->question_id
                                                ]["wc"][$i[1]] = [];
                                            }
                                            array_push(
                                                $answerCounter[
                                                    $ans->question_id
                                                ]["wc"][$i[1]],
                                                $ans->wc
                                            );
                                        }
                                    }
                                } else {
                                    $i = array_search(
                                        $ans->text,
                                        array_column(
                                            $current_q->choices,
                                            "name"
                                        )
                                    );
                                    // print_r($current_q);
                                    // if($current_q->order == 15)
                                    // print_r($ans);
                                    $answerCounter[$ans->question_id][$i] += 1;
                                    if (isset($ans->wc)) {
                                        array_push(
                                            $answerCounter[$ans->question_id][
                                                "wc"
                                            ][$i],
                                            $ans->wc
                                        );
                                    }
                                }
                                break;
                            case 4:
                            case 6:
                                // dd($items);
                                array_push(
                                    $answerCounter[$ans->question_id][
                                        "answers"
                                    ],
                                    $ans->text
                                );
                                array_push(
                                    $answerCounter[$ans->question_id]["ids"],
                                    $items->participant_id
                                );
                                break;
                            case 5:
                            case 7:
                                array_push(
                                    $answerCounter[$ans->question_id][
                                        $i[1] - 1
                                    ],
                                    $ans->text
                                );
                                break;
                            // case 8:
                            case 9:
                                // $av = $current_q->setup['mode'] == 0 ? $ans->value : $ans->value - 1;//ans->value
                                $av = $ans->value;
                                // print(" - $av - ");
                                // print('<pre>');
                                // dd( $answerCounter[$ans->question_id]);
                                // print_r($current_q->setup);
                                if ($av - 1 >= 0) {
                                    $answerCounter[$ans->question_id][
                                        $i[1] - 1
                                    ][$av - 1] += 1;
                                }
                                break;
                            case 10:
                                $answerCounter[$ans->question_id][$i[1] - 1][
                                    $ans->value - 1
                                ] += 1;
                                break;
                            case 11:
                                // if($setup->selectMultiple == 1){

                                if ($current_q->setup["selectMultiple"] == 1) {
                                    if ($ans->value == 1 && isset($i[2])) {
                                        $answerCounter[$ans->question_id][
                                            $i[1] - 1
                                        ][$i[2] - 1] += 1;
                                    }
                                } else {
                                    // dd($answerCounter[$ans->question_id][$i[1]-1]);
                                    // print_r($ans);
                                    if (
                                        $ans->value != "" &&
                                        isset(
                                            $answerCounter[$ans->question_id][
                                                $i[1] - 1
                                            ][$ans->value - 1]
                                        )
                                    ) {
                                        $answerCounter[$ans->question_id][
                                            $i[1] - 1
                                        ][$ans->value - 1] += 1;
                                    }
                                }
                                break;
                            case 12:
                                // print_r($ans);
                                $col = (object) $current_q->items[$i[2] - 1];
                                $type = $col->column[0]["type"];
                                switch ($type) {
                                    case 1:
                                    case 2:
                                        if ($ans->value != "") {
                                            $answerCounter[$ans->question_id][
                                                $i[1] - 1
                                            ][$i[2] - 1][$ans->value - 1] += 1;
                                        }
                                        break;
                                    case 3:
                                        array_push(
                                            $answerCounter[$ans->question_id][
                                                $i[1] - 1
                                            ][$i[2] - 1]["answers"],
                                            $ans->value
                                        );
                                        array_push(
                                            $answerCounter[$ans->question_id][
                                                $i[1] - 1
                                            ][$i[2] - 1]["ids"],
                                            $items->participant_id
                                        );
                                        break;
                                }
                                break;

                            case 14:
                                // $answerCounter[$q['id']][2]
                                $meta = (object) $ans->meta;
                                // dd($meta->item_list);
                                $answerCounter[$ans->question_id][
                                    $meta->item_list - 1
                                ][$i[1] - 1] += 1;
                                break;
                            default:
                                $answerCounter[$ans->question_id] =
                                    $current_q->question_type_id == 13
                                        ? "memory test descriptive"
                                        : "descriptive";
                                break;
                        }
                    }
                } catch (\Throwable $th) {
                    dd($th);
                }
            }
        }
    }

    /**
     * Calculate Percentage
     * @param Array $answerCounter
     * @param Integer $allSample
     * @return Array
     */
    private function calculatePercentage($answerCounter, $allSample)
    {
        $percentageCalc = [];
        foreach (generator($answerCounter) as $ac_key => $ac_value) {
            $percentageCalc[$ac_key] = [];
            if (is_array($ac_value)) {
                foreach (generator($ac_value) as $av_key => $av_value) {
                    if (is_array($av_value)) {
                        if (is_numeric($av_key)) {
                            foreach ($av_value as $avv_key => $avv_value) {
                                if (is_array($avv_value)) {
                                    foreach (
                                        generator($avv_value)
                                        as $mm_key => $mm_value
                                    ) {
                                        if (is_numeric($mm_key)) {
                                            $percentageCalc[$ac_key][$av_key][
                                                $avv_key
                                            ][$mm_key] = (int) round(
                                                ($mm_value / $allSample) * 100
                                            );
                                        }
                                    }
                                } else {
                                    $percentageCalc[$ac_key][$av_key][
                                        $avv_key
                                    ] = !is_numeric($avv_value)
                                        ? 0
                                        : (int) round(
                                            ($avv_value / $allSample) * 100
                                        );
                                }
                            }
                        } else {
                        }
                    } else {
                        $percentageCalc[$ac_key][$av_key] = (int) round(
                            ($av_value / $allSample) * 100
                        );
                    }
                }
            }
        }
        return $percentageCalc;
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
        //
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
    public function destroy($id)
    {
        //
    }
}
