<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Questionnaire;
use App\Models\Gallery;
use App\Models\Activity_Log;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($questionnaire_code)
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('view', $questionnaire->parent_survey);
        return Inertia::render('Gallery/Index', [
            'project' => $questionnaire->project,
            'questionnaire' => $questionnaire,
            'gallery' =>  $questionnaire->project->galleries,
            'parts' => $questionnaire->project->questionnaires,
            'pageSTR' => 'gallery.index',
            'app_url' => env('APP_URL', 'http://no-APP_URL-in.env'),
            'engine_url' => env('ENGN_URL', 'http://no-ENGN_URL-in.env')
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
    public function store(Request $request, $questionnaire_code)
    {

        $request->validate([
            'file' => 'required|file',
            'chunk' => 'required|integer',
            'totalChunks' => 'required|integer',
            'originalName' => 'required|string'
        ]);
        $file = $request->file('file');

        //dd($request);
        $chunk = $request->input('chunk');
        $totalChunks = $request->input('totalChunks');
        $filename = $request->input('name');
        $originalName = $request->input('originalName');

        $chunkFilename = "chunk_{$chunk}_{$filename}";

        $content = file_get_contents($file->getRealPath());
        Storage::disk('local')->put("tmp_chunks/{$chunkFilename}", $content);

        $user = Auth::user();
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        $project = Project::whereCode($questionnaire->project->code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);

        if ($chunk == $totalChunks - 1) {
            // dd('mali', $chunk);
            // All chunks received, merge the file
            $this->mergeChunks($filename, $totalChunks, $originalName, $questionnaire->project->code, $request, false);
            return redirect()->back()->with('message', 'Upload complete');
        }


        Activity_Log::create([
            'user_id' => $user->id,
            'action' => 'add',
            'details' => 'User ' . $user->name . "added a new file image in " . $project->name,
        ]);
        return Redirect::back();
    }

    private function mergeChunks($filename, $totalChunks, $originalName, $code, $request, $isUpdate)
    {
        //dd($request->img_id);
        // Ensure the public/videos directory exists
        Storage::disk('public')->makeDirectory('videos');

        $finalPath = public_path("/gallery/{$filename}");
        $out = fopen($finalPath, "wb");
        $user = Auth::user();

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFilename = "chunk_{$i}_{$filename}";
            $chunkPath = storage_path("app/tmp_chunks/{$chunkFilename}");

            if (file_exists($chunkPath)) {
                $in = fopen($chunkPath, "rb");
                stream_copy_to_stream($in, $out);
                fclose($in);
                unlink($chunkPath);
            }
        }

        fclose($out);

        $project = Project::whereCode($code)->first();

        if ($isUpdate) {
            $gallery = Gallery::find($request->img_id['id']);
            $gallery->update([
                'filename' => $filename,
                'originalName' => $originalName,
                'user_id' => $user->id,
                'project_id' => $project->id
            ]);
        } else {
            $gallery = $project->galleries()->create([
                'filename' => $filename,
                'originalName' => $originalName,
                'user_id' => $user->id,
                'project_id' => $project->id
            ]);
        }
        return $gallery;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $questionnaire_code)
    {
        $request->validate([
            'file' => 'required|file',
            'chunk' => 'required|integer',
            'totalChunks' => 'required|integer',
            'originalName' => 'required|string'
        ]);
        $file = $request->file('file');
        $chunk = $request->input('chunk');
        $totalChunks = $request->input('totalChunks');
        $filename = $request->input('name');
        $originalName = $request->input('originalName');

        $chunkFilename = "chunk_{$chunk}_{$filename}";

        $content = file_get_contents($file->getRealPath());
        Storage::disk('local')->put("tmp_chunks/{$chunkFilename}", $content);

        $user = Auth::user();
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        $project = Project::whereCode($questionnaire->project->code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);

        if ($chunk == $totalChunks - 1) {
            // All chunks received, merge the file
            $gallery = $this->mergeChunks($filename, $totalChunks, $originalName, $questionnaire->project->code, $request, true);
            return redirect()->back()->with('message', 'Upload complete');
        }

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $gallery ?? null]) : Redirect::back();
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
     */
    public function destroy($questionnaire_code, $id, Request $request): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        //dd($questionnaire_code);

        if ($request->type == 'deleteAll') {
            // Delete all images for a project
            $project = Project::whereCode($questionnaire_code)->first();
            
            // Add safety check for project
            if (!$project) {
                return request()->wantsJson() 
                    ? response()->json(['success' => false, 'message' => 'Project not found'], 404)
                    : redirect()->back()->with('error', 'Project not found');
            }
            
            //dd($project->id);
            $Images = Gallery::where('project_id', $project->id)->where('deleted_at', null)->pluck('id');
            foreach ($Images as $image) {
                $gallery = Gallery::find($image);
                if ($gallery) {
                    $gallery->delete();
                }
            }
        } elseif ($request->type == 'deleteSelected') {
            // Delete selected images by IDs
            $selectedIds = $request->input('ids', []);
            
            if (empty($selectedIds)) {
                return request()->wantsJson() 
                    ? response()->json(['success' => false, 'message' => 'No IDs provided'], 400)
                    : redirect()->back()->with('error', 'No IDs provided');
            }
            
            // Find the questionnaire to get the project
            $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
            if (!$questionnaire || !$questionnaire->project) {
                return request()->wantsJson() 
                    ? response()->json(['success' => false, 'message' => 'Questionnaire or project not found'], 404)
                    : redirect()->back()->with('error', 'Questionnaire or project not found');
            }
            
            // Delete selected images
            $deletedCount = 0;
            foreach ($selectedIds as $imageId) {
                $gallery = Gallery::where('id', $imageId)
                    ->where('project_id', $questionnaire->project->id)
                    ->first();
                
                if ($gallery) {
                    $gallery->delete();
                    $deletedCount++;
                }
            }
            
            return request()->wantsJson() 
                ? response()->json(['success' => true, 'message' => "{$deletedCount} items deleted successfully"])
                : redirect()->back()->with('message', "{$deletedCount} items deleted successfully");
                
        } else {
            // Delete single image
            $gallery = Gallery::with('project.parent_survey')->find($id);
            
            // Add safety check for gallery
            if (!$gallery) {
                return request()->wantsJson() 
                    ? response()->json(['success' => false, 'message' => 'Gallery item not found'], 404)
                    : redirect()->back()->with('error', 'Gallery item not found');
            }
            
            // Add safety check for project relationship
            if (!$gallery->project) {
                return request()->wantsJson() 
                    ? response()->json(['success' => false, 'message' => 'Project not found for this gallery item'], 404)
                    : redirect()->back()->with('error', 'Project not found for this gallery item');
            }
            
            Gate::authorize('update', $gallery->project->parent_survey);
            $gallery->delete();
        }

        return request()->wantsJson() ? response()->json(['success' => true]) : Redirect::back();
    }
}
