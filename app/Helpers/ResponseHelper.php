<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    public static function success($message = '', $status = true, $data = [], $statusCode = 200): JsonResponse
    {
        return response()->json(compact('message', 'status', 'data'), $statusCode);
    }

    public static function error($message = 'Something went wrong, please try again.', $status = false, $statusCode = 400): JsonResponse
    {
        return response()->json(compact('message', 'status'), $statusCode);
    }

    public static function unprocessableEntity($message = '', $status = false,  $statusCode = 422): JsonResponse
    {
        return response()->json(compact('message', 'status'), $statusCode);
    }

    public static function unauthenticated($message = 'Unauthenticated.', $status = false,  $statusCode = 403): JsonResponse
    {
        return response()->json(compact('message', 'status'), $statusCode);
    }

    public static function notFound($message = 'Not found.', $status = false,  $statusCode = 404): JsonResponse
    {
        return response()->json(compact('message', 'status'), $statusCode);
    }
}
