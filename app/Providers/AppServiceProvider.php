<?php

namespace App\Providers;

use App\Crm\AgendaTareas;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // La plantilla SB Admin usa Bootstrap: la paginación también.
        Paginator::useBootstrapFive();
        Carbon::setLocale(config('app.locale'));

        // Contador de tareas vencidas junto a "Tareas" en el menú.
        View::composer('layouts.include.navigation-menu', function ($view) {
            $view->with('tareasVencidas', app(AgendaTareas::class)->vencidas());
        });
    }
}
