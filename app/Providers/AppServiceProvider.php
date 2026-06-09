<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
//Para la paginacion
use Illuminate\Pagination\Paginator;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot()
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();

        // Compartir la variable $ticket a la vista layouts.menu
        view()->composer('layouts.menu', function ($view) {
            $ticketId = request()->route('id') ?? request()->route('ticket');
            $ticket = null;
            if ($ticketId) {
                $ticket = \App\Models\Tickets::find($ticketId);
            }
            if (!$ticket) {
                // Arreglo vacío con claves nulas para evitar errores de tipo int/null en el modelo Tickets
                $ticket = ['TicketID' => null, 'id' => null];
            }
            $view->with('ticket', $ticket);
        });
    }
    
    
}
