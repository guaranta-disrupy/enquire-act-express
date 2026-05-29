<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interfaces;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Response;
use OpenApi\Annotations as OA;

/**
 * Interface ThemeControllerInterface
 * 
 * @package App\Http\Controllers\Interfaces
 * 
 * @group Themes
 */
interface ThemeControllerInterface
{
    /**
     * Display a listing of themes.
     *
     * @param Request $request The request object
     * @param string $questionnaire_code The questionnaire code
     * @return Response|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Get(
     *     path="/survey/{questionnaire_code}/themes",
     *     summary="Get all themes",
     *     tags={"Themes"},
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
     *     )
     * )
     */
    public function index(Request $request, string $questionnaire_code): Response|JsonResponse;

    /**
     * Store a new theme.
     *
     * @param Request $request The request containing theme data
     * @param string $questionnaire_code The questionnaire code
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/themes/store",
     *     summary="Create a new theme",
     *     tags={"Themes"},
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
     *             required={"name", "setup"},
     *             @OA\Property(property="name", type="string", description="Theme name"),
     *             @OA\Property(
     *                 property="setup",
     *                 type="object",
     *                 @OA\Property(
     *                     property="design",
     *                     type="object",
     *                     description="Theme design configuration"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Theme created successfully",
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
     * Update a theme.
     *
     * @param Request $request The request containing updated theme data
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The theme ID
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/themes/update/{id}",
     *     summary="Update a theme",
     *     tags={"Themes"},
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
     *         description="Theme ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "setup"},
     *             @OA\Property(property="name", type="string", description="Updated theme name"),
     *             @OA\Property(
     *                 property="setup",
     *                 type="object",
     *                 @OA\Property(
     *                     property="design",
     *                     type="object",
     *                     description="Updated theme design configuration"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Theme updated successfully",
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
     *             @OA\Property(property="message", type="string", example="Theme not found.")
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
     * Delete a theme.
     *
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The theme ID
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Delete(
     *     path="/survey/{questionnaire_code}/themes/delete/{id}",
     *     summary="Delete a theme",
     *     tags={"Themes"},
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
     *         description="Theme ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Theme deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Theme deleted successfully")
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
     *             @OA\Property(property="message", type="string", example="Theme not found.")
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
     * Copy a theme.
     *
     * @param Request $request The request containing copy data
     * @param string $questionnaire_code The questionnaire code
     * @param int $id The theme ID to copy
     * @return RedirectResponse|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized
     *
     * @OA\Post(
     *     path="/survey/{questionnaire_code}/themes/copy/{id}",
     *     summary="Copy a theme",
     *     tags={"Themes"},
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
     *         description="Theme ID to copy",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Theme copied successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Theme copied successfully")
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
     *             @OA\Property(property="message", type="string", example="Theme not found.")
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
    public function copyTheme(Request $request, string $questionnaire_code, int $id): RedirectResponse|JsonResponse;
}
