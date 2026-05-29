<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Rules\TestConfig;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TestController extends Controller
{

    public function index(Request $request, Survey $survey): JsonResponse | Response
    {
        Gate::forUser($request->user())->authorize('view', $survey->parent_survey);
        $tests = $survey->tests()->with(['user:name,id', 'editor:name,id'])->orderBy('part', 'asc')->get();
        $group_ids = $survey->group->project->groups->pluck('id');
        $allSurveys = Survey::whereIn('group_id', $group_ids)->get();
        $data = [
            'tests' => $tests,
            'survey' => $survey,
            'projects' => projectsList(),
            'project' => $survey->group->project,
            'allSurveys' => $allSurveys
        ];
        return $request->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Tests/Test', $data);
    }

    public function settings(Survey $survey): JsonResponse | Response
    {
        Gate::authorize('view', $survey->parent_survey);
        $group_ids = $survey->group->project->groups->pluck('id');
        $allSurveys = Survey::whereIn('group_id', $group_ids)->get();
        $data = [
            'survey' => $survey,
            'allSurveys' => $allSurveys
        ];
        return request()->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Tests/Settings', $data);
    }

    public function saveSettings(Request $request, Survey $survey): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $survey->parent_survey);
        $survey->config = $request->config;
        $survey->save();
        return $request->wantsJson() ? response()->json(['success' => true]) : back();
    }

    public function create(Survey $survey): JsonResponse | Response
    {
        Gate::authorize('update', $survey->parent_survey);
        $group = $survey->group;
        $project = $group->project;
        $data = [
            'survey' => $survey,
            'group' => $group,
            'project' => $project,
            'action' => 'create'
        ];
        return request()->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Tests/Create', $data);
    }

    public function store(Request $request, Survey $survey): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $survey->parent_survey);

        $request->validate([
            'config' => ['required', new TestConfig()],
            'primes' => 'array',
            'targets' => 'array',
        ]);

        $test = $survey->tests()->create([
            'config' => $request->config,
            'primes' => $request->primes,
            'targets' => $request->targets,
            'user_id' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'part' => $survey->tests()->count() + 1
        ]);

        return $request->wantsJson() ? response()->json(['success' => true, 'test' => $test]) : back();
    }

    public function edit(Survey $survey, Test $test): JsonResponse | Response
    {
        Gate::authorize('view', $survey->parent_survey);
        $next = Test::where('survey_id', $survey->id)->where('id', '>', $test->id)->first();
        $previous = Test::where('survey_id', $survey->id)->where('id', '<', $test->id)->orderBy('id', 'desc')->first();
        $navigator = [
            'hasNext' => !is_null($next),
            'hasPrevious' => !is_null($previous),
            'next' => !is_null($next) ? route('tests.edit', [$survey, $next]) : null,
            'previous' => !is_null($previous) ? route('tests.edit', [$survey, $previous]) : null,
        ];
        $group = $survey->group;
        $project = $group->project;
        $data = [
            'survey' => $survey,
            'group' => $group,
            'test' => $test,
            'project' => $project,
            'navigator' => $navigator,
            'action' => 'update'
        ];
        return request()->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Tests/Create', $data);
    }

    public function update(Request $request, Survey $survey, Test $test): RedirectResponse | JsonResponse
    {
        dd($request->all());
        Gate::authorize('update', $survey->parent_survey);
        $request->validate(['config' => [new TestConfig()]]);
        $test->update([
            'config' => $request->config,
            'primes' => $request->primes,
            'targets' => $request->targets,
            'updated_by' => $request->user()->id
        ]);
        $test->survey->touch();
        $test->survey->group->touch();
        $test->survey->group->project->touch();
        return $request->wantsJson() ? response()->json(['success' => true, 'test' => $test]) : back();
    }

    public function destroy(Survey $survey, Test $test): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $survey->parent_survey);
        $test->delete();
        return request()->wantsJson() ? response()->json(['success' => true]) : back();
    }

    public function copy(Survey $survey, Test $test): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $survey->parent_survey);
        $new_test = $test->replicate()->fill([
            'survey_id' => $survey->id,
            'part' => $survey->tests()->count() + 1
        ]);
        $new_test->save();
        return request()->wantsJson() ? response()->json(['success' => true, 'test' => $new_test]) : back();
    }

    public function move(Survey $survey, Test $test, $part): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $survey->parent_survey);
        $tests = Test::where('survey_id', $survey->id)->orderBy('part', 'asc')->get()->toArray();
        $out = array_splice($tests, ($test->part - 1), 1);
        array_splice($tests, ($part - 1), 0, $out);

        foreach (generator($tests) as $key => $t) {
            $tt = Test::find($t['id']);
            $tt->part = $key + 1;
            $tt->save();
        }
        return request()->wantsJson() ? response()->json(['success' => true]) : back();
    }
}
