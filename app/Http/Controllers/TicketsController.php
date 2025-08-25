<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Tickets;
use Illuminate\Http\Request;

class TicketsController extends Controller
{
    public function index()
    {
        $tickets = Tickets::orderBy('created_at', 'desc')
            ->get()
            ->groupBy('Estatus');
        $solicitud = Solicitud::all();
        return view('tickets.index', compact('tickets', /*'solicitudes'*/));
    }
}
