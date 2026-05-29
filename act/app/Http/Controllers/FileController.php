<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function uploadImage(Request $request)
    {
        $path_url = 'storage/' . Auth::id();
        if (!Storage::exists('public/' . Auth::id())) {
            Storage::makeDirectory('public/' . Auth::id());
        }

        if ($request->hasFile('upload')) {
            $fileName = $request->file('upload')->hashName();
            $request->file('upload')->move(storage_path('app/public/' . Auth::id()), $fileName);
            $url = asset($path_url . '/' . $fileName);
        }
        return response()->json(['url' => $url]);
    }
}
