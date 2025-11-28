<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Perfume Shop API",
 *     version="1.0.0",
 *     description="API documentation for Perfume Shop e-commerce platform",
 *     @OA\Contact(
 *         email="support@perfumeshop.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: Bearer {token}"
 * )
 */
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
}
