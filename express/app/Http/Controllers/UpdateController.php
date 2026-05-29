<?php

namespace App\Http\Controllers;

use App\Models\Export;
use App\Models\Filter;
use App\Models\Gallery;
use App\Models\Project;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    public function update()
    {
        $teams = [
            11 => 1,
            4 => 5,
            5 => 7,
            3 => 8,
            2 => 9,
            12 => 10,
            21 => 12,
            50 => 13,
            25 => 15,
            8 => 16,
            13 => 18,
            20 => 25,
            29 => 28,
            16 => 29,
            49 => 30,
            15 => 31,
            26 => 32,
            45 => 33,
            24 => 34,
            7 => 35,
            51 => 37,
            34 => 41,
            39 => 42,
            40 => 44,
            44 => 45,
            47 => 46,
            48 => 47,
            1 => 3,
            14 => 27,
            32 => 36
        ];
        $users = [
            8 => 1,
            1 => 2,
            6 => 3,
            4 => 5,
            5 => 7,
            3 => 8,
            2 => 9,
            10 => 10,
            20 => 12,
            9 => 13,
            7 => 14,
            11 => 17,
            15 => 18,
            13 => 17,
            12 => 20,
            18 => 21,
            16 => 22,
            19 => 23,
            17 => 24
        ];
        DB::beginTransaction();
        $projects = Project::withTrashed()->get();
        foreach ($projects as $project) {
            if (isset($teams[$project->team_id]) && isset($users[$project->user_id])) {
                $project->update([
                    'team_id' => $teams[$project->team_id],
                    'user_id' => $users[$project->user_id]
                ]);
            }

        }
        $exports = Export::all();
        foreach ($exports as $export) {
            if (isset($users[$export->user_id])) {
                $export->update([
                    'user_id' => $users[$export->user_id]
                ]);
            }
        }
        $filters = Filter::withTrashed()->get();
        foreach ($filters as $filter) {
            if (isset($users[$filter->user_id])) {
                $filter->update([
                    'user_id' => $users[$filter->user_id]
                ]);
            }
        }
        $galleries = Gallery::withTrashed()->get();
        foreach ($galleries as $gallery) {
            if (isset($users[$gallery->user_id])) {
                $gallery->update([
                    'user_id' => $users[$gallery->user_id]
                ]);
            }
        }
        $questionnaires = Questionnaire::withTrashed()->get();
        foreach ($questionnaires as $questionnaire) {
            if (isset($users[$questionnaire->user_id])) {
                $questionnaire->update([
                    'user_id' => $users[$questionnaire->user_id]
                ]);
            }
        }
        DB::commit();
        return 'done';
    }
}
