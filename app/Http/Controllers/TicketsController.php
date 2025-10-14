<?php

namespace App\Http\Controllers;

use App\Models\Empleados;
use App\Models\Solicitud;
use App\Models\Tickets;
use Illuminate\Http\Request;

class TicketsController extends Controller
{
    public function index()
    {
        $tickets = Tickets::orderBy('created_at', 'desc')->get();

        $ticketsStatus = [
            'nuevos' => $tickets->where('Estatus', 'Pendiente'),
            'proceso' => $tickets->where('Estatus', 'En progreso'),
            'resueltos' => $tickets->where('Estatus', 'Cerrado'),
        ];

        $responsablesTI = Empleados::where('ObraID', 46)->where('tipo_persona', 'FISICA')->get();

        //$solicitudes = Solicitud::all();

        return view('tickets.index', compact('ticketsStatus', 'responsablesTI',/*'solicitudes'*/));
    }

    public function update(Request $request)
    {
        try {
            $ticketId = $request->input('ticketId');
            $ticket = Tickets::find($ticketId);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            // Actualizar los campos permitidos
            if ($request->has('prioridad')) {
                $ticket->Prioridad = $request->input('prioridad');
            }

            if ($request->has('responsableTI')) {
                $ticket->ResponsableTI = $request->input('responsableTI');
            }

            if ($request->has('estatus')) {
                $ticket->Estatus = $request->input('estatus');
            }

            $ticket->save();

            return response()->json([
                'success' => true,
                'message' => 'Ticket actualizado correctamente',
                'ticket' => $ticket
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el ticket: ' . $e->getMessage()
            ], 500);
        }
    }
}
