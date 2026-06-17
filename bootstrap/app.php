<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for all requests (not just API routes)
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);

        // Handle 404 errors with custom JSON response
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'error' => 'Route not found',
                'message' => 'The requested endpoint does not exist',
            ], 404);
        });
    })->create();
