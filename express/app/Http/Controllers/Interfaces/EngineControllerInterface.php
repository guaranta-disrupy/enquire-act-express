<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interfaces;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * Interface EngineControllerInterface
 * 
 * @package App\Http\Controllers\Interfaces
 * 
 * @group Engine
 */
interface EngineControllerInterface
{
    /**
     * Retrieves live project data with rate limit 200 requests per minute by respondent ID or client's IP address.
     *
     * @param Request $request The request object
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/api/f8208271af/show2",
     *     summary="Get questionnaire data for engine",
     *     tags={"Engine"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"questionnaire_code", "participant_id"},
     *             @OA\Property(property="questionnaire_code", type="string"),
     *             @OA\Property(property="participant_id", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="questionnaire",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="code", type="string"),
     *                     @OA\Property(property="setup", type="object"),
     *                     @OA\Property(property="theme", type="object")
     *                 ),
     *                 @OA\Property(
     *                     property="questions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="question", type="string"),
     *                         @OA\Property(property="question_type_id", type="integer"),
     *                         @OA\Property(property="setup", type="object"),
     *                         @OA\Property(property="order", type="integer")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated action",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden action",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Survey already completed or paused.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Questionnaire not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=419,
     *         description="CSRF token mismatch",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="CSRF token mismatch.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     )
     * )
     */
    public function show2(Request $request): JsonResponse;
}
