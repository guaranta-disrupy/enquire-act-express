<?php

namespace App\Http\Controllers\Interfaces;

use App\Models\Survey;
use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Project;
use OpenApi\Annotations as OA;

interface TestControllerInterface
{
    /**
     * Get all tests for a survey
     * 
     * Retrieves all tests associated with the given survey. Only authorized users can access this endpoint.
     * The tests are returned in a paginated format with test details including name, type, settings and other metadata.
     * 
     * @param Survey $survey
     * @return JsonResponse|Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * 
     * @OA\Get(
     *     path="/tests/{survey}",
     *     tags={"Tests"},
     *     summary="Get all tests for a survey",
     *     description="Retrieves all tests associated with the given survey. Only authorized users can access this endpoint.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="survey",
     *         in="path",
     *         required=true,
     *         description="Survey ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Survey not found"),
     *     @OA\Response(response=403, description="Unauthorized access")
     * )
     */
    public function index(Survey $survey): JsonResponse | Response;

    /**
     * Create a new test for a survey
     * 
     * @param Survey $survey
     * @return JsonResponse|Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * 
     * @OA\Post(
     *     path="/tests/{survey}/store",
     *     tags={"Tests"},
     *     summary="Create a new test for a survey",
     *     description="Creates a new test for the given survey. Only authorized users can access this endpoint.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="survey",
     *         in="path",
     *         required=true,
     *         description="Survey ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"config", "primes", "targets"},
     *             @OA\Property(
     *                 property="config",
     *                 type="object",
     *                 required={
     *                     "title", "main_trial", "random", "prime_header", "main_header",
     *                     "empty_selection", "main_header_timeout", "type", "single_select",
     *                     "limit", "auto_next", "display_by", "random_prime",
     *                     "random_prime_subsets", "prime_as_next", "bottom_text",
     *                     "conditions", "macros"
     *                 },
     *                 @OA\Property(property="title", type="string", nullable=true),
     *                 @OA\Property(property="main_trial", type="boolean"),
     *                 @OA\Property(property="random", type="boolean"),
     *                 @OA\Property(property="prime_header", type="string", nullable=true),
     *                 @OA\Property(property="main_header", type="string"),
     *                 @OA\Property(property="empty_selection", type="string"),
     *                 @OA\Property(property="main_header_timeout", type="integer"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="single_select", type="boolean"),
     *                 @OA\Property(property="limit", type="integer", example=1),
     *                 @OA\Property(property="auto_next", type="boolean", example=true),
     *                 @OA\Property(property="display_by", type="integer", example=1),
     *                 @OA\Property(property="random_prime", type="boolean", example=true),
     *                 @OA\Property(property="random_prime_subsets", type="boolean", example=true),
     *                 @OA\Property(property="prime_as_next", type="boolean", example=true),
     *                 @OA\Property(property="bottom_text", type="string", example="Bottom text"),
     *                 @OA\Property(property="conditions", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="macros", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(
     *                 property="primes",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="prime", type="string", example="Prime 1"),
     *                     @OA\Property(property="equivalent", type="string", example="Equivalent 1"),
     *                     @OA\Property(property="index", type="integer", example=1),
     *                     @OA\Property(
     *                         property="config",
     *                         type="object",
     *                         @OA\Property(property="conditions", type="object")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="targets",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="target", type="string", example="Target 1"),
     *                     @OA\Property(property="equivalent", type="string", example="Equivalent 1"), 
     *                     @OA\Property(property="index", type="integer", example=1),
     *                     @OA\Property(property="can_select", type="boolean", example=true),
     *                     @OA\Property(                                                                                                                                                                                                    
     *                         property="colors",
     *                         type="object",
     *                         @OA\Property(property="normal", type="string"),
     *                         @OA\Property(property="hover", type="string"),
     *                         @OA\Property(property="active", type="string")
     *                     ),
     *                     @OA\Property(property="value", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="tests",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Unauthorized access")
     * )
     */
    public function store(Request $request, Survey $survey): JsonResponse | Response;

    /**
     * Update an existing test
     * 
     * @param Survey $survey
     * @param Test $test
     * @return JsonResponse|Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * 
     * @OA\Put(
     *     path="/tests/{survey}/update/{test}",
     *     tags={"Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="survey",
     *         in="path",
     *         required=true,
     *         description="Survey ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="test",
     *         in="path", 
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="primes",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="prime", type="string", example="Prime 1"),
     *                     @OA\Property(property="equivalent", type="string", example="Equivalent 1"),
     *                     @OA\Property(property="index", type="integer", example=1),
     *                     @OA\Property(property="can_select", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="colors",
     *                         type="object",
     *                         @OA\Property(property="normal", type="string"),
     *                         @OA\Property(property="hover", type="string"),
     *                         @OA\Property(property="active", type="string")
     *                     ),
     *                     @OA\Property(property="value", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="config",
     *                 type="object",
     *                 required={
     *                     "title", "main_trial", "random", "prime_header", "main_header",
     *                     "empty_selection", "main_header_timeout", "type", "single_select",
     *                     "limit", "auto_next", "display_by", "random_prime",
     *                     "random_prime_subsets", "prime_as_next", "bottom_text",
     *                     "conditions", "macros"
     *                 },
     *                 @OA\Property(property="title", type="string", nullable=true),
     *                 @OA\Property(property="main_trial", type="boolean"),
     *                 @OA\Property(property="random", type="boolean"),
     *                 @OA\Property(property="prime_header", type="string", nullable=true),
     *                 @OA\Property(property="main_header", type="string"),
     *                 @OA\Property(property="empty_selection", type="string"),
     *                 @OA\Property(property="main_header_timeout", type="integer"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="single_select", type="boolean"),
     *                 @OA\Property(property="limit", type="integer", example=1),
     *                 @OA\Property(property="auto_next", type="boolean", example=true),
     *                 @OA\Property(property="display_by", type="integer", example=1),
     *                 @OA\Property(property="random_prime", type="boolean", example=true),
     *                 @OA\Property(property="random_prime_subsets", type="boolean", example=true),
     *                 @OA\Property(property="prime_as_next", type="boolean", example=true),
     *                 @OA\Property(property="bottom_text", type="string", example="Bottom text"),
     *                 @OA\Property(property="conditions", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="macros", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(
     *                 property="targets",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="target", type="string", example="Target 1"),
     *                     @OA\Property(property="equivalent", type="string", example="Equivalent 1"), 
     *                     @OA\Property(property="index", type="integer", example=1),
     *                     @OA\Property(property="can_select", type="boolean", example=true),
     *                     @OA\Property(                                                                                                                                                                                                    
     *                         property="colors",
     *                         type="object",
     *                         @OA\Property(property="normal", type="string"),
     *                         @OA\Property(property="hover", type="string"),
     *                         @OA\Property(property="active", type="string")
     *                     ),
     *                     @OA\Property(property="value", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="tests",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Unauthorized access")
     * )
     */
    public function update(Survey $survey, Test $test): JsonResponse | Response;

    /**
     * Delete a test
     * 
     * @param Survey $survey
     * @param Test $test
     * @return JsonResponse|Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * 
     * @OA\Delete(
     *     path="/tests/{survey}/destroy/{test}",
     *     tags={"Tests"},
     *     summary="Delete a test",
     *     description="Deletes the specified test from the survey. Only authorized users can access this endpoint.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="survey",
     *         in="path",
     *         required=true,
     *         description="Survey ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="test",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized access"),
     *     @OA\Response(response=404, description="Test not found")
     * )
     */
    public function destroy(Survey $survey, Test $test): JsonResponse | Response;
}