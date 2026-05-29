<?php

namespace App\Http\Controllers\Interfaces;

use OpenApi\Annotations as OA;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

interface EngineControllerInterface
{
    
    /**
     * Get live project data
     * @param Request $request
     * @return JsonResponse
     * 
     * @OA\Get(
     *     path="/api/v2/fetch",
     *     summary="Get live project data with rate limit 100 requests per minute", 
     *     description="Retrieves live project data with rate limit 100 requests per minute by respondent ID or client's IP address",
     *     tags={"Engine"},
     *     @OA\Parameter(
     *         name="survey_id",
     *         in="query",
     *         description="Survey ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="query", 
     *         description="Respondent ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="integer", example=1),
     *             @OA\Property(property="total", type="integer", example=100),
     *             @OA\Property(property="config", type="object"),
     *             @OA\Property(property="surveys", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="variables", type="array", @OA\Items(type="object")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Survey completed or paused",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Survey already completed or paused.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded",
     *         @OA\JsonContent(
     *             type="object", 
     *             @OA\Property(property="message", type="string", example="Too Many Attempts.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error"
     *     )
     * )
     * 
     */

     
    public function getLiveProjectData2(Request $request): JsonResponse;
}