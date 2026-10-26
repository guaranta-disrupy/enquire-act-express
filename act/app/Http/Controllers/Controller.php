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
 *     title="nextsensus API Documentation",
 *     description="API documentation for nextsensus"
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
 *     name="Authentication",
 *     description="API Endpoints for authentication"
 * )
 * 
 * @OA\Tag(
 *     name="Tests",
 *     description="API Endpoints for test management"
 * )
 * 
 * @OA\Tag(
 *     name="Engine",
 *     description="API Endpoints for engine"
 * )
 * 
 * 
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
