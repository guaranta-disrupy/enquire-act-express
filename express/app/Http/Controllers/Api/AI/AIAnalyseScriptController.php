<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\GeminiFileAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AIAnalyseScriptController extends Controller
{
    private GeminiFileAnalysisService $fileAnalysisService;

    public function __construct(GeminiFileAnalysisService $fileAnalysisService)
    {
        $this->fileAnalysisService = $fileAnalysisService;
    }

    /**
     * Analyzes uploaded script file and returns EXPRESS parts
     */
    public function analyzeScript(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf|max:10240'
            ]);

            $file = $request->file('file');
            
            $result = $this->fileAnalysisService->analyzeScript($file);
            
            return response()->json([
                'success' => true,
                'parts' => $result['parts'] ?? []
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Script analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze script: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extracts question data for a specific part from the script
     */
    public function getQuestionPartData(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf|max:10240',
                'part' => 'required|string'
            ]);

            $file = $request->file('file');
            $partIdentifier = $request->input('part');
            
            $result = $this->fileAnalysisService->getPartQuestionData($file, $partIdentifier);
            
            return response()->json([
                'success' => true,
                'questions' => $result['questions'] ?? []
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Part question data extraction failed', [
                'part' => $request->input('part'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract question data: ' . $e->getMessage()
            ], 500);
        }
    }
}
