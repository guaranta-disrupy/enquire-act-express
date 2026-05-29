<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Survey;
use App\Models\TemplatePrime;
use App\Models\TemplateTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class TemplateController extends Controller
{
    public function storePrime(Request $request, $team, Project $project, Survey $survey)
    {
	Gate::authorize('update', $survey->parent_survey);

        $request->validate([
            'name' => 'required|string|max:64',
            'template_data' => 'nullable|array',
        ]);

        $templatePrime = TemplatePrime::create([
            'project_id' => $project->id,
            'survey_id' => $survey->id,
            'team_id' => $team,
            'user_id' => Auth::id(),
            'name' => $request->name,
            'data' => $request->template_data ?? [],
        ]);
        // return response()->json(['id' => $templatePrime->id], 201);
    }

    public function storeTarget(Request $request, $team, Project $project, Survey $survey)
    {
	Gate::authorize('update', $survey->parent_survey);

        $request->validate([
            'name' => 'required|string|max:64',
            'template_data' => 'nullable|array',
        ]);

        $templateTarget = TemplateTarget::create([
            'project_id' => $project->id,
            'survey_id' => $survey->id,
            'team_id' => $team,
            'user_id' => Auth::id(),
            'name' => $request->name,
            'data' => $request->template_data ?? [],
        ]);
        // return response()->json(['id' => $templateTarget->id], 201);
    }

    public function index($team, Project $project, Survey $survey): Response
    {
        Gate::authorize('view', $survey->parent_survey);
        
        $templatePrimes = TemplatePrime::where('team_id', $team)->get();
        $templateTargets = TemplateTarget::where('team_id', $team)->get();
        $variables = $project->variables;
        
        return Inertia::render('PrimesTargets/Index', [
            'project' => $project,
            'survey' => $survey,
            'templatePrimes' => $templatePrimes,
            'templateTargets' => $templateTargets,
            'variables' => $variables,
        ]);
    }

    public function showPrime(TemplatePrime $templatePrime): JsonResponse
    {
        return response()->json($templatePrime);
    }

    public function updatePrime(Request $request, $team, Project $project, Survey $survey, TemplatePrime $templatePrime)
    {
        Gate::authorize('update', $survey->parent_survey);

        $request->validate([
            'name' => 'required|string|max:64',
            'data' => 'sometimes|array',
        ]);

        $templatePrime->update([
            'name' => $request->name,
            'data' => $request->data ?? $templatePrime->data,
        ]);
				// return response()->json(['id' => $templatePrime->id], 201);
        // return response()->json(['message' => 'Prime template updated successfully']);
    }

    public function destroyPrime($team, Project $project, Survey $survey, TemplatePrime $templatePrime)
    {
        Gate::authorize('update', $survey->parent_survey);
        
        $templatePrime->delete();
        
        // return response()->json(['message' => 'Prime template deleted successfully']);
    }

    public function showTarget(TemplateTarget $templateTarget): JsonResponse
    {
        return response()->json($templateTarget);
    }

    public function updateTarget(Request $request, $team, Project $project, Survey $survey, TemplateTarget $templateTarget)
    {
        Gate::authorize('update', $survey->parent_survey);

        $request->validate([
            'name' => 'required|string|max:64',
            'data' => 'sometimes|array',
        ]);

        $templateTarget->update([
            'name' => $request->name,
            'data' => $request->data ?? $templateTarget->data,
        ]);

        // return response()->json(['message' => 'Target template updated successfully']);
    }

    public function destroyTarget($team, Project $project, Survey $survey, TemplateTarget $templateTarget)
    {
        Gate::authorize('update', $survey->parent_survey);
        
        $templateTarget->delete();
        
        // return response()->json(['message' => 'Target template deleted successfully']);
    }

    public function copyPrime(Request $request, $team, Project $project, Survey $survey, TemplatePrime $templatePrime)
    {
        Gate::authorize('update', $survey->parent_survey);

        $copied = TemplatePrime::create([
            'project_id' => $project->id,
            'survey_id' => $survey->id,
            'team_id' => $team,
            'user_id' => Auth::id(),
            'name' => ($templatePrime->name ?? 'Template') . ' copy',
            'data' => $templatePrime->data ?? [],
        ]);

        // return response()->json(['id' => $copied->id], 201);
    }

    public function copyTarget(Request $request, $team, Project $project, Survey $survey, TemplateTarget $templateTarget)
    {
        Gate::authorize('update', $survey->parent_survey);

        $copied = TemplateTarget::create([
            'project_id' => $project->id,
            'survey_id' => $survey->id,
            'team_id' => $team,
            'user_id' => Auth::id(),
            'name' => ($templateTarget->name ?? 'Template') . ' copy',
            'data' => $templateTarget->data ?? [],
        ]);

        // return response()->json(['id' => $copied->id], 201);
    }

    function saveTemplates(Request $request, $team, Project $project, Survey $survey){
    Gate::authorize('update', $survey->parent_survey);

    foreach($request->templatePrimes as $prime){
        $templatePrimes = TemplatePrime::findOrFail($prime['id']);
        $templatePrimes->update($prime);
    }
    
    foreach($request->templateTargets as $target){
        $templateTargets = TemplateTarget::findOrFail($target['id']);
        $templateTargets->update($target);
    }
    
}


}