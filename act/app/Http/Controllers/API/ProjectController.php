<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function destroy(Request $request)
    {
        $project = Project::where('code', $request->code)->first();
        $project->delete();
        return response()->json(['result' => 'ok'], 200);
    }

    public function surveys(Request $request)
    {
        $project = Project::where('code', $request->code)->first();
        if (is_null($project)) {
            return response()->json([]);
        }
        $surveys = $project->groups()->with(['surveys:id,group_id,name'])->get()->map(function($values) {
            return $values->surveys->map(function($v) {
                $v['url'] = env('APP_ENGINE', config('app.url') . '/launch2/?id=' . $v['id']);
                return $v;
            });
        })->flatten(1)->toArray();
        return response()->json($surveys);
    }
}
