<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProjectController extends Controller
{

    public function index(Request $request)
    {
        $keyword = $request->keyword ?? false;
        $projects = $request->user()->currentTeam->projects()->when($keyword, function($query, $keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        })->with('user:id,name')->orderBy('created_at', 'desc')->paginate(50);
        // dd($projects->toArray());
        return Inertia::render('Projects/Project', [
            'projects' => $projects,
            'keyw' => $request->keyword ?? null,

        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);
        $config = [
            'development' => true,
            'redirects' => [
                'complete' => 'https://splitsecondresearch.com?complete=true',
                'quality_control' => '',
                'pass_variable' => false,
            ],
            'progress' => [
                'show' => true,
                'text' => '',
                'continue' => 'true'
            ],
            'buttons' => [
                'next' => 'NEXT',
                'final' => 'CONTINUE'
            ],
            'texts' => [
                'saving' => 'Please wait while we save your progress...',
                'redirecting' => 'Preparing the next part...',
                'error_start' => 'Something went wrong while we were loading the app. Please refresh the page.',
                'error_saving' => 'Something went wrong while we were saving your progress. We\'ll try again! If this keeps on happening please refresh the page.'
            ],
            'records' => [
                'show_completes' => false
            ],
            'theme' => 0,
            'js' => '',
            'css' => ''
        ];
        $project = $request->user()->projects()->create([
            'name' => $request->input('name') ?? $request->input('project_name'),
            'code' => $request->input('code') ?? unique_code(config('app.code_length')),
            'team_id' => $request->input('team_id') ?? $request->user()->currentTeam->id,
            'config' => $config
        ]);
        $group_config = [
            'random' => false,
            'conditions' => []
        ];
        $group = $project->groups()->create([
            'user_id' => $request->user()->id,
            'name' => 'Group 1',
            'description' => '',
            'config' => $group_config
        ]);
        return ($request->has('source') && $request->source == 'fluent')
            ? Redirect::route('groups.index', $project)
            : Redirect::route('projects.index');
    }

    public function copy(Request $request, Project $project)
    {
        $new_project = $project->replicate()->fill([
            'user_id' => $request->user()->id,
            'code' => unique_code(config('app.code_length')),
            'name' => $project->name . ' - Copy',
            'team_id' => $request->team_id
        ]);
        $new_project->save();

        $project->groups->each(function($group) use($new_project, $request) {
            $new_group = $group->replicate()->fill([
                'user_id' => $request->user()->id,
                'project_id' => $new_project->id
            ]);
            $new_group->save();
            $group->surveys->each(function($survey) use($new_group, $request) {
                $new_survey = $survey->replicate()->fill([
                    'group_id' => $new_group->id,
                    'user_id' => $request->user()->id
                ]);
                $new_survey->save();
                $survey->tests->each(function($test) use($new_survey, $request) {
                    $new_test = $test->replicate()->fill([
                        'survey_id' => $new_survey->id,
                        'user_id' => $request->user()->id
                    ]);
                    $new_test->save();
                });
            });
        });
        $project->variables->each(function($variable) use($request, $new_project) {
            $new_variable = $variable->replicate()->fill([
                'user_id' => $request->user()->id,
                'project_id' => $new_project->id
            ]);
            $new_variable->save();
        });
        return back();
    }

    public function create()
    {
        return Inertia::render('Projects/Create');
    }

    public function settings(Project $project)
    {
        $p = $project->load('user:id,name,email,profile_photo_path');
        return Inertia::render('Projects/Settings', [
            'project' => $p,
            'projects' => projectsList(),
            'teams' => request()->user()->allTeams()->pluck('name', 'id')
        ]);
    }

    public function variables(Project $project)
    {
        $p = $project->load('user:id,name,email,profile_photo_path');
        return Inertia::render('Projects/Variable', [
            'project' => $p,
            'projects' => projectsList()
        ]);
    }

    public function saveSettings(Request $request, Project $project)
    {
        if ($request->has('name')) {
            $request->validate([
                'name' => 'string|required|max:255'
            ]);
            if ($project->name !== strip_tags($request->name)) {
                $user = $request->user();
                Cache::forget($user->currentTeam->id);
            }
            $project->name = strip_tags($request->name);

        }
        if ($request->has('config')) {
            $request->validate([
                'config.redirects.complete' => 'required|url',
                'config.buttons.next' => 'required|string',
                'config.buttons.final' => 'required|string',
                'config.texts.redirecting' => 'required|string',
                'config.texts.saving' => 'required',
                'config.texts.error_start' => 'required',
                'config.texts.error_saving' => 'required'
            ]);
            $project->config = $request->config;
        }
        $project->save();
        return back();
    }

    public function destroy(Request $request, Project $project)
    {
        if (!Hash::check($request->password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')]
            ]);
        } else {
            $project->delete();
        }

        return Redirect::route('projects.index');
    }

    public function projects(Request $request, Team $team = null)
    {
        $projects = $team ? $team->projects : $request->user()->currentTeam->projects;
        return response()->json($projects, 200);
    }
}
