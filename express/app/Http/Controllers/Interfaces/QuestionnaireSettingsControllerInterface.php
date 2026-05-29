<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interfaces;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Response;
use OpenApi\Annotations as OA;

/**
 * Interface QuestionnaireSettingsControllerInterface
 * 
 * @package App\Http\Controllers\Interfaces
 * 
 * @group Questionnaire Settings
 */
interface QuestionnaireSettingsControllerInterface
{
    /**
     * Get questionnaire settings.
     *
     * @param string $questionnaire_code The questionnaire code
     * @return Response|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Get(
     *     path="/survey/{questionnaire_code}/settings",
     *     summary="Get questionnaire settings",
     *     tags={"Questionnaires"},
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
    public function settings(string $questionnaire_code): Response|JsonResponse;

    /**
     * Update questionnaire settings.
     *
     * @param Request $request The request containing settings data
     * @param string $questionnaire_code The questionnaire code
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/updatesettings",
     *     summary="Update questionnaire settings",
     *     tags={"Questionnaires"},
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
     *             required={"settings"},
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 required={"name", "setup"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(
     *                     property="setup",
     *                     type="object",
     *                     @OA\Property(property="design_id", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Settings updated successfully",
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
    public function updateSettings(Request $request, string $questionnaire_code): RedirectResponse|JsonResponse;

    /**
     * Store gallery images.
     *
     * @param Request $request The request containing gallery data
     * @param string $questionnaire_code The questionnaire code
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/gallery/store",
     *     summary="Create a new gallery",
     *     tags={"Questionnaires"},
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"images"},
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     description="Array of image files to upload (required)",
     *                     @OA\Items(
     *                         type="file",
     *                         format="binary"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gallery images uploaded successfully",
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
     *             @OA\Property(property="message", type="string", example="The images field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(type="string", example="The images field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request, string $questionnaire_code): RedirectResponse|JsonResponse;

    /**
     * Update a gallery in questionnaire.
     *
     * @param Request $request The request containing updated questionnaire data
     * @param string $questionnaire_code The questionnaire code
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/gallery/update",
     *     summary="Update a gallery in questionnaire",
     *     tags={"Questionnaires"},
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"changeImage","id"},
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     nullable=true,
     *                     description="ID for image update",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="changeImage",
     *                     type="file",
     *                     description="Image file to upload (required)",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gallery updated successfully",
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
    public function update(Request $request, string $questionnaire_code): RedirectResponse|JsonResponse;

    /**
     * Delete a gallery in questionnaire.
     *
     * @param string $id The gallery id
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Delete(
     *     path="/survey/{questionnaire_code}/gallery/delete/{id}",
     *     summary="Delete a gallery in questionnaire",
     *     tags={"Questionnaires"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Id of the gallery to delete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gallery deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true)
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
     *     )
     * )
     */
    public function destroy(string $id): RedirectResponse|JsonResponse;
}
