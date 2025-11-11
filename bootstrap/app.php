<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        if (config('app.env') === 'production') {
            Integration::handles($exceptions);
        }

        // Global exception handling for Data validation
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->renderable(function (\Spatie\LaravelData\Exceptions\CannotCastEnum $e, $request) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Invalid enum value',
                    'errors' => ['enum' => ['The provided value is not valid for this field.']],
                ], 422);
            }
        });

        $exceptions->renderable(function (\ArgumentCountError $e, $request) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Required fields missing',
                    'errors' => ['message' => ['Required fields were not provided.']],
                ], 422);
            }
        });

        $exceptions->renderable(function (\Illuminate\Database\Eloquent\MassAssignmentException $e, $request) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Mass assignment error',
                    'errors' => ['message' => [$e->getMessage()]],
                ], 500);
            }
        });
    })->create();
