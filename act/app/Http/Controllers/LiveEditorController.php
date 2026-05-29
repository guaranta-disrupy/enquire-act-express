<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use function Termwind\render;
use App\Models\Test;

class LiveEditorController extends Controller
{
   
    public function index(Request $request,$testid){

        $test = Test::findOrFail($testid);
       return Inertia::render('LiveEditor/content/index',
       ['tests'=>$test,'config'=>$test->config]);
    }


    public function TestEditor(Request $request,$testid){
         $test = Test::findOrFail($testid);
       return Inertia::render('Editors/index',
       ['tests'=>$test,'config'=>$test->config]);
    }

    public function saveTest(Request $request){

        // dd($request->all());
        $test = Test::findOrFail($request->tests['id']);
        
        $test->update([
        'targets' => $request->tests['targets'],
        'primes' => $request->tests['primes'],
        'config' =>$request->tests['config']
    ]);
        
            // return response()->json(['text' => $test]);
    }

    public function iframe(Request $request, $testid){
        $test = Test::findOrFail($testid);
        return Inertia::render('LiveEditor/iframe/index',
        ['tests'=>$test,'config'=>$test->config]);
    }
}
