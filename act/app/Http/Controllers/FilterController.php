<?php

namespace App\Http\Controllers;

use App\Models\Filter;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Str;

class FilterController extends Controller
{
    public function index(Project $project)
    {
        return Inertia::render('Filters/Filter', [
            'project' => $project,
            'projects' => projectsList(),
            'filters' => $project->filters()->with('user:id,name')->get()
        ]);
    }

    public function create(Project $project)
    {
        return Inertia::render('Filters/Create', [
            'project' => $project
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required',
            'src' => 'nullable'
        ]);
        $ids = $this->formatIds($request->ids);
        $filters = preg_split("/\\r\\n|\\r|\\n/", $request->name);
        $srcs = preg_split("/\\r\\n|\\r|\\n/", $request->src);
        foreach (generator($filters) as $key => $value) {
            if (isset($srcs[$key]) && !empty($srcs[$key])) {
                $sources = explode(",", $srcs[$key]);
                $ids = [];
                foreach (generator($sources) as $source) {
                    $response = Http::get($source);
                    $ids[] = trim($response->body());
                }
                $ids = implode(",", $ids);
            }
            $project->filters()->create([
                'name' => trim($value),
                'src' => $srcs[$key] ?? null,
                'description' => strip_tags($request->description),
                'user_id' => $request->user()->id,
                'data' => [
                    'operator' => $request->operator,
                    'ids' => $ids
                ]
            ]);
        }
        return back();
    }

    private function formatIds($ids)
    {
        $ids = explode(Str::contains($ids, ",") ? ',' : "\n", trim($ids));
        $ids = collect($ids)->map(function ($id) {
            return trim($id);
        })->toArray();
        return implode(",", $ids);
    }

    public function destroy(Project $project, Filter $filter)
    {
        $filter->delete();
        return back();
    }

    public function edit(Project $project, Filter $filter)
    {
        $next = Filter::where('project_id', $project->id)->where('id', '>', $filter->id)->first();
        $previous = Filter::where('project_id', $project->id)->where('id', '<', $filter->id)->orderBy('id', 'desc')->first();
        $navigator = [
            'hasNext' => !is_null($next),
            'hasPrevious' => !is_null($previous),
            'next' => !is_null($next) ? route('filters.edit', [$project, $next]) : null,
            'previous' => !is_null($previous) ? route('filters.edit', [$project, $previous]) : null,
        ];
        return Inertia::render('Filters/Update', [
            'project' => $project,
            'filter' => $filter,
            'navigator' => $navigator
        ]);
    }

    public function update(Request $request, Project $project, Filter $filter)
    {
        $request->validateWithBag('updateFilter', [
            'name' => 'required|max:255',
            'src' => 'nullable'
        ]);
        $ids = $this->formatIds($request->ids);
        if (!is_null($request->src)) {
            $sources = explode2($request->src);
            $ids = [];
            foreach (generator($sources) as $source) {
                $response = Http::get($source);
                $ids[] = trim($response->body());
            }
            $ids = implode(",", $ids);
        }
        $data = [
            'operator' => $request->operator,
            'ids' => $ids
        ];
        $filter->update([
            'name' => trim(strip_tags($request->name)),
            'description' => strip_tags($request->description),
            'data' => $data,
            'src' => $request->src
        ]);
        //return Redirect::route('filters.index', $project);
        return back();
    }

    public function sync(Project $project, Filter $filter, $all = false)
    {
        $ids = $filter->data['ids'];
        if ($filter->src) {
            $sources = explode2($filter->src);
            $ids = [];
            foreach (generator($sources) as $source) {
                $response = Http::get($source);
                $ids[] = trim($response->body());
            }
            $ids = implode(",", $ids);
            $filter->update([
                'data' => [
                    'operator' => $filter->data['operator'],
                    'ids' => $ids
                ]
            ]);
        }
        return $all ? true : Redirect::route('filters.index', $project);
    }

    public function syncAll(Project $project)
    {
        $filters = $project->filters;
        foreach (generator($filters) as $filter) {
            $this->sync($project, $filter, true);
        }
        return Redirect::route('filters.index', $project);
    }

    public function deleteAll(Project $project)
    {
        $filters = $project->filters;
        foreach (generator($filters) as $filter) {
            $filter->delete();
        }
        return Redirect::route('filters.index', $project);
    }
}
