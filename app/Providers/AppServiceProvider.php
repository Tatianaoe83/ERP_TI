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

        // El panel de ticket (partials.modal-ticket) se monta global en el layout (fuera de /tickets),
        // donde no llega $responsablesTI del controlador. Se lo proveemos aquí (misma fuente que el controlador).
        view()->composer('partials.modal-ticket', function ($view) {
            if (!array_key_exists('responsablesTI', $view->getData())) {
                $view->with('responsablesTI', \App\Models\Empleados::where('ObraID', 46)
                    ->where('tipo_persona', 'FISICA')->get());
            }
        });
    }
    
    
}
