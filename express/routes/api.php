<?php

use App\Http\Controllers\Api\FluentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Api\EngineController;
use App\Http\Controllers\Api\SurveyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuestionnaireController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AiExecutionController;
use App\Http\Controllers\Api\AI\AIStreamController;
use App\Http\Controllers\Api\AI\AIActionsController;
use App\Http\Controllers\Api\AI\AIAnalyseScriptController;
use App\Http\Controllers\Api\HelpbotProxyController;

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

/* Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
 */

/** Engine requests */
Route::apiResource("f8208271af", EngineController::class);
Route::post("/f8208271af/show2", [EngineController::class, "show2"]);

/* Fluent request */
Route::middleware("auth:sanctum")->group(function () {
    Route::get("/user", function (Request $request) {
        return $request->user();
    });
});

Route::get("/fluent/surveys", [FluentController::class, "tests"]);

Route::post("/fluent/respondent_data/{q_code}", [
    FluentController::class,
    "respondent_data",
]);
Route::post("/fluent/analyze/{q_code}", [
    FluentController::class,
    "summary_data",
]);

// Fluent access routes
Route::prefix('fluent2')->middleware('auth:sanctum')->group(function () {
    Route::post('survey/store', [SurveyController::class, 'store']);
    Route::delete('survey/{survey:code}/destroy', [SurveyController::class, 'destroy']);
    Route::put('survey/{survey:code}/update', [SurveyController::class, 'update']);
    Route::post('survey/copy', [SurveyController::class, 'copy']);
});

Route::get('/survey/{survey_code}/q_lists/details', [SurveyController::class, 'q_lists_details']);
Route::get('/survey/{survey_code}/q_lists/reference', [SurveyController::class, 'q_lists_reference']);

// Overlay service routes
Route::middleware('auth:sanctum')->group(function() {
    Route::get('/overlay_pull/{questionnaire:code}/question/{isValidation?}', [QuestionnaireController::class, 'question']);
    Route::get('/overlay_pull/{questionnaire:code}/questionnaire', [QuestionnaireController::class, 'questionnaire']);
    Route::post('/overlay_push/{isValidation?}', [QuestionnaireController::class, 'updateFromJson']);
});


//chatbot
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chat/new', [ChatController::class, 'createConversation'])->name('api.chat.new');
    Route::post('/chat/rename', [ChatController::class, 'updateConversationTitle'])->name('api.chat.rename');
    Route::get('/chat/message', function () {
        return response()->json(['error' => 'Method not allowed. Use POST to send a message.'], 405);
    });
    Route::post('/chat/message', [ChatController::class, 'sendMessage'])->name('api.chat.message');
    Route::post('/chat/message/stream/express', [AIStreamController::class, 'sendMessageStream'])->name('api.chat.message.stream.express');
    Route::post('/chat/clear', [ChatController::class, 'clear'])->name('api.chat.clear');
    Route::post('/chat/feedback', [ChatController::class, 'addFeedback'])->name('api.chat.feedback');
    Route::post('/chat/pin', [ChatController::class, 'pinConversation'])->name('api.chat.pin');
    Route::post('/chat/conversation-rating', [ChatController::class, 'addConversationRating'])->name('api.chat.conversation-rating');
    Route::post('/chat/action-result', [ChatController::class, 'updateActionResult'])->name('api.chat.action-result');
    Route::get('/chat/history', [ChatController::class, 'getConversationHistory'])->name('api.chat.history');
    Route::get('/chat/conversation', [ChatController::class, 'getConversation'])->name('api.chat.conversation');
    Route::post('/chat/append-assistant-message', [ChatController::class, 'appendAssistantMessage'])->name('api.chat.append-assistant-message');
    Route::get('/chat/diagnose', [ChatController::class, 'diagnose'])->name('api.chat.diagnose');
    Route::get('/chat/warmup', [ChatController::class, 'warmupNeo4j'])->name('api.chat.warmup');
    Route::post('/chat/analyzeScript', [AIAnalyseScriptController::class, 'analyzeScript'])->name('api.chat.analyze-script');
    Route::post('/chat/getQuestionPartData', [AIAnalyseScriptController::class, 'getQuestionPartData'])->name('api.chat.get-question-part-data');
    Route::post('/chat/get-questions-for-selection', [AIActionsController::class, 'getQuestionsForSelection'])->name('api.chat.get-questions-for-selection');
    Route::post('/chat/duplicate-question', [AIActionsController::class, 'duplicateQuestion'])->name('api.chat.duplicate-question');
    Route::post('/chat/preview-multiple-questions', [AIActionsController::class, 'previewMultipleQuestions'])->name('api.chat.preview-multiple-questions');
    // Proxy to ai-helpbot stream (Bearer forwarded from client); returns streaming response
     Route::post('/chat/message/stream', [HelpbotProxyController::class, 'proxyStream'])->name('api.chat.message.stream');
});
Route::get('/chat/token-usage', [ChatController::class, 'tokenUsage'])->name('api.chat.token-usage');
Route::get('/ai/execute/{actionable_id}', [AiExecutionController::class, 'execute'])->name('api.ai.execute');
Route::post('/chat/confirm-action', [AIActionsController::class, 'confirmAction'])->name('api.chat.confirm-action');
Route::post('/chat/cancel-action', [AIActionsController::class, 'cancelAction'])->name('api.chat.cancel-action');

Route::get('test', function () {
    return response()->json([
        'ok' => true,
        'message' => 'Test route works',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('test');

// Route::post('/generate-token', [AuthController::class, 'generateToken']);
