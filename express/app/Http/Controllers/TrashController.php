<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\Questionnaire;
use App\Models\Question;
use App\Models\Activity_Log;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

use Inertia\Inertia;

class TrashController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        
        $projects = Project::onlyTrashed()->paginate(10);
        $questionnaire =  Questionnaire::onlyTrashed()->paginate(10);
        $questions = Question::onlyTrashed()->paginate(10);
        // $actions = User::withTrashed()->find(Auth::user()->id)->logs()->select('action')->groupBy('action')->get();
        return Inertia::render('Trash/Index',[
            'projects' => $projects,
            'questionnaires' => $questionnaire,
            'questions' => $questions,
            // 'actions' =>  $actions->map(function($o){ return strtolower($o->action);  })
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $name
     * @return \Illuminate\Http\Response
     */
    public function show($name)
    {
        //
        $projects = Project::onlyTrashed()->where('name','LIKE',"%$name%")->paginate(10);
        $questionnaires = Questionnaire::onlyTrashed()->where('name','LIKE',"%$name%")->paginate(10);
        $questions = Question::onlyTrashed()->where('question','LIKE',"%$name%")->paginate(10);
        
        return Inertia::render('Trash/Index',[
            'projects' => $projects,
            'questionnaires' => $questionnaires,
            'questions' => $questions,
        ]);
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
    public function update(Request $request, $id)
    {
        //
        // dd($request->type);
        $data = $this->getData($request->type, $id);
        $data->restore();

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "restore",
            'details' => "User ".Auth::user()->name." restored ". $data->name,
        ]);
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        // dd($request->type);
        $data = $this->getData($request->type, $id);
        $data->forceDelete();
        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "delete",
            'details' => "User ".Auth::user()->name." permanently deleted". $data->name,
        ]);
        return Redirect::back();
    }

    /**
     * Get the specified resource type from the storage
     * 
     * @param string $type
     * @param int $id
     * @return collection
     */
    private function getData($type,$id)
    {
        switch($type){
            case "projects":
                    $data = Project::withTrashed()->where('code',$id)->first();
                    
                    break;
            case "questionnaires":
                    $data = Questionnaire::withTrashed()->where('code',$id)->first();
                break;
            case "questions":
                    $data = Question::withTrashed()->find($id);
                break;
        }
        return $data;
    }
}
