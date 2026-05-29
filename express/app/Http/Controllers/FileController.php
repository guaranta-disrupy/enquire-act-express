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
        $path_url = 'gallery/' . Auth::id();
        if (!Storage::exists('gallery/' . Auth::id())) {
            Storage::makeDirectory('gallery/' . Auth::id());
        }
        

        if ($request->hasFile('upload')) {
            $fileName = $request->file('upload')->hashName();
            $request->file('upload')->move(public_path($path_url), $fileName);
            $url = asset($path_url . '/' . $fileName);
        }
        return response()->json(['url' => $url]);
    }
}
