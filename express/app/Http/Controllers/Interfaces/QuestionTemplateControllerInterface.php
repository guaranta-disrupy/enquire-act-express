<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interfaces;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Response;
use OpenApi\Annotations as OA;

/**
 * Interface QuestionTemplateControllerInterface
 * 
 * @package App\Http\Controllers\Interfaces
 * 
 * @group Question Templates
 */
interface QuestionTemplateControllerInterface
{
    /**
     * Display a listing of question templates.
     *
     * @param Request $request The request object
     * @param string $questionnaire_code The questionnaire code
     * @return Response|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Get(
     *     path="/survey/{questionnaire_code}/templates",
     *     summary="Get all question templates",
     *     tags={"Question Templates"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="questionnaire_code",
     *         in="path",
     *         required=true,
     *         description="Code of the questionnaire",
     *         @OA\Schema(type="string")
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
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     )
     * )
     */
    public function index(Request $request, string $questionnaire_code): Response|JsonResponse;

    /**
     * Store a new question template.
     *
     * @param Request $request The request containing template data
     * @param string $questionnaire_code The questionnaire code
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/templates/store",
     *     summary="Create a new question template",
     *     tags={"Question Templates"},
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
     *             required={"question", "question_type_id"},
     *             @OA\Property(property="question", type="string", description="Template question text"),
     *             @OA\Property(property="question_type_id", type="integer", description="Question type ID"),
     *             @OA\Property(property="setup", type="object", description="Template setup configuration"),
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
     *         description="Template created successfully",
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
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     )
     * )
     */
    public function store(Request $request, string $questionnaire_code): RedirectResponse|JsonResponse;

    /**
     * Update a question template.
     *
     * @param Request $request The request containing updated template data
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The template ID
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/templates/update/{id}",
     *     summary="Update a question template",
     *     tags={"Question Templates"},
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
     *         description="Template ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question", "question_type_id"},
     *             @OA\Property(property="question", type="string", description="Updated template question text"),
     *             @OA\Property(property="question_type_id", type="integer", description="Updated question type ID"),
     *             @OA\Property(property="setup", type="object", description="Updated template setup configuration"),
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
     *         description="Template updated successfully",
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
     *             @OA\Property(property="message", type="string", example="Template not found.")
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
     * Copy a question template.
     *
     * @param Request $request The request containing copy data
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The template ID to copy
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/templates/copy/{id}",
     *     summary="Copy a question template",
     *     tags={"Question Templates"},
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
     *         description="Template ID to copy",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template copied successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Template copied successfully")
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
     *             @OA\Property(property="message", type="string", example="Template not found.")
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
    public function copyTemplate(Request $request, string $questionnaire_code, int $id): RedirectResponse|JsonResponse;

    /**
     * Delete a question template.
     *
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The template ID
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Delete(
     *     path="/survey/{questionnaire_code}/templates/delete/{id}",
     *     summary="Delete a question template",
     *     tags={"Question Templates"},
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
     *         description="Template ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Template deleted successfully")
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
     *             @OA\Property(property="message", type="string", example="Template not found.")
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
}
