<?php

namespace App\Traits;

trait FormatResponseTrait
{
    public function successResponse($message = '', $data = [])
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], 200);
    }

    public function errorResponse($message = '', $statusCode = 400, $data = [])
    {
        return response()->json([
            'status'  => 'failed',
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }
}
