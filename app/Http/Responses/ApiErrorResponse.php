<?php

namespace App\Http\Responses;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiErrorResponse
{
    public static function validation(Validator $validator): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => 'validation_error',
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()->toArray(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function from(Throwable $exception): JsonResponse
    {
        [$status, $code, $message] = match (true) {
            $exception instanceof ValidationException => [Response::HTTP_UNPROCESSABLE_ENTITY, 'validation_error', 'The given data was invalid.'],
            $exception instanceof AuthenticationException => [Response::HTTP_UNAUTHORIZED, 'unauthenticated', 'Unauthenticated.'],
            $exception instanceof AuthorizationException => [Response::HTTP_FORBIDDEN, 'forbidden', 'This action is unauthorized.'],
            $exception instanceof ModelNotFoundException => [Response::HTTP_NOT_FOUND, 'not_found', 'The requested resource was not found.'],
            $exception instanceof HttpExceptionInterface => self::httpExceptionDetails($exception),
            default => [Response::HTTP_INTERNAL_SERVER_ERROR, 'server_error', 'An unexpected error occurred.'],
        };

        $payload = ['status' => 'error', 'code' => $code, 'message' => $message];

        if ($exception instanceof ValidationException) {
            $payload['errors'] = $exception->errors();
        }

        return response()->json($payload, $status);
    }

    private static function httpExceptionDetails(HttpExceptionInterface $exception): array
    {
        $status = $exception->getStatusCode();
        $codes = [
            Response::HTTP_BAD_REQUEST => 'bad_request',
            Response::HTTP_UNAUTHORIZED => 'unauthenticated',
            Response::HTTP_FORBIDDEN => 'forbidden',
            Response::HTTP_NOT_FOUND => 'not_found',
            Response::HTTP_METHOD_NOT_ALLOWED => 'method_not_allowed',
            Response::HTTP_TOO_MANY_REQUESTS => 'too_many_requests',
        ];

        return [$status, $codes[$status] ?? 'http_error', $exception->getMessage() ?: (Response::$statusTexts[$status] ?? 'Request failed.')];
    }
}
