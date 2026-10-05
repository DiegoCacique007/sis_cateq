<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'approved' => \App\Http\Middleware\EnsureUserApproved::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'periodo.activo' => \App\Http\Middleware\AsegurarPeriodoActivo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportWhen(fn (\Throwable $error) => request()->is('chatbot/message'));
        $exceptions->render(function (\Throwable $error, \Illuminate\Http\Request $request) {
            if (! $request->is('chatbot/message')) {
                return null;
            }
            $status = match (true) {
                $error instanceof \Illuminate\Validation\ValidationException => 422,
                $error instanceof \Illuminate\Auth\AuthenticationException => 401,
                $error instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                $error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $error->getStatusCode(),
                default => 500,
            };
            if ($status >= 500) {
                \Illuminate\Support\Facades\Log::error('chatbot.http_failure', ['exception_class' => get_class($error)]);
            }
            [$code, $message] = match ($status) {
                401 => ['AUTH_REQUIRED', 'Inicia sesión para utilizar el asistente.'],
                403 => ['ACCESS_DENIED', 'Tu cuenta no tiene acceso al asistente.'],
                419 => ['SESSION_EXPIRED', 'La sesión caducó. Recarga la página e inicia sesión si es necesario.'],
                422 => ['INVALID_INPUT', 'Escribe entre 2 y 2000 caracteres y selecciona recursos válidos.'],
                429 => ['RATE_LIMITED', 'Has enviado demasiados mensajes. Espera un minuto e inténtalo de nuevo.'],
                default => ['CHATBOT_ERROR', 'No pude procesar la solicitud en este momento.'],
            };

            return response()->json(['status' => $status === 403 ? 'denied' : 'error', 'code' => $code, 'message' => $message,
                'data' => ['items' => [], 'options' => []], 'actions' => [], 'meta' => ['hasMore' => false]], $status)
                ->header('Cache-Control', 'no-store, private');
        });
    })->create();
