<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Chatbot\Contracts\IntentResolver::class, \App\Services\Chatbot\Intent\RuleBasedIntentResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        \Illuminate\Support\Facades\RateLimiter::for('chatbot', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('chatbot:'.$request->user()->getAuthIdentifier()));
        // Registro: 40 intentos por IP (red compartida de la parroquia) y 5 por correo, cada 10 minutos.
        \Illuminate\Support\Facades\RateLimiter::for('register', function (\Illuminate\Http\Request $request) {
            $email = is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : '';
            $limited = function (\Illuminate\Http\Request $request, array $headers) {
                $message = 'Demasiados intentos de registro. Espera unos minutos e inténtalo de nuevo.';

                return $request->expectsJson()
                    ? response()->json(['message' => $message, 'errors' => ['email' => [$message]]], 429, $headers)
                    : redirect()->route('register')->withErrors(['email' => $message])->withInput($request->only('email'));
            };

            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinutes(10, 40)->by('register-ip:'.$request->ip())->response($limited),
                \Illuminate\Cache\RateLimiting\Limit::perMinutes(10, 5)->by('register-email:'.($email !== '' ? $email : $request->ip()))->response($limited),
            ];
        });
        // Recuperación de contraseña: 20 solicitudes por IP cada 10 minutos; depende solo de la IP, no revela qué correos existen.
        \Illuminate\Support\Facades\RateLimiter::for('forgot-password', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinutes(10, 20)->by('forgot-password-ip:'.$request->ip())
                ->response(function (\Illuminate\Http\Request $request, array $headers) {
                    $message = 'Demasiadas solicitudes de recuperación de contraseña. Espera unos minutos e inténtalo de nuevo.';

                    return $request->expectsJson()
                        ? response()->json(['message' => $message, 'errors' => ['email' => [$message]]], 429, $headers)
                        : redirect()->route('password.request')->withErrors(['email' => $message])->withInput($request->only('email'))
                            ->with('error_title', 'Demasiadas solicitudes');
                });
        });

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (auth()->check()) {
                $role = auth()->user()->role;
                $layout = match ($role) {
                    'parroco' => 'layouts.app_parroquia_parroco',
                    'coordinador_general' => 'layouts.app_parroquia_coordinador_general',
                    'coordinador_comunidades' => 'layouts.app_parroquia_coordinador_comunidades',
                    'catequista' => 'layouts.app_parroquia_catequista',
                    default => 'layouts.app_parroquia_admin'
                };
                $view->with('layout_role', $layout);
                $view->with('route_prefix', $role.'.');
            }
        });
    }
}
