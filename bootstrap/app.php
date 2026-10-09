<?php

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$wantsJson = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : route('login'),
        );

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($wantsJson): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) use ($wantsJson) {
            return $wantsJson($request)
                ? ApiResponse::error('Validation failed.', 422, $e->errors())
                : null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($wantsJson) {
            return $wantsJson($request)
                ? ApiResponse::error('Unauthenticated.', 401)
                : null;
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($wantsJson) {
            return $wantsJson($request)
                ? ApiResponse::error($e->getMessage() ?: 'This action is unauthorized.', 403)
                : null;
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($wantsJson) {
            return $wantsJson($request)
                ? ApiResponse::error('Resource not found.', 404)
                : null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($wantsJson) {
            return $wantsJson($request)
                ? ApiResponse::error('Endpoint not found.', 404)
                : null;
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($wantsJson) {
            return $wantsJson($request)
                ? ApiResponse::error($e->getMessage() ?: 'HTTP error.', $e->getStatusCode())
                : null;
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($wantsJson) {
            return $wantsJson($request)
                ? ApiResponse::error(config('app.debug') ? $e->getMessage() : 'Server error.', 500)
                : null;
        });
    })->create();
