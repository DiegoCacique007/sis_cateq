<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (auth()->check()) {
                $role = auth()->user()->role;
                $layout = match($role) {
                    'parroco' => 'layouts.app_parroquia_parroco',
                    'coordinador_general' => 'layouts.app_parroquia_coordinador_general',
                    'coordinador_comunidades' => 'layouts.app_parroquia_coordinador_comunidades',
                    'catequista' => 'layouts.app_parroquia_catequista',
                    default => 'layouts.app_parroquia_admin'
                };
                $view->with('layout_role', $layout);
                $view->with('route_prefix', $role . '.');
            }
        });
    }
}
