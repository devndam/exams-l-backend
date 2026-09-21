<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Support\TelegramNotifier;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt' => Authenticate::class,
            'role' => Authorize::class,
        ]);

        $middleware->throttleApi('general');

        // Our custom JWT auth isn't Laravel's built-in AuthenticatesRequests contract, so
        // without this it's absent from Laravel's default middleware priority list and the
        // global 'throttle:general' (part of the 'api' group) runs BEFORE it — meaning
        // $request->user() is never set yet when the 'general' limiter's key callback runs,
        // silently falling back to IP and re-pooling an entire shared network's traffic
        // into one bucket. This forces auth.jwt to always run before any throttle middleware.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\ThrottleRequests::class,
            prepend: Authenticate::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ApiException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], $e->statusCode);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $errors[] = ['field' => $field, 'message' => $message];
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 400);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Route not found',
                'errors' => [],
            ], 404);
        });

        // Runs AFTER every other exception->response resolution (the render() callbacks
        // above, Laravel's own special-cases for HttpResponseException/ThrottleRequestsException/
        // ModelNotFoundException/etc., and the default renderer) — never race those by adding
        // a catch-all render() callback, which would intercept HttpResponseException before
        // Laravel unwraps it and turn e.g. a 429 rate-limit response into a bogus 500.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return $response;
            }

            $contentType = $response->headers->get('Content-Type') ?? '';
            if (! str_contains($contentType, 'json')) {
                return $response;
            }

            $data = json_decode($response->getContent() ?: '{}', true) ?? [];
            if (array_key_exists('success', $data)) {
                return $response;
            }

            // Anything reaching here was not thrown as ApiException/ValidationException (both
            // already render themselves above) — mirrors the Node handler's
            // `!(err instanceof ApiError)` branch: sanitize the message in production.
            $message = app()->environment('production')
                ? 'Internal Server Error'
                : ($data['message'] ?? $e->getMessage() ?: 'Internal Server Error');

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [],
            ], $response->getStatusCode(), $response->headers->all());
        });

        $exceptions->report(function (Throwable $e) {
            if ($e instanceof ApiException || $e instanceof ValidationException || $e instanceof HttpResponseException) {
                return;
            }

            app(TelegramNotifier::class)->notifyError($e, request());
        });
    })->create();
