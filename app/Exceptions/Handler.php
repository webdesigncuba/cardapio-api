<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * Convert an authentication exception into an unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'message' => 'No autenticado. Debes iniciar sesión con tu token Sanctum.',
            'status' => 401
        ], 401);
    }

    public function register(): void
    {
        $this->renderable(function (ModelNotFoundException|NotFoundHttpException $e, $request) {
            return response()->json([
                'message' => 'Recurso no encontrado',
                'status' => 404,
            ], 404);
        });
    }
}
