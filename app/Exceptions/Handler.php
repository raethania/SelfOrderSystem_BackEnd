<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\Request;
use App\ApiResponseTrait;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class Handler
 *
 * Global exception handler custom untuk API yang meneruskan
 * format error menggunakan ApiResponseTrait.
 */
class Handler
{
    use ApiResponseTrait;

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Throwable $e, Request $request)
    {
        if ($e instanceof ValidationException) {
            return $this->errorResponse('Validation Error', $e->errors(), 422);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return $this->errorResponse('Resource not found', [], 404);
        }

        if ($e instanceof AuthenticationException) {
            return $this->errorResponse('Unauthenticated', [], 401);
        }

        if ($e instanceof AccessDeniedHttpException) {
            return $this->errorResponse('Unauthorized access', [], 403);
        }

        // Tangkap kode HTTP jika exception memiliki method getStatusCode
        $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        // Sembunyikan detail error (termasuk trace database) jika tidak mode debug
        $message = config('app.debug') ? $e->getMessage() : 'Internal Server Error';
        
        return $this->errorResponse($message, [], $code);
    }
}
