<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Survey;
use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class Variable2Controller extends Controller
{
    public function index(Request $request, Project $project, Survey $survey): JsonResponse | Response
    {
        Gate::authorize('view', $survey->parent_survey);
        $variables = $project->variables;
        $data = [
            'project' => $project,
            'projects' => projectsList(),
            'survey' => $survey,
            'userPermissions' => getUserPermissions($survey->parent_survey)
        ];
        return request()->wantsJson() ? response()->json(['data' => $data]) : Inertia::render('Variables2/Index', $data);
    }

    public function store(Request $request, Project $project): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $project->groups()->first()->surveys()->first()->parent_survey);
        $request->validateWithBag('createVariable', [
            'name' => ['required', 'alpha_dash', 'max:255', 'min:1'],
            'config.criteria' => ['required']
        ]);

        $variable = $project->variables()->create([
            'name' => strip_tags($request->name),
            'config' => $request->config,
            'user_id' => $request->user()->id,
        ]);
        return $request->wantsJson() ? response()->json(['success' => true, 'variable' => $variable]) : back();
    }

    public function update(Request $request, Project $project, Variable $variable): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $project->groups()->first()->surveys()->first()->parent_survey);
        $request->validateWithBag('updateVariable', [
            'name' => ['required', 'alpha_dash', 'max:255', 'min:1'],
            'config.criteria' => ['required']
        ]);
        $variable->update([
            'name' => strip_tags($request->name),
            'config' => $request->config,
        ]);
        return $request->wantsJson() ? response()->json(['success' => true, 'variable' => $variable]) : back();
    }

    public function copy(Request $request, Project $project, Variable $variable): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $project->groups()->first()->surveys()->first()->parent_survey);
        $new_var = $variable->replicate();
        $new_var->save();
        return $request->wantsJson() ? response()->json(['success' => true, 'variable' => $new_var]) : back();
    }

    public function destroy(Project $project, Variable $variable): RedirectResponse | JsonResponse
    {
        Gate::authorize('update', $project->groups()->first()->surveys()->first()->parent_survey);
        $variable->delete(); 
        return request()->wantsJson() ? response()->json(['success' => true]) : back();
    }
}

