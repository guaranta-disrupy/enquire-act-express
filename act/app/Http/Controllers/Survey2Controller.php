<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Survey;
use App\Models\Test;
use App\Models\TemplatePrime;
use App\Models\TemplateTarget;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use App\Rules\SurveyConfig;
use App\Rules\ProjectConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class Survey2Controller extends Controller
{
    public function index(Survey $survey): JsonResponse | Response
    {
        Gate::authorize('view', $survey->parent_survey);
        $surveys = $survey->group->surveys;
       
        $tests = $survey->tests()->with(['user:name,id', 'editor:name,id'])->orderBy('part', 'asc')->get();
        $project = $survey->group->project;
        $templatePrimes = $project->team->templatePrimes;
        $templateTargets = $project->team->templateTargets;
        $variables = $project->variables;
        // dd($templatePrimes, $templateTargets);
        $data = [
            'survey' => $survey,
            'tests' => $tests,
            'test' => $tests,
            'surveys' => $surveys,
            'project' => $project,
            'variables' => $variables,
            'templatePrimes' => $templatePrimes,
            'templateTargets' => $templateTargets,
            'userPermissions' => getUserPermissions($survey->parent_survey),
            'updated_at' => $survey->updated_at,
        ];

        
        return request()->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Surveys2/Index', $data); 

    }

    public function create(Survey $survey): JsonResponse | Response
    {
        Gate::authorize('update', $survey->parent_survey);
        $tests = $survey->tests()->with(['user:name,id', 'editor:name,id'])->orderBy('part', 'asc')->get();
        $project = $survey->group->project;
        $data = [
            'survey' => $survey,
            'tests' => $tests,
            'action' => 'create',
            'project' => $project,
            'userPermissions' => getUserPermissions($survey->parent_survey)
        ];
        return request()->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Surveys2/Create', $data);
    }


   public function update(Request $request, Survey $survey, Test $test): JsonResponse | Response
    {
        Gate::authorize('update', $survey->parent_survey);
        $tests = $survey->tests()->with(['user:name,id', 'editor:name,id'])->orderBy('part', 'asc')->get();
        $variables = $survey->group->project->variables->toArray();
        $project = $survey->group->project;
        $templatePrimes = $project->team->templatePrimes;
        $templateTargets = $project->team->templateTargets;
        // Find the specific test and merge it with the test ID
        $specificTest = $tests->where('id', $test->id)->first();

        if ($specificTest) {
            $specificTest->testid = $test->id;
        }
         
        $data = [
            'project' => $project,
            'variables' => $variables,
            'survey' => $survey,
            'tests' => $specificTest,
            'action' => 'update',
            'test' => $specificTest,
            'templatePrimes' => $templatePrimes,
            'templateTargets' => $templateTargets,
            'userPermissions' => getUserPermissions($survey->parent_survey)
        ];
        return request()->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Surveys2/Create2', $data);
    }

   

public function saveTest(Request $request): JsonResponse
{
    $testid = $request->input('testid');
    $surveyID = $request->input('survey_id');

    $survey = Survey::findOrFail($surveyID);

    Gate::authorize('update', $survey->parent_survey);

    $test = Test::findOrFail($testid);
    $test->update([
        'targets' => $request->input('tests.targets'),
        'primes' => $request->input('tests.primes'),
        'config' => $request->input('tests.config')
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Test saved successfully!',
        'test' => $test
    ]);
}

public function templates(Survey $survey): JsonResponse | Response
{
    Gate::authorize('view', $survey->parent_survey);
    $templatePrimes = $survey->group->project->templatePrimes;
    $templateTargets = $survey->group->project->templateTargets;
    $data = [
        'survey' => $survey,
        'templatePrimes' => $templatePrimes,
        'templateTargets' => $templateTargets,
        'userPermissions' => getUserPermissions($survey->parent_survey)
    ];
     return request()->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Surveys2/Templates', $data);
}  
     
    public function save_settings(Request $request, Survey $survey, Project $project): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $survey->parent_survey);
        // dd($survey->config['styles']['theme1']['landscape'],$request->config['styles']['theme1']['landscape']);
        if ($request->has('config2')) {
            $request->validate(['config2' => ['required', new ProjectConfig()]]);
            $project->config = $request->config2;
            $project->save();
        }
        if ($request->has('config')) {
            $request->validate(['config' => ['required', new SurveyConfig()]]);
            /**
             * Save the latest saved styles to the survey's config
             */
            $config = $request->config;
            $latestSavedStyles = $survey->config['styles'] ?? [];
            $config['styles'] = $latestSavedStyles;
            $survey->config = $config;
            // end
            // $survey->config = $request->config;
            $survey->save();
        }

        return request()->wantsJson() ? response()->json(['success' => true]) : back();
    }
}
