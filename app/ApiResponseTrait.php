<?php

namespace App;

trait ApiResponseTrait
{
    protected function successResponse($message, $data = [], $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function paginateResponse($message, $data, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data->items(),
            'meta'    => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage()
            ],
            'links' => [
                'first' => $data->url(1),
                'last'  => $data->url($data->lastPage()),
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ],
        ], $code);
    }

    protected function errorResponse($message, $errors = [], $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function emptyDataMessage($name) {
        return "No " . $name . " data available.";
    }

    protected function availableDataMessage($name) {
        return $name . " data is available.";
    }

    protected function successMessage(string $operation): string
    {
        return "Data has been successfully " . $operation . ".";
    }

    protected function errorMessage(string $operation, string $reason = ""): string
    {
        $base = "Failed to " . $operation . " data.";
        
        return $reason ? $base . " Reason: " . $reason : $base;
    }
}
