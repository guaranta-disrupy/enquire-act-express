<?php

use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\Question_TemplateController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\AutoTranslateQuestionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ManageTranslationController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return 'Invalid page';
});

Route::middleware(["auth_fluent"])->group(function () {
    Route::get("update", [
        \App\Http\Controllers\UpdateController::class,
        "update",
    ]);

    Route::prefix("survey/{questionnaire_code}")->group(function () {
        Route::get("/settings", [
            QuestionnaireController::class,
            "settings",
        ])->name("questionnaires.settings");
        Route::post("/updatesettings", [
            QuestionnaireController::class,
            "updateSettings",
        ])->name("questionnaireSettings.update");
        Route::post("/updateadvanced", [
            QuestionnaireController::class,
            "updateAdvanced",
        ])->name("questionnaireAdvanced.update");
        Route::post("/copyQnr", [
            QuestionnaireController::class,
            "copyQuestionnaire",
        ])->name("questionnaires.copy");

        Route::prefix("questions")->group(function () {
            Route::get("/", [QuestionController::class, "index"])->name(
                "questions.index"
            );
            Route::post("/store", [QuestionController::class, "store"])->name(
                "questions.store"
            );
            Route::post("/update/{id}", [
                QuestionController::class,
                "update",
            ])->name("questions.update");
            Route::delete("/delete/{id}", [
                QuestionController::class,
                "destroy",
            ])->name("questions.destroy");
            Route::post("/move/{id}", [
                QuestionController::class,
                "move_question",
            ])->name("questions.move");
            Route::post("/updatelogic/{id}", [
                QuestionController::class,
                "updateLogic",
            ])->name("questions.logic");
            Route::patch("/restore/{id}", [
                QuestionController::class,
                "restoreQuestion",
            ])->name("question.restore");
            Route::post("/copyQuestion/{id}", [
                QuestionController::class,
                "copyQuestion",
            ])->name("question.copy");
            Route::post("/usetemplate", [
                QuestionController::class,
                "usetemplate",
            ])->name("questions.usetemplate");
            Route::post("/auto-translate/{question_id}", [
                AutoTranslateQuestionController::class,
                "translateNewQuestion",
            ])->name("questions.autoTranslate");
            Route::get("/detect-untranslated", [
                AutoTranslateQuestionController::class,
                "detectUntranslatedQuestions",
            ])->name("questions.detectUntranslated");
            Route::post("/translate-multiple", [
                AutoTranslateQuestionController::class,
                "translateMultipleQuestions",
            ])->name("questions.translateMultiple");
        });

        Route::prefix("templates")->group(function () {
            Route::get("/", [
                Question_TemplateController::class,
                "index",
            ])->name("templates.index");
            Route::post("/store", [
                Question_TemplateController::class,
                "store",
            ])->name("templates.store");
            Route::post("/update/{id}", [
                Question_TemplateController::class,
                "update",
            ])->name("templates.update");
            Route::delete("/delete/{id}", [
                Question_TemplateController::class,
                "destroy",
            ])->name("templates.destroy");
            Route::post("/copy/{id}", [
                Question_TemplateController::class,
                "copyTemplate",
            ])->name("templates.copy");
        });

        Route::prefix("gallery")
            ->name("gallery.")
            ->group(function () {
                Route::post("/store", [
                    GalleryController::class,
                    "store",
                ])->name("store");
                Route::post("/update", [
                    GalleryController::class,
                    "update",
                ])->name("update");
                Route::delete("/delete/{id}", [
                    GalleryController::class,
                    "destroy",
                ])->name("destroy");
            });

        Route::prefix("themes")->group(function () {
            Route::get("/", [ThemeController::class, "index"])->name(
                "themes.index"
            );
            Route::post("/store", [ThemeController::class, "store"])->name(
                "themes.store"
            );
            Route::post("/update/{id}", [ThemeController::class, "update"])->name(
                "themes.update"
            );
            Route::delete("/delete/{id}", [
                ThemeController::class,
                "destroy",
            ])->name("themes.destroy");
            Route::post("/copy/{id}", [
                ThemeController::class,
                "copyTheme",
            ])->name("themes.copy");
            Route::post("/updatethemesettings", [
                ThemeController::class,
                "applyTheme",
            ])->name("apply.theme");
        });
    });

    Route::get("/download-questionnaire/{questionnaire_code}", [ManageTranslationController::class, "downloadQuestionnaire"])->name("download.questionnaire");
    Route::put("/upload-translation-csv/{code}", [ManageTranslationController::class, "uploadTranslationCSV"])->name("upload.translation.csv");
    Route::post("/ai-translate/{code}", [ManageTranslationController::class, "aiTranslate"])->name("ai.translate");
    Route::post("/ai-translate-question/{code}", [ManageTranslationController::class, "aiTranslateQuestion"])->name("ai.translate.question");
    Route::post("/ai-translate-single-question/{code}", [ManageTranslationController::class, "aiTranslateSingleQuestion"])->name("ai.translate.question.single");
    Route::post("/ai-translate-system-messages/{code}", [ManageTranslationController::class, "aiTranslateSystemMessages"])->name("ai.translate.systemMessages");
    Route::post("/cleanup-system-messages/{code}", [ManageTranslationController::class, "cleanupSystemMessages"])->name("cleanup.systemMessages");
    Route::delete("/delete-language/{code}", [ManageTranslationController::class, "deleteLanguage"])->name("delete.language");
});

// Back to fluent redirect
Route::get("/fluent/survey/{survey_id}", function ($survey_id) {
    return \redirect(env("APP_FLUENT_URL") . "/surveys/" . $survey_id . "/components");
})->name("redirect.fluent");

Route::get("/image", function (Request $request) {
    $originalPath = $request->query('path');
    
    // Return 404 if path is empty
    if (empty($originalPath)) {
        abort(404, 'Image path is required');
    }
    
    // Decode the URL-encoded path
    $pathToImage = urldecode($originalPath);
    $originalUrl = $pathToImage; // Keep original for potential fallback
    
    // If the path is a full URL, extract just the path part
    if (filter_var($pathToImage, FILTER_VALIDATE_URL)) {
        $parsedUrl = parse_url($pathToImage);
        // Get the path from the parsed URL, fallback to the original if path is missing
        $pathToImage = isset($parsedUrl['path']) && !empty($parsedUrl['path']) 
            ? $parsedUrl['path'] 
            : $pathToImage;
    }
    
    // Ensure path starts with / for consistency (but not if it's still a full URL)
    if (!empty($pathToImage) && !filter_var($pathToImage, FILTER_VALIDATE_URL) && !str_starts_with($pathToImage, '/')) {
        $pathToImage = '/' . $pathToImage;
    }
    
    // Normalize old URL patterns: /ide/gallery/ -> /gallery/
    // This handles legacy paths from pre-defined themes (e.g., https://express.splitsecondsurveys.co.uk/ide/gallery/filename.png)
    if (str_starts_with($pathToImage, '/ide/gallery/')) {
        $pathToImage = '/gallery/' . substr($pathToImage, strlen('/ide/gallery/'));
    }
    
    // Handle gallery paths (e.g., /gallery/filename.png or /gallery/userId/filename.png)
    if (str_starts_with($pathToImage, '/gallery/')) {
        $pathToImage = public_path($pathToImage);
    }
    // Convert public storage URL path to actual filesystem path
    // e.g., /storage/targets_images/file.png -> storage/app/public/targets_images/file.png
    elseif (str_starts_with($pathToImage, '/storage/')) {
        $pathToImage = storage_path('app/public/' . substr($pathToImage, strlen('/storage/')));
    } elseif (str_starts_with($pathToImage, 'storage/')) {
        $pathToImage = storage_path('app/public/' . substr($pathToImage, strlen('storage/')));
    } else {
        $pathToImage = public_path($pathToImage);
    }
    
    // Verify the file exists before serving
    if (!file_exists($pathToImage)) {
        // If original was a full URL and local file doesn't exist, redirect to original URL
        // This handles cases where old images are still hosted on the old domain
        if (filter_var($originalUrl, FILTER_VALIDATE_URL)) {
            return redirect($originalUrl, 302);
        }
        
        // Log for debugging
        \Log::warning('Image not found', [
            'original_path' => $originalPath,
            'decoded_path' => urldecode($originalPath),
            'resolved_path' => $pathToImage
        ]);
        abort(404, 'Image not found');
    }
    
    return response()->file($pathToImage, [
        'Cache-Control' => 'public, max-age=' . (60 * 60 * 24 * 365), // 1 year
        'Expires' => gmdate('D, d M Y H:i:s', time() + (60 * 60 * 24 * 365)) . ' GMT',
        'ETag' => md5_file($pathToImage), // Add ETag for better caching
    ]);
})->name("image.cache");

Route::get("/video", function (Request $request) {
    $pathToVideo = $request->query('path');
    
    // Decode the URL-encoded path
    $pathToVideo = urldecode($pathToVideo);
    
    // If the path is a URL, extract just the path part
    if (filter_var($pathToVideo, FILTER_VALIDATE_URL)) {
        $pathToVideo = parse_url($pathToVideo, PHP_URL_PATH);
    }
    
    // Handle gallery paths (e.g., /gallery/userId/filename.mp4)
    if (str_starts_with($pathToVideo, '/gallery/')) {
        $pathToVideo = public_path($pathToVideo);
    }
    // Convert public storage URL path to actual filesystem path
    // e.g., /storage/videos/file.mp4 -> storage/app/public/videos/file.mp4
    elseif (str_starts_with($pathToVideo, '/storage/')) {
        $pathToVideo = storage_path('app/public/' . substr($pathToVideo, strlen('/storage/')));
    } elseif (str_starts_with($pathToVideo, 'storage/')) {
        $pathToVideo = storage_path('app/public/' . substr($pathToVideo, strlen('storage/')));
    } else {
        $pathToVideo = public_path($pathToVideo);
    }
    
    // Verify the file exists before serving
    if (!file_exists($pathToVideo)) {
        abort(404, 'Video not found');
    }
    
    // Determine MIME type based on file extension
    $extension = strtolower(pathinfo($pathToVideo, PATHINFO_EXTENSION));
    $mimeTypes = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        '3gp' => 'video/3gpp',
    ];
    $mimeType = $mimeTypes[$extension] ?? 'video/mp4';
    
    // Support range requests for video streaming (required for HTML5 video)
    $fileSize = filesize($pathToVideo);
    $range = $request->header('Range');
    
    if ($range) {
        // Parse range header
        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
        $start = intval($matches[1] ?? 0);
        $end = $matches[2] ? intval($matches[2]) : $fileSize - 1;
        $length = $end - $start + 1;
        
        // Validate range
        if ($start > $end || $start < 0 || $end >= $fileSize) {
            return response('Range Not Satisfiable', 416, [
                'Content-Range' => "bytes */{$fileSize}",
            ]);
        }
        
        // Return partial content
        $file = fopen($pathToVideo, 'rb');
        fseek($file, $start);
        $data = fread($file, $length);
        fclose($file);
        
        return response($data, 206, [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=' . (60 * 60 * 24 * 365), // 1 year
            'Expires' => gmdate('D, d M Y H:i:s', time() + (60 * 60 * 24 * 365)) . ' GMT',
            'ETag' => md5_file($pathToVideo),
        ]);
    }
    
    // Return full file with caching headers
    return response()->file($pathToVideo, [
        'Content-Type' => $mimeType,
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'public, max-age=' . (60 * 60 * 24 * 365), // 1 year
        'Expires' => gmdate('D, d M Y H:i:s', time() + (60 * 60 * 24 * 365)) . ' GMT',
        'ETag' => md5_file($pathToVideo), // Add ETag for better caching
    ]);
})->name("video.cache");