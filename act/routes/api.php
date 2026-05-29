<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\EngineController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RecordController;
use App\Http\Controllers\API\SurveyController;
use App\Http\Controllers\API\AnalysisController;
use App\Http\Controllers\SurveyEditorController;
use App\Http\Controllers\API\ProjectController as APIProjectController;
//chatbot
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\AiExecutionController;
use App\Http\Controllers\API\AI\ConversationController;
use App\Http\Controllers\API\AI\UtilityPromptController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::put('/surveys/{survey}/styles', [SurveyEditorController::class, 'updateStyles'])->middleware('auth');

Route::get('/v1/fetch', [EngineController::class, 'getLiveProjectData']);
Route::get('/v2/fetch', [EngineController::class, 'getLiveProjectData2']);

Route::prefix('v1')->group(function() {
    Route::post('/save', [RecordController::class, 'save']);
    Route::get('/goWhere', [EngineController::class, 'goWhere']);
});
Route::middleware('auth:sanctum')->prefix('v1')->group(function() {
    Route::get('/teams', [TeamsController::class, 'teams']);
    Route::get('/projects/{team?}', [ProjectController::class, 'projects']);
    Route::get('/groups/{project}', [GroupController::class, 'groups']);
    Route::get('/record', [RecordController::class, 'getRecord']);
});

Route::prefix('v1')->group(function() {
    Route::get('/record', [RecordController::class, 'getRecord']);
});

Route::prefix('fluent')->middleware('auth:sanctum')->group(function() {
    // Delete project whenever fluent deleted the project

    Route::get('/surveys', [APIProjectController::class, 'surveys']);

    Route::post('/analysis/{survey_id}', [AnalysisController::class, 'start_caching']);
    Route::post('/export', [AnalysisController::class, 'exportData']);
    Route::post('/results', [AnalysisController::class, 'getScores']);
    Route::post('/tabular', [AnalysisController::class, 'computeTabularIQScores']);

});

Route::prefix('fluent2')->middleware('auth:sanctum')->group(function() {
    Route::post('survey/store', [SurveyController::class, 'store']);
    Route::delete('survey/{survey}/destroy', [SurveyController::class, 'destroy']);
    Route::put('survey/{survey}/update', [SurveyController::class, 'update']);
    Route::post('survey/copy', [SurveyController::class, 'copy']);

    # 
    Route::get('survey/{survey}/tests', [SurveyController::class, 'tests']);
});

// Overlay service
Route::middleware('auth:sanctum')->group(function() {
    Route::get('/overlay_pull/{survey}/tests', [SurveyController::class, 'survey']);
    Route::get('/overlay_pull/{project:code}/variables', [SurveyController::class, 'get_variables']);
    Route::get('/overlay_pull/{survey}/survey', [SurveyController::class, 'get_survey_instructions']);
    Route::get('/overlay_pull/{project:code}/project', [SurveyController::class, 'get_project_instructions']);
    Route::post('/overlay_push/{isValidation?}', [SurveyController::class, 'updateFromJson']);
});




Route::post('/request_token', function() {
    $user = \App\Models\User::find(request()->input('id'));
    $tokens = $user->__tokens;
    $tokens['act'] = $user->createToken('fluent-access-token')->plainTextToken;
    $user->update([
        '__tokens' => $tokens
    ]);
    return response()->json(['message' => 'ok'], 200);

})->middleware('auth:sanctum');

Route::get('/{survey}/references', [AnalysisController::class, 'references'])->middleware('auth:sanctum');

Route::post('/generate-token', [AuthController::class, 'generateToken']);

//chatbot
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chat/conversation/create', [ChatController::class, 'createConversation'])->name('api.chat.conversation.create');
    Route::post('/chat/message', [ChatController::class, 'sendMessage'])->name('api.chat.message');
    Route::post('/chat/message/stream', [ChatController::class, 'sendMessageStream'])->name('api.chat.message.stream');
    Route::post('/chat/clear', [ChatController::class, 'clear'])->name('api.chat.clear');
    Route::post('/chat/feedback', [ChatController::class, 'addFeedback'])->name('api.chat.feedback');
    Route::post('/chat/conversation-rating', [ChatController::class, 'addConversationRating'])->name('api.chat.conversation-rating');
    Route::post('/chat/action-result', [ChatController::class, 'updateActionResult'])->name('api.chat.action-result');
    Route::get('/chat/history', [ChatController::class, 'getConversationHistory'])->name('api.chat.history');
    Route::get('/chat/diagnose', [ChatController::class, 'diagnose'])->name('api.chat.diagnose');
    Route::get('/chat/token-usage', [ChatController::class, 'tokenUsage'])->name('api.chat.token-usage');
    Route::get('/chat/conversations/{conversationId}/messages', [ChatController::class, 'getConversationMessages'])->name('api.chat.conversation.messages');
    Route::get('/chat/{conversation}', [ConversationController::class, 'getConversation'])->name('api.chat.get');
    Route::post('/chat/utility-prompt', [UtilityPromptController::class, 'handleUtilityPrompt'])->name('api.chat.utility-prompt');
    Route::post('/chat/pin', [ChatController::class, 'pinConversation'])->name('api.chat.pin');
    Route::post('/chat/rename', [ChatController::class, 'updateConversationTitle'])->name('api.chat.rename');
});
Route::get('/ai/execute/{actionable_id}', [AiExecutionController::class, 'execute'])->name('api.ai.execute');
Route::post('/chat/confirm-action', [ChatController::class, 'confirmAction'])->name('api.chat.confirm-action');
Route::post('/chat/cancel-action', [ChatController::class, 'cancelAction'])->name('api.chat.cancel-action');

//editor routes to allow ai to update styles and test data without refreshing the page
Route::put('/surveys/{survey}/styles', [SurveyEditorController::class, 'updateStyles'])->middleware('auth');
Route::get('/surveys/{survey}/editor-data', [SurveyEditorController::class, 'getEditorData'])->middleware('auth');
Route::get('/surveys/{survey}/tests/{testId}', [SurveyEditorController::class, 'getTestData'])->middleware('auth');
Route::put('/surveys/{survey}/tests/{testId}', [SurveyEditorController::class, 'updateTestData'])->middleware('auth');