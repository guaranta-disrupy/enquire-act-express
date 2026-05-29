<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0",
 *     title="Express API Documentation",
 *     description="API documentation for Express application"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="Engine",
 *     description="API endpoints for engine"
 * )
 * 
 * @OA\Tag(
 *     name="Questionnaires",
 *     description="API endpoints for managing questionnaires"
 * )
 * 
 * @OA\Tag(
 *     name="Question Templates",
 *     description="API endpoints for managing question templates"
 * )
 * 
 * @OA\Tag(
 *     name="Questions",
 *     description="API endpoints for managing questions"
 * )
 * 
 * @OA\Tag(
 *     name="Themes",
 *     description="API endpoints for managing themes"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
