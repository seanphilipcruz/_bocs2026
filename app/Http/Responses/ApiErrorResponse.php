<?php

namespace App\Http\Responses;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public static function from(Throwable $exception, ?Request $request = null): JsonResponse
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

        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $requestId = $request?->header('X-Request-ID') ?: (string) Str::uuid();
            [$category, $safeMessage, $suggestion] = self::serverErrorDetails($exception);

            $payload['message'] = $safeMessage;
            $payload['error'] = [
                'category' => $category,
                'request_id' => $requestId,
                'method' => $request?->method(),
                'path' => $request?->path(),
                'suggestion' => $suggestion,
            ];

            if (config('app.debug')) {
                $payload['error']['debug'] = [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => collect($exception->getTrace())
                        ->take(8)
                        ->map(fn (array $frame): array => array_filter([
                            'file' => $frame['file'] ?? null,
                            'line' => $frame['line'] ?? null,
                            'class' => $frame['class'] ?? null,
                            'function' => $frame['function'] ?? null,
                        ], fn ($value): bool => $value !== null))
                        ->values()
                        ->all(),
                ];
            }

            return response()->json($payload, $status, ['X-Request-ID' => $requestId]);
        }

        return response()->json($payload, $status);
    }

    private static function serverErrorDetails(Throwable $exception): array
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'oauth')
            || str_contains($message, 'cryptkey')
            || str_contains($message, 'key file')
            || str_contains($message, 'key path')
            || str_contains($message, 'invalid key supplied')) {
            return [
                'authentication_configuration_error',
                'The authentication service is not configured correctly.',
                'Verify that the Laravel Passport keys exist, are readable, and have valid permissions.',
            ];
        }

        if (str_contains($message, 'sqlstate') || str_contains($message, 'database')) {
            return [
                'database_error',
                'The API could not complete a database operation.',
                'Verify the database connection, schema migrations, and submitted data.',
            ];
        }

        if (str_contains($message, 'permission denied') || str_contains($message, 'not writable')) {
            return [
                'filesystem_error',
                'The API could not access a required file or directory.',
                'Verify ownership and permissions for Laravel storage and cache directories.',
            ];
        }

        return [
            'application_error',
            'The API could not complete the request.',
            'Use the request_id to locate the matching exception in storage/logs/laravel.log.',
        ];
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
