<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param mixed $result
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendSuccess($result = [], string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if (!empty($result)) {
            $response['data'] = $result;
        }

        return response()->json($response, $code);
    }

    /**
     * Error response method.
     *
     * @param string $message
     * @param array $errors
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendError(string $message, array $errors = [], int $code = 400): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Response with pagination method.
     *
     * @param mixed $result
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendPaginated($result, string $message = 'Success'): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $result->items(),
            'pagination' => [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
        ];

        return response()->json($response);
    }

    /**
     * Validation error response method.
     *
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendValidationError(array $errors): JsonResponse
    {
        return $this->sendError('Validation failed', $errors, 422);
    }

    /**
     * Not found response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->sendError($message, [], 404);
    }

    /**
     * Unauthorized response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->sendError($message, [], 401);
    }

    /**
     * Forbidden response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendForbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->sendError($message, [], 403);
    }
}