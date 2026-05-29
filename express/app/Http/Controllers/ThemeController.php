<?php

namespace App\Http\Controllers;

use App\Models\Question_Template;
use App\Models\Question_Type;
use App\Models\Questionnaire;
use App\Models\Project;
use App\Models\Gallery;
use App\Models\Theme;
use App\Models\Activity_Log;
use App\Models\QuestionTemplate_Language;
use App\Models\FluentGallery;
use App\Models\MainSurvey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

use Inertia\Inertia;

class ThemeController extends Controller
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
        $language = QuestionTemplate_Language::find($request->language_id);
        // $question_by_language = QuestionTemplate_Language::find($request->language_id)->templates;

        $project = $questionnaire->project;

        // Prefer Fluent global galleries (shared with Questions page) when available
        $mainSurvey = MainSurvey::where('code', $project->code)->first();
        $globalGalleries = $mainSurvey
            ? FluentGallery::where('survey_id', $mainSurvey->id)->get()
            : collect();

        $themes = Theme::all();

        $data = [
            'language' => $language,
            'language_all' => QuestionTemplate_Language::all(),
            'questions' => Question_Template::all(),
            'questionnaire' => $questionnaire,
            'project' => $project,
            // Use global Fluent galleries when available so Gallery modal matches Questions page behaviour
            'gallery' => $globalGalleries->isNotEmpty() ? $globalGalleries : $project->galleries,
            'question_types' => Question_Type::all(),
            'pageSTR' => 'themes.index',
            'themes' => $themes,
            'parts' => $project->questionnaires, //questionnaires in main layout
            'app_url' => env('APP_URL', 'http://no-APP_URL-in.env'),
            'engine_url' => env('ENGN_URL', 'http://no-ENGN_URL-in.env')
        ];

        return request()->wantsJson()
            ? response()->json(['success' => true, 'data' => $themes, 'globalGalleries' => $globalGalleries])
            : Inertia::render('Theme/Theme', $data);
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
        $designID = DB::table('themes')->orderBy('id', 'desc')->first()->id;

        $request->validate([
            'name' => ['required'],
        ]);

        $user = Auth::user();

        $setup = $request->setup;
        $setup['design_id'] = $designID + 1;

        $theme = Theme::create([
            'text' => $request->name,
            'setup' => $setup
        ]);

        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "add",
            'details' => "User " . $user->name . " added new theme " . $theme->name,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $theme]) : Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Theme  $theme
     * @return \Illuminate\Http\Response
     */
    public function show(Theme $theme)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Theme  $theme
     * @return \Illuminate\Http\Response
     */
    public function edit(Theme $theme)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Theme  $theme
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $user = Auth::user();
        $request->validate([
            'name' => ['required'],
        ]);

        $theme = Theme::find($id);
        $theme->update([
            'text' => $request->name,
            'setup' => $request->setup,
        ]);

        $questionnaires = Questionnaire::whereThemeId($id)->get();
        foreach ($questionnaires as $questionnaire) {

            $setup = $questionnaire->setup;
            $setup['design'] = $request->setup['design'];

            $questionnaire->update([
                'setup' => $setup,
            ]);
        }

        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "update",
            'details' => "User " . $user->name . " updated " . $theme->name . " theme"
        ]);

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $theme]) : Redirect::back();
    }

    public function copyTheme(Request $request, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($request->questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $designID = DB::table('themes')->orderBy('id', 'desc')->first()->id;

        $request->validate([
            'name' => ['required'],
        ]);

        $user = Auth::user();

        $setup = $request->setup;
        $setup['design_id'] = $designID + 1;

        $theme = Theme::create([
            'text' => $request->name . " copy",
            'setup' => $setup
        ]);

        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "copu",
            'details' => "User " . $user->name . " copied " . $theme->name . " theme"
        ]);

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Theme  $theme
     * @return \Illuminate\Http\Response
     */
    public function destroy($questionnaire_code, $id): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $theme = Theme::find($id);
        $theme->delete();

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "delete",
            'details' => "User " . Auth::user()->name . " deleted theme" . $theme->name,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }

    public function applyTheme(Request $request, $questionnaire_code): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $setup = $request->setup;

        $setup['design_id'] = $request->theme_design_id;
        $setup['design'] = Theme::find($request->theme_design_id)['setup']['design'];

        $questionnaire->update([
            'name' => $request->name,
            'setup' => $setup,
            'theme_id' => $setup['design_id']
        ]);

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "Update",
            'details' => "User " . Auth::user()->name . " updated theme settings in " . $questionnaire->name . " questionnaire code: " . $questionnaire_code,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }
}
