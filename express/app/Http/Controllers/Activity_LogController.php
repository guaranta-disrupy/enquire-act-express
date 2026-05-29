<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\Activity_Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Inertia\Inertia;

class Activity_LogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /* $search = $request->search ?? null;
        $surveys = Auth::user()->currentTeam->surveys()->when($search, function($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })->orderBy('created_at', 'desc')->with('user:id,name')->paginate(10); */

        $logs = Activity_Log::orderBy('id','desc')->paginate(10);
        //$actions = User::withTrashed()->find(Auth::user()->id)->logs()->select('action')->groupBy('action')->get();
        return Inertia::render('ActivityLogs',[
            'logs' => $logs,
            'actions' =>  ['add', 'update', 'store', 'delete']//$actions->map(function($o){ return strtolower($o->action);  })
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
       
        $logs = User::withTrashed()->find($id)->logs()->paginate(10);
        $actions = User::withTrashed()->find($id)->logs()->select('action')->groupBy('action')->get();
        return Inertia::render('ActivityLogs',[
            'logs' => $logs,
            'actions' =>  $actions->map(function($o){ return strtolower($o->action);  })
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
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
