<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param mixed $errors
     * @param int $code
     * @return JsonResponse
     */
    protected function errorResponse(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param LengthAwarePaginator|ResourceCollection $paginator
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    protected function paginatedResponse($paginator, $data = null, ?string $message = null): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        // Handle ResourceCollection
        if ($paginator instanceof ResourceCollection) {
            $response['data'] = $paginator->collection;
            $response['meta'] = [
                'current_page' => $paginator->resource->currentPage(),
                'last_page' => $paginator->resource->lastPage(),
                'per_page' => $paginator->resource->perPage(),
                'total' => $paginator->resource->total(),
            ];
        } else {
            // Handle LengthAwarePaginator
            // If $data is a ResourceCollection, resolve it to an array
            if ($data instanceof ResourceCollection) {
                $response['data'] = $data->resolve();
            } else {
                $response['data'] = $data ?? $paginator->items();
            }
            $response['meta'] = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ];
        }

        return response()->json($response);
    }
}

