<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\AuditLog;
use App\Http\Middleware\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        $middleware->throttleApi();

        $middleware->api(prepend: [
            SanitizeInput::class,
        ]);

        $middleware->api(append: [
            SecurityHeaders::class,
            AuditLog::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json(['message' => 'Resource tidak ditemukan.'], 404);
            }

            if ($e instanceof ThrottleRequestsException) {
                return response()->json(['message' => 'Terlalu banyak permintaan, coba lagi nanti.'], 429);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json(['message' => 'Terjadi kesalahan.'], $e->getStatusCode());
            }

            report($e);

            return response()->json(['message' => 'Terjadi kesalahan pada server.'], 500);
        });
    })->create();