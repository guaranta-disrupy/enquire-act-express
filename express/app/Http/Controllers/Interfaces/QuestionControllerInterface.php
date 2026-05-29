<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interfaces;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Response;
use OpenApi\Annotations as OA;

/**
 * Interface QuestionControllerInterface
 * 
 * @package App\Http\Controllers\Interfaces
 * 
 * @group Questions
 */
interface QuestionControllerInterface
{
    /**
     * Display the index page for questions within a questionnaire.
     *
     * @param Request $request The request object
     * @return Response|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Get(
     *     path="/survey/{questionnaire_code}/questions",
     *     summary="Get all questions for a questionnaire",
     *     tags={"Questions"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="questionnaire_code",
     *         in="path",
     *         required=true,
     *         description="Code of the questionnaire",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="language_id",
     *         in="query",
     *         required=false,
     *         description="Language ID for templates",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
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
     *         description="Unauthorized action",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
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
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *         )
     *     )
     * )
     */
    public function index(Request $request): Response|JsonResponse;

    /**
     * Store a new question in the questionnaire.
     *
     * @param Request $request The request containing question data
     * @param string $questionnaire_code The questionnaire code
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/questions/store",
     *     summary="Create a new question",
     *     tags={"Questions"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="questionnaire_code",
     *         in="path",
     *         required=true,
     *         description="Code of the questionnaire",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question"},
     *             @OA\Property(property="question", type="string"),
     *             @OA\Property(property="question_type_id", type="integer"),
     *             @OA\Property(property="setup", type="object"),
     *             @OA\Property(property="choices", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="rows", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="columns", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="items", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="item1", type="string"),
     *             @OA\Property(property="item2", type="string"),
     *             @OA\Property(property="values", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
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
     *         description="Unauthorized action",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
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
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *         )
     *     )
     * )
     */
    public function store(Request $request, string $questionnaire_code): RedirectResponse|JsonResponse;

    /**
     * Update a question's details.
     *
     * @param Request $request The request containing updated question data
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The question ID
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/questions/update/{id}",
     *     summary="Update a question",
     *     tags={"Questions"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="questionnaire_code",
     *         in="path",
     *         required=true,
     *         description="Code of the questionnaire",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Question ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question"},
     *             @OA\Property(property="question", type="string"),
     *             @OA\Property(property="question_type_id", type="integer"),
     *             @OA\Property(property="setup", type="object"),
     *             @OA\Property(property="choices", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="rows", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="columns", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="items", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="item1", type="string"),
     *             @OA\Property(property="item2", type="string"),
     *             @OA\Property(property="values", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
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
     *         description="Unauthorized action",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question not found.")
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
    public function update(Request $request, string $questionnaire_code, int $id): RedirectResponse|JsonResponse;

    /**
     * Delete a question from the questionnaire.
     *
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The question ID
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Delete(
     *     path="/survey/{questionnaire_code}/questions/delete/{id}",
     *     summary="Delete a question",
     *     tags={"Questions"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="questionnaire_code",
     *         in="path",
     *         required=true,
     *         description="Code of the questionnaire",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Question ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
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
     *         description="Unauthorized action",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=419,
     *         description="CSRF token mismatch",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="CSRF token mismatch.")
     *         )
     *     )
     * )
     */
    public function destroy(string $questionnaire_code, int $id): RedirectResponse|JsonResponse;

    /**
     * Move a question to a different position.
     *
     * @param Request $request The request containing the new position
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The question ID
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/questions/move/{id}",
     *     summary="Move a question to a different position",
     *     tags={"Questions"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="questionnaire_code",
     *         in="path",
     *         required=true,
     *         description="Code of the questionnaire",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Question ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="order", type="integer", description="New position for the question")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question moved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question moved successfully")
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
     *         description="Unauthorized action",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question not found.")
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
    //public function move_question(Request $request, string $questionnaire_code, int $id): RedirectResponse|JsonResponse;

    /**
     * Copy a question within the same questionnaire.
     *
     * @param Request $request The request object
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The question ID to copy
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/questions/copyQuestion/{id}",
     *     summary="Copy a question within the same questionnaire",
     *     tags={"Questions"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="questionnaire_code",
     *         in="path",
     *         required=true,
     *         description="Code of the questionnaire",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Question ID to copy",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question copied successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question copied successfully")
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
     *         description="Unauthorized action",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=419,
     *         description="CSRF token mismatch",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="CSRF token mismatch.")
     *         )
     *     )
     * )
     */
    //public function copyQuestion(Request $request, string $questionnaire_code, int $id): RedirectResponse|JsonResponse;
}
