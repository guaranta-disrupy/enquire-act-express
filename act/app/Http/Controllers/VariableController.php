<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class VariableController extends Controller
{
    public function index(Project $project, Request $request)
    {
        $variables = $project->variables;

        return Inertia::render('Variables/Variable', [
            'project' => $project,
            'projects' => projectsList(),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $request->validateWithBag('createVariable', [
            'name' => ['required', 'alpha_dash', 'max:255', 'min:1'],
            'config.criteria' => ['required']
        ]);

        $variable = $project->variables()->create([
            'name' => strip_tags($request->name),
            'config' => $request->config,
            'user_id' => $request->user()->id,
        ]);
        return Redirect::back();
    }

    public function update(Request $request, Project $project, Variable $variable)
    {
        $request->validateWithBag('updateVariable', [
            'name' => ['required', 'alpha_dash', 'max:255', 'min:1'],
            'config.criteria' => ['required']
        ]);
        $variable->update([
            'name' => strip_tags($request->name),
            'config' => $request->config,
        ]);
        return Redirect::back();
    }

    public function copy(Request $request, Project $project, Variable $variable)
    {
        $new_var = $variable->replicate();
        $new_var->save();
        return Redirect::back();
    }

    public function destroy(Project $project, Variable $variable)
    {
        $variable->delete();
        return Redirect::back();
    }
}
