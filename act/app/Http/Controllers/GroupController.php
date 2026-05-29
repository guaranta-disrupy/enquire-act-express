<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Project;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $groups = $project->groups()->with(['surveys.user:id,name', 'user:id,name'])->get();
        //dd($project->config['records']['show_completes']);
        if ($project->config['records']['show_completes'] ===  true) {
            $groups = $groups->map(function($group) {
                $group->surveys = $group->surveys->map(function($survey) {
                    return $survey->append('completes');
                });
                return $group;
            });
        }
        return Inertia::render('Groups/Group', [
            'groups' => $groups,
            'project' => $project,
            'projects' => projectsList(),
            'teams' => $request->user()->allTeams()
        ]);
    }

    public function settings(Project $project, Group $group)
    {
        $groups = DB::table('groups')->select(['id', 'name'])->where('project_id', $project->id)->whereNull('deleted_at')->get()->toArray();
        $g = $group->load('user:id,name,email,profile_photo_path');
        return Inertia::render('Groups/Settings', [
            'group' => $g,
            'project' => $project,
            'groups' => $groups
        ]);
    }

    public function create(Request $request, Project $project)
    {
        return Inertia::render('Groups/Create', [
            'project' => $project
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $request->validateWithBag('createGroup', [
            'name' => 'required|max:255'
        ]);
        
        $project->groups()->create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'config' => [
                'random' => false,
                'conditions' => []
            ]
        ]);

        return Redirect::route('groups.index', $project);
    }

    public function destroy(Project $project, Group $group)
    {
        $group->delete();
        return back();
    }

    public function rename(Request $request, Project $project, Group $group)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $group->update([
            'name' => strip_tags($request->name)
        ]);
        return back();
    }

    public function saveSettings(Request $request, Project $project, Group $group)
    {
        $request->validate([
            'config' => 'required'
        ]);

        $group->update([
            'config' => $request->config
        ]);
        return back();
    }

    public function copySurvey(Request $request, Project $project, Group $group, Survey $survey)
    {
        $new_survey = $survey->replicate()->fill([
            'group_id' => $group->id,
            'user_id' => $request->user()->id
        ]);
        $new_survey->save();
        foreach ($survey->tests as $test) {
            $new_test = $test->replicate()->fill([
                'survey_id' => $new_survey->id,
                'user_id' => $request->user()->id
            ]);
            $new_test->save();
        }
        return back();
    }

    public function groups(Request $request, Project $project)
    {
        return $project->groups;
    }
}
