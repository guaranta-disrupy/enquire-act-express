
<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\VariableController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\LiveEditorController;
use Inertia\Inertia;
use App\Models\Record;
use App\Http\Controllers\EngineController;
use App\Http\Controllers\SurveyEditorController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GalleryProxyController;
use App\Http\Controllers\Survey2Controller;
use App\Http\Controllers\TemplateController;
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

Route::middleware(['auth_fluent', 'verified', 'subscription'])->group(function() {

    // Route::controller(LiveEditorController::class)->group(function () {
    //     Route::get('/live', 'index')->name('live');
    // });

    // Route::post('/upload', [FileController::class, 'uploadImage'])->name('images.upload');
    
    // Route::controller(LiveEditorController::class)->group(function () {
    //     Route::get('/live/{testid}', 'index')->name('live/{testid}');
    //     Route::get('/live-iframe/{testid}', 'iframe')->name('live.iframe');
    //     Route::get('/TestEditor/{testid}', 'TestEditor')->name('/TestEditor/{testid}');
    //     Route::post('/saveTest', 'saveTest')->name('saveTest');
    // });

    Route::prefix('variables/{project}')->name('variables.')->group(function() {
        Route::get('/', [VariableController::class, 'index'])->name('index');
        Route::post('/store', [VariableController::class, 'store'])->name('store');
        Route::put('/{variable}/update', [VariableController::class, 'update'])->name('update');
        Route::get('/{variable}/copy', [VariableController::class, 'copy'])->name('copy');
        Route::delete('/{variable}/destroy', [VariableController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('variables2/{project}')->name('variables2.')->group(function() {
        Route::get('/{survey}', [\App\Http\Controllers\Variable2Controller::class, 'index'])->name('index');
        Route::post('/store', [\App\Http\Controllers\Variable2Controller::class, 'store'])->name('store');
        Route::put('/{variable}/update', [\App\Http\Controllers\Variable2Controller::class, 'update'])->name('update');
        Route::get('/{variable}/copy', [\App\Http\Controllers\Variable2Controller::class, 'copy'])->name('copy');
        Route::delete('/{variable}/destroy', [\App\Http\Controllers\Variable2Controller::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tests/{survey}')->name('tests.')->group(function() {
        Route::get('/', [TestController::class, 'index'])->name('index');
        Route::get('/create', [TestController::class, 'create'])->name('create');
        Route::post('/store', [TestController::class, 'store'])->name('store');
        Route::get('/edit/{test}', [TestController::class, 'edit'])->name('edit');
        Route::put('/update/{test}', [TestController::class, 'update'])->name('update');
        Route::delete('/destroy/{test}', [TestController::class, 'destroy'])->name('destroy');
        Route::get('/copy/{test}', [TestController::class, 'copy'])->name('copy');
        Route::get('/settings', [TestController::class, 'settings'])->name('settings');
        Route::put('/settings', [TestController::class, 'saveSettings'])->name('saveSettings');
        Route::get('/{test}/{part}', [TestController::class, 'move'])->name('move');
    });

    Route::get('/surveys/{survey}/editor', [SurveyEditorController::class, 'show'])->name('surveys.editor');
    Route::get('/surveys/{survey}/preview', [SurveyEditorController::class, 'preview'])->name('surveys.preview');
});

Route::prefix('templates/{team}/{project}/{survey:id}')->middleware(['auth_fluent', 'verified', 'subscription'])->name('templates.')->group(function() {
    Route::get('/', [TemplateController::class, 'index'])->name('index');
    Route::post('/primes', [TemplateController::class, 'storePrime'])->name('primes.store');
    Route::get('/primes/{templatePrime}', [TemplateController::class, 'showPrime'])->name('primes.show');
    Route::put('/primes/{templatePrime}', [TemplateController::class, 'updatePrime'])->name('primes.update');
    Route::delete('/primes/{templatePrime}', [TemplateController::class, 'destroyPrime'])->name('primes.destroy');
    Route::post('/primes/{templatePrime}/copy', [TemplateController::class, 'copyPrime'])->name('primes.copy');

    Route::post('/targets', [TemplateController::class, 'storeTarget'])->name('targets.store');
    Route::get('/targets/{templateTarget}', [TemplateController::class, 'showTarget'])->name('targets.show');
    Route::put('/targets/{templateTarget}', [TemplateController::class, 'updateTarget'])->name('targets.update');
    Route::delete('/targets/{templateTarget}', [TemplateController::class, 'destroyTarget'])->name('targets.destroy');
    Route::post('/targets/{templateTarget}/copy', [TemplateController::class, 'copyTarget'])->name('targets.copy');
});

// Gallery proxy route - outside middleware groups to avoid Inertia headers
Route::get('/gallery-proxy', [GalleryProxyController::class, 'show'])
    ->middleware(['throttle:300,1'])
    ->name('gallery.proxy');

Route::get('/launch', function(Request $request) {
    $data = $request->all();
    $data['id'] = $data['id'] ?? Str::random(10);
    if (!isset($data['code']) && !isset($data['survey_id'])) {
        return 'Invalid link!';
    }
    return Redirect::to('https://ast.splitsecondsurveys.co.uk/ast-engine/?' . http_build_query($data));
});

Route::get('/launch2', function(Request $request) {
    $data = $request->all();
    $data['id'] = $data['id'] ?? Str::random(10);
    if (!isset($data['code']) && !isset($data['survey_id'])) {
        return 'Invalid link!';
    }
    return redirect(env('APP_ENGINE') .'?' . http_build_query($data));
});

Route::middleware(['auth_fluent'])->group(function() {
    Route::get('/survey/{survey}', [Survey2Controller::class, 'index'])->name('survey2.index');
    Route::get('/survey/{survey}/editor/create', [Survey2Controller::class, 'create'])->name('survey2.create');
    Route::get('/survey/{survey}/editor/{test}/update', [Survey2Controller::class, 'update'])->name('survey2.update');
    Route::post('saveTest', [Survey2Controller::class, 'saveTest'])->name('survey2.saveTest');
    Route::put('/survey/config/save/{survey}/{project}', [Survey2Controller::class, 'save_settings'])->name('survey2.save_settings');

    Route::get('/survey/{survey}/templates', [Survey2Controller::class, 'templates'])->name('survey2.templates');
    Route::post('/survey/{survey}/templates/save', [Survey2Controller::class, 'saveTemplates'])->name('survey2.templates.save');
});

// Back to fluent redirect with progress
// Route::get('/fluent/survey/{survey_id}', function($survey_id) {
//     $fluentUrl = env('APP_FLUENT_URL') . '/surveys/' . $survey_id . '/components';
//     return Inertia::render('ExternalRedirect', [
//         'redirectUrl' => $fluentUrl,
//         'redirectText' => 'Redirecting to Connoisseur',
//         'useDynamicLoading' => true
//     ]);
// })->name('redirect.fluent');
Route::get('/fluent/survey/{survey_id}', function($survey_id) {
    return redirect(env('APP_FLUENT_URL') . '/surveys/' . $survey_id . '/components');
})->name('redirect.fluent');

// Survey test page
Route::get('/survey-test', [EngineController::class, 'renderSurvey']);