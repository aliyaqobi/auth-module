<?php

namespace Modules\Auth\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * Return success response
     */
    public function success($data = null, $message = 'Your request has been successfully completed.')
    {
        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], 200);
    }

    /**
     * Return error response
     */
    public function error($message = 'Something went wrong', $errors = [], $status = 400)
    {
        return response()->json([
            'status' => $status,
            'success' => false,
            'message' => $message,
            'data' => ['errors' => $errors],
        ], $status);
    }
}
