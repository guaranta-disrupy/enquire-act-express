<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Theme;
use App\Models\Question;
use App\Models\Question_Type;
use App\Models\Questionnaire;
use App\Models\Gallery;
use App\Models\Activity_Log;
use App\Models\Question_Template;
use App\Models\QuestionTemplate_Language;
use App\Models\FluentGallery;
use App\Models\MainSurvey;
use App\Models\FluentGlobalSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

use Inertia\Inertia;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): \Inertia\Response | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($request->questionnaire_code)->first();

        if (!$questionnaire) {
            // Return a proper 404 instead of calling methods on null
            dd($request->questionnaire_code);
        }

        $questionnaire->load('questions');
        $getQuestions = $questionnaire->questions->load('updateInfo:id,name');
        $project = $questionnaire->project;
       

        Gate::authorize('view', $questionnaire->parent_survey);
        $language = QuestionTemplate_Language::find($request->language_id ?? 1);
        $question_by_language = QuestionTemplate_Language::find($request->language_id ?? 1)->templates;

        $mainSurvey = MainSurvey::where('code', $project->code)->first();

        $globalGalleries = FluentGallery::where('survey_id', $mainSurvey->id)->get();
        // dd($globalGalleries->toArray());
        // Only fetch the specific config/message columns we need
        $globalSetup = FluentGlobalSetup::where('survey_id', $mainSurvey->id)
            ->first(['display_config', 'express_messages']);

        //dd(FluentGlobalSetup::where('survey_id', $mainSurvey->id)->first());

             //dd($globalSetup->express_messages);
        $data = [
            'language' => $language,
            'language_all' => QuestionTemplate_Language::all(),
            'survey_name' => $mainSurvey->name,
            'questions' => $getQuestions,
            'question_templates' => Question_Template::all(),
            'questionnaire' => $questionnaire,
            'question_types' => Question_Type::all(),
            'project' => $questionnaire->project,
            'projects' => Project::where(['team_id' => Auth::user()->currentTeam->id]),
            'gallery' => $globalGalleries,
            'global_setup' => $globalSetup,
            'parts' => $questionnaire->project->questionnaires,
            'themes' => Theme::all(),
            'pageSTR' => 'questions.index',
            'app_url' => env('APP_URL', 'http://no-APP_URL-in.env'),
            'engine_url' => env('ENGN_URL', 'http://no-ENGN_URL-in.env')
        ];
        // dd($data);

        $questionData = $data['questions'];

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $questionData]) : Inertia::render('Questions/Index', $data);
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
    public function store(Request $request, $questionnaire_code): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'question' => ['required']
        ]);
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);

        $user = Auth::user();

        $count = $questionnaire->questions()->count();
        $question = $questionnaire->questions()->create([
            'order' => ++$count,
            'question_type_id' => $request->question_type_id,
            'question' => $request->question,
            'setup' => $request->setup,
            'choices' => $request->choices,
            'rows' => $request->rows,
            'columns' => $request->columns,
            'items' => $request->items,
            'item1' => $request->item1,
            'item2' => $request->item2,
            'values' => $request->values,
            'questionnaire_id' => $questionnaire->id,
            'updated_by' => $user->id,
        ]);

        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "add",
            'details' => "User " . $user->name . " added new quesion in " . $questionnaire->name . " questionnaire code: " . $questionnaire->code,
        ]);

        $variable_start = collect($questionnaire->setup)->get('variable_start', 1);
        $questionKey = 'Q' . ($question->order + ($variable_start - 1));

        return request()->wantsJson() ? response()->json([
            'success' => true, 
            'data' => $questionnaire,
            'question' => [
                'id' => $question->id,
                'key' => $questionKey,
                'order' => $question->order
            ]
        ]) : Redirect::back();
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
    public function update(Request $request, $questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'question' => ['required']
        ]);
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $question = Question::find($id);
        $question->update([
            'question_type_id' => $request->question_type_id,
            'question' => $request->question,
            'setup' => $request->setup,
            'choices' => $request->choices,
            'rows' => $request->rows,
            'columns' => $request->columns,
            'items' => $request->items,
            'item1' => $request->item1,
            'item2' => $request->item2,
            'values' => $request->values,
            'updated_by' => Auth::user()->id,
        ]);

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "update",
            'details' => "User " . Auth::user()->name . " updated question " . $question->order . " in " . $questionnaire->name . " questionnaire code: " . $questionnaire_code,
        ]);
        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $question]) : Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $question = Question::find($id);
        $question->delete();
        $questions = $questionnaire->questions->toArray();
        foreach ($questions as $key => $q) {
            $qquestion = Question::find($q['id']);
            $qquestion->order = $key + 1;
            $qquestion->save();
        }

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "delete",
            'details' => "User " . Auth::user()->name . " deleted question " . $question->id . " in " . Questionnaire::whereCode($questionnaire_code)->first()->name . " questionnaire code: " . $questionnaire_code,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }

    public function move_question(Request $request, $questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        // $question = Question::where('id', $id)->first();
        // dd($question->setup['advanceSkiplogic']['leave'][0]['conditions'][0]['var']);
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $question = Question::find($id);
        $questions = $questionnaire->questions->toArray();
        //take question to move
        $out = array_splice($questions, ($question->order - 1), 1);
        //move question
        array_splice($questions, ($request->order - 1), 0, $out);
        //apply changes
        foreach ($questions as $key => $q) {
            $qquestion = Question::find($q['id']);
            $qquestion->order = $key + 1;
            $qquestion->save();
        }

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "move",
            'details' => "User " . Auth::user()->name . " moved questions in " . $questionnaire->name . " questionnaire code: " . $questionnaire_code,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }

    public function updateLogic(Request $request, $questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {

        // return $request;
        $question = Question::whereId($id)->firstOrFail();
        $questionnaire = Questionnaire::whereId($question->questionnaire_id)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $question->update([
            'setup->advanceSkiplogic' => [
                'enter' => $request->enter,
                'leave' => $request->leave
            ]
        ]);

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "update",
            'details' => "User " . Auth::user()->name . " updated skip logic in " . $questionnaire->name . " Q" . $question->order,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $question]) : Redirect::back();
    }

    public function copyQuestion(Request $request, $questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $questionToCopy = Question::whereId($id)->first();

        $order = $questionnaire->questions()->count();
        copyQuestions(['order' => $order + 1], $questionToCopy);

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }

    public function copyQuestionTo(Request $request, $questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionToCopy = Question::whereId($id)->get();
        // return $questionToCopy;
        $qnrId = Questionnaire::whereCode($request->qnrCodeToCopy)->first();
        Gate::authorize('update', $qnrId->parent_survey);
        // return $qnrId->id;
        $copyQ = $questionToCopy[0]->replicate();
        $copyQ->questionnaire_id = $qnrId->id;
        $copyQ->save();

        $questionReorder = Question::whereQuestionnaireId($qnrId->id)->orderBy('order', 'asc')->get();

        foreach ($questionReorder as $key => $newQorder) {
            $newQorder->update(['order' => $key + 1]);
        }

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }

    public function restoreQuestion($id)
    {
        $question = Question::onlyTrashed()->find($id);
        Gate::authorize('update', $question->questionnaire->parent_survey);
        $question->restore();

        return Redirect::back();
    }

    public function usetemplate(Request $request, $questionnaire_code): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $count = $questionnaire->questions()->count();
        $questionnaire->questions()->create([
            'order' => ++$count,
            'question_type_id' => $request->q_data['question_type_id'],
            'question' => $request->q_data['question'],
            'setup' => $request->q_data['setup'],
            'choices' => $request->q_data['choices'],
            'rows' => $request->q_data['rows'],
            'columns' => $request->q_data['columns'],
            'items' => $request->q_data['items'],
            'item1' => $request->q_data['item1'],
            'item2' => $request->q_data['item2'],
            'values' => $request->q_data['values'],
            'questionnaire_id' => $questionnaire->id,
        ]);

        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "add",
            'details' => "User " . $user->name . " used new template in " . $questionnaire->name . " questionnaire code: " . $questionnaire_code,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $questionnaire]) : Redirect::back();
    }
}
