<?php

namespace App\Http\Controllers;

use App\Models\Question_Template;
use App\Models\QuestionTemplate_Language;
use App\Models\Question_Type;
use App\Models\Questionnaire;
use App\Models\Project;
use App\Models\Gallery;
use App\Models\FluentGallery;
use App\Models\MainSurvey;
use App\Models\Activity_Log;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Gate;

use Inertia\Inertia;

class Question_TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): \Inertia\Response | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($request->questionnaire_code)->first();
        Gate::authorize('view', $questionnaire->parent_survey);

        $project = $questionnaire->project;

        $languageId = (int) ($request->language_id ?? 1);
        $language = QuestionTemplate_Language::find($languageId);
        
        // If language doesn't exist, try to get the first available language
        if (!$language) {
            $language = QuestionTemplate_Language::first();
            $languageId = $language ? $language->id : 1;
        }
        
        $question_by_language = $language ? $language->templates : collect([]);

        // Resolve galleries from Fluent (global) first, falling back to legacy project galleries
        $mainSurvey = MainSurvey::where('code', $project->code)->first();
        $globalGalleries = $mainSurvey
            ? FluentGallery::where('survey_id', $mainSurvey->id)->get()
            : collect();

        $data = [
            'language' => $language,
            'language_all' => QuestionTemplate_Language::all(),
            'questions' =>  $question_by_language,
            'questionnaire' => $questionnaire,
            'project' => $project,
            // Use global Fluent galleries when available so Gallery modal matches Questions page behaviour
            'gallery' => $globalGalleries->isNotEmpty() ? $globalGalleries : $project->galleries,
            'question_types' => Question_Type::all(),
            'pageSTR' => 'templates.index',
            'parts' => $project->questionnaires,
            'app_url' => env('APP_URL', 'http://no-APP_URL-in.env'),
        ];

        $templateData = $data['questions'];

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $templateData]) : Inertia::render('Questions/Template', $data);
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
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        // dd($request->name);
        $request->validate([
            'name' => ['required'],
            'language_id' => ['required'],
            'q_data.question' => ['required']
        ]);

        $user = Auth::user();

        $template = Question_Template::create([
            'name' => $request->name,
            'question_language_id' => $request->language_id,
            'question_type_id' => $request->q_data['question_type_id'],
            'question' => $request->q_data['question'],
            'setup' => $request->q_data['setup'],
            'choices' => $request->q_data['choices'],
            'rows' => $request->q_data['rows'],
            'columns' => $request->q_data['columns'],
            'items' => $request->q_data['items'],
            'item1' => $request->q_data['item1'],
            'item2' => $request->q_data['item2'],
            'values' => $request->q_data['values']
        ]);

        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "add",
            'details' => "User " . $user->name . " added new template " . $template->name,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $template]) : Redirect::back();
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);

        $questionT = Question_Template::find($id);
        $questionT->delete();

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "delete",
            'details' => "User " . Auth::user()->name . " deleted template question"
        ]);

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }

    public function copyTemplate(Request $request, $questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);

        $templateToCopy = Question_Template::whereId($id)->first();
        $user = Auth::user();
        $template = Question_Template::create([
            'question_language_id' => $templateToCopy['question_language_id'],
            'name' => $templateToCopy->name,
            'question_type_id' => $templateToCopy['question_type_id'],
            'question' => $templateToCopy['question'],
            'setup' => $templateToCopy['setup'],
            'choices' => $templateToCopy['choices'],
            'rows' => $templateToCopy['rows'],
            'columns' => $templateToCopy['columns'],
            'items' => $templateToCopy['items'],
            'item1' => $templateToCopy['item1'],
            'item2' => $templateToCopy['item2'],
            'values' => $templateToCopy['values']
        ]);

        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "copy",
            'details' => "User " . $user->name . " copy template " . $template->name,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
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
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);

        $request->validate([
            'q_data.question' => ['required']
        ]);
        $question = Question_Template::find($id);
        $question->update([
            'name' => $request->name,
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
        ]);

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "upate",
            'details' => "User " . Auth::user()->name . " updated template " . $request->name,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $question]) : Redirect::back();
    }
}
