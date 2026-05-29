<?php

namespace App\Http\Controllers;

use App\Models\Activity_Log;
use App\Models\Project;
use App\Models\Filter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;

class FilterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($project_code)
    {
        return response()->json([
            'project' => Project::whereCode($project_code)->first()
        ], 200);
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
    public function store(Request $request, $project_code)
    {
        $request->validate([
            'name' => ['required']
        ]);

        if($request->sourceUrl){
            $urls = preg_split('/\r\n|\r|\n/', $request->sourceUrl);
            $filterIds = [];
            foreach ($urls as $url) {
                $filterIds[] = $this->idPuller($url);
            }
            $filterIds = implode(",", $filterIds);
        }
        else{
            $filterIds = $request->filtered_ids;
        }

        $project = Project::whereCode($project_code)->first();
        $project->filters()->create([
            'name' => $request->name,
            'details' => $request->details,
            'sourceUrl' => $request->sourceUrl,
            'filtered_ids' => $filterIds,
        ]);

        $user = Auth::user();
        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "add",
            'details' => "User ".$user->name." added new filter in ". $project->name. " project"
        ]);

        return Redirect::back();
    }

    public function idPuller($url): string
    {
        $http = new \GuzzleHttp\Client();
        $request = $http->get($url);
        return trim($request->getBody());
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
    public function update(Request $request, $project_code, $id)
    {
        $request->validate([
            'name' => ['required']
        ]);

        $project = Project::whereCode($project_code)->first();

        $filter = Filter::whereProjectId($project->id)->whereId($id)->first();
        $filter->update([
            'name' => $request->name,
            'details' => $request->details,
            'sourceUrl' => $request->sourceUrl,
            'filtered_ids' => $request->filtered_ids
        ]);

        $user = Auth::user();
        Activity_Log::create([
            'user_id' => $user->id,
            'action' => "update",
            'details' => "User ".$user->name." updated filter: ". $filter->name
        ]);

        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($project_code, $id)
    {
        $project = Project::whereCode($project_code)->first();
        $filter = Filter::whereProjectId($project->id)->whereId($id)->first();
        $filter->delete();

        Activity_Log::create([
            'user_id' => Auth::user()->id,
            'action' => "delete",
            'details' => "User ".Auth::user()->name." deleted filter: ". $filter->name. " in " . $project->name." project",
        ]);

        return Redirect::back();
    }

    public function syncIDs(Request $request, $project_code, $id = 0)
    {
        $project = Project::whereCode($project_code)->first();

        $filter_qry = Filter::whereProjectId($project->id);

        if ($id == 0) {
            $filters = $filter_qry->get();
        }else {
            $filters[0] = $filter_qry->whereId($request->id)->first();
        }
        
        foreach ($filters as $filter) {
            try {
                $http = Http::get($filter->sourceUrl);
                $filtered_ids = trim($http->body());
        
                $filter->update([
                    'filtered_ids' => $filtered_ids
                ]);

                Activity_Log::create([
                    'user_id' => Auth::user()->id,
                    'action' => "update",
                    'details' => "User ".Auth::user()->name." update filter: ". $filter->name. " filtered ids in " . $project->name." project",
                ]);

            } catch (\Throwable $th) {
                return Redirect::back()->with('status', false);
            }
        }

        return Redirect::back();
    }
}
