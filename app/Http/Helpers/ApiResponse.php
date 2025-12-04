<?php

namespace App\Http\Helpers;

class ApiResponse
{
    public static function success($data = null, $message = 'Success', $code = 200)
    {
         if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $data,
            ], $code);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error($message = 'Error', $code = 400, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}
