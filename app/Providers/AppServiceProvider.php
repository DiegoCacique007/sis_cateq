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
