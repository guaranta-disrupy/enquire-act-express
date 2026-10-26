<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Project;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class SurveyController extends Controller
{
    public function index(Request $request, Group $group)
    {
        return Inertia::render('Surveys/Index', [
            'group' => $group
        ]);
    }

    public function create(Request $request, Group $group)
    {
        return Inertia::render('Surveys/Create', [
            'group' => $group,
            'project' => $group->project
        ]);
    }

    public function store(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required|max:255'
        ]);
        $config = [
            'css' => "",
            'js' => "",
            'forward_response' => false,
            'response_var' => 'ast',
            'swipe' => false,
            'left_key' => 'f',
            'right_key' => 'j',
            'click_instructions' => 'Click Yes or No.',
            'swipe_instructions' => ' Or SWIPE <strong>LEFT</strong> or <strong>RIGHT</strong>',
            'keypress_instructions' => "Press <strong>F</strong> if you disagree or <strong>J</strong> if otherwise.",
            'keypress_click' => false,
            'fullscreen_text' => "For the best experience, please enter fullscreen.",
            'fullscreen_button' => "Enter Fullscreen",
            'engine_bg_image' => null,
            // 'dummy_trial' => false,

        ];
        Survey::create([
            'name' => $request->name,
            'user_id' => $request->user()->id,
            'config' => $config,
            'group_id' => $group->id
        ]);

        return Redirect::route('groups.index', $group->project);
    }

    public function destroy(Group $group, Survey $survey)
    {
        $survey->delete();
        return back();
    }

    public function edit(Group $group, Survey $survey)
    {
        return Inertia::render('Surveys/Edit', [
            'group' => $group,
            'survey' => $survey,
            'project' => $group->project
        ]);
    }

    public function update(Request $request, Group $group, Survey $survey)
    {
        $request->validate([
            'name' => 'required|max:255'
        ]);

        $survey->update([
            'name' => $request->name
        ]);

        return Redirect::route('groups.index', $group->project);
    }
}
