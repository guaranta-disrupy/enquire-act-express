<?php

namespace App\Http\Controllers;

use App\Models\Exportable;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class ExportableController extends Controller
{
    public function download(Exportable $exportable)
    {
        return response()->download(storage_path("app/exportables/{$exportable->survey_id}/{$exportable->source}"), $exportable->file);
    }

    public function destroy(Exportable $exportable)
    {
        if (Storage::exists("exportables/{$exportable->survey_id}/{$exportable->source}")) {
            Storage::delete("exportables/{$exportable->survey_id}/{$exportable->source}");
        }
        $exportable->delete();
        return back();
    }

    public function destroyAll(Survey $survey)
    {
        //$survey->exportables()->delete();
        foreach (generator($survey->exportables) as $exportable) {
            // delete the exported file from the storage
            if (Storage::exists("exportables/{$exportable->survey_id}/{$exportable->source}")) {
                Storage::delete("exportables/{$exportable->survey_id}/{$exportable->source}");
            }

            // delete the row from the database
            $exportable->delete();
        }
        return Redirect::back();
    }

    
}
