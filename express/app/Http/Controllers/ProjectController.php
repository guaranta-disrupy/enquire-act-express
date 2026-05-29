<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Theme;
use App\Models\Activity_Log;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $projects =  Auth::user()->system_user === "root_admin" ? Project::with('user:id,name')->orderBy('id', 'desc')->paginate(10) :
                     Auth::user()->currentTeam->projects()->with('user:id,name')->orderBy('id', 'desc')->paginate(10);
        $projects = Auth::user()->currentTeam->projects()->with('user:id,name')->orderBy('id', 'desc')->paginate(10);
        $themes = Theme::all();
        return Inertia::render('Projects/Index', [
            'themes' => $themes,
            'projects' => $projects,
        ]);
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
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'max:255'],
            'details' => ['nullable', 'max:255'],
        ]);
        $user = Auth::user();
        $project = $user->projects()->create([
            'name' => $request->name,
            'details' => $request->details ?? null,
            'team_id' => $request->input('team_id') ?? $user->currentTeam->id,
            'code' => $request->input('code') ?? generateCode(8),
        ]);

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "store",
            'details' => "User ".Auth::user()->name." added new project",
        ]);

        return ($request->has('source') && $request->source == 'fluent')
            ? Redirect::route('questionnaires.index', $project->code)
            : Redirect::back();
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
        // $request->validateWithBag('project', [
        //     'name' => 'required',
        //     'details' => 'required', 
        //     ]);
        $request->validate([
            'name' => ['required', 'max:255'], 
            'details' => ['required', 'max:255'],
        ]);
        
        $item = Project::whereCode($request->code)->first();
        $item->update([
            'name' => $request->name,
            'details' => $request->details, 
        ]);

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "update",
            'details' => "User ".Auth::user()->name." added new project ".$request->name,
        ]);

        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    { 
        $data = Project::whereCode($code)->first();
        $data->delete();

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "delete",
            'details' => "User ".Auth::user()->name." deleted project ".$data->name,
        ]);


        return Redirect::back();
    }

    public function getTrashed(){
        $projects = Project::onlyTrashed()->orderBy('id','desc')->get();
        
        return Inertia::render('Pages/ActivityLogs',['trashed_projects' => $projects]);
    }

    public function restoreTrashed($id){
        $projects = Project::onlyTrashed()->find($id);
        $projects->restore();
    }

    public function copy(Request $request){
        $request->validate([
            'name' => ['required', 'max:255']
        ]);

        $project = Project::whereCode($request->code)->first();
        $user = Auth::user();
        $new_project = $user->projects()->create([
            'name' => $request->name,
            'details' => $request->details,
            'team_id' => $user->currentTeam->id,
            'code' => generateCode(8),
        ]);

        foreach ($project->questionnaires as $questionnaire) {
            $new_questionnaire = copyQuestionnaire([
                'project_id' => $new_project->id,
                'user_id' => Auth::user()->id
            ], $questionnaire);
            
            foreach ($questionnaire->questions as $question) {
                copyQuestions(['questionnaire_id' => $new_questionnaire->id], $question);
            }
        }

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "update",
            'details' => "User ".Auth::user()->name." replicated project ".$project->name.' as '.$new_project->name
        ]);
        return Redirect::back();
    }
}
