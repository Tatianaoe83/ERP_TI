<?php

namespace App\Http\Controllers;

use App\Models\Empleados;
use App\Models\Gerencia;
use App\Models\Obras;
use App\Models\Proyecto;
use App\Models\Solicitud;
use App\Models\Tickets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Colors\Rgb\Channels\Red;

class SoporteTIController extends Controller
{
    public function index()
    {
        return view('soporte.index');
    }

    public function autoCompleteEmpleado(Request $request): JsonResponse
    {
        $query = $request->get('query');

        $resultados = DB::table('empleados')
            ->select('EmpleadoID', 'NombreEmpleado')
            ->where('NombreEmpleado', 'like', '%' . $query . '%')
            ->groupBy('EmpleadoID', 'NombreEmpleado')
            ->limit(5)
            ->get();

        return response()->json($resultados);
    }

    public function getEmpleadoInfo(Request $request): JsonResponse
    {
        $empleadoID = $request->input('EmpleadoID');
        $tipo = $request->input('type');

        if (!in_array($tipo, ['Ticket', 'Solicitud'])) {
            return response()->json(['error' => 'Tipo no válido'], 400);
        }

        $empleado = Empleados::query();

        if ($tipo === 'Ticket') {
            $empleado = $empleado->select('EmpleadoID', 'NombreEmpleado', 'Correo', 'NumTelefono')->find($empleadoID);

            if (!$empleado) {
                return response()->json(['error' => 'Empleado no encontrado'], 404);
            }

            return response()->json([
                'correo' => $empleado->Correo,
                'telefono' => $empleado->NumTelefono
            ]);
        }

        if ($tipo === 'Solicitud') {
            $empleado = $empleado->with(['gerencia', 'puestos', 'obras'])->find($empleadoID);

            if (!$empleado) {
                return response()->json(['error' => 'Empleado no encontrado'], 404);
            }

            return response()->json([
                'GerenciaID' => $empleado->puestos->departamentos->gerencia->GerenciaID ?? null,
                'NombreGerencia' => $empleado->puestos->departamentos->gerencia->NombreGerencia ?? null,
                'PuestoID' => $empleado->puestos->PuestoID ?? null,
                'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                'ObraID' => $empleado->obras->ObraID ?? null,
                'NombreObra' => $empleado->obras->NombreObra ?? null,
            ]);
        }


        return response()->json(['error' => 'Ocurrió un error inesperado'], 500);
    }

    public function getTypes(): JsonResponse
    {
        $proyectos = Proyecto::select('ProyectoID as id', 'NombreProyecto as text')->get();
        $obras = Obras::select('ObraID as id', 'NombreObra as text')->get();
        $gerencias = Gerencia::select('GerenciaID as id', 'NombreGerencia as text')->get();

        $data = [
            [
                'text' => 'Proyectos',
                'children' => $proyectos
            ],
            [
                'text' => 'Obras',
                'children' => $obras
            ],
            [
                'text' => 'Gerencias',
                'children' => $gerencias
            ],
        ];
        return response()->json($data);
    }

    public function crearTickets(Request $request)
    {
        $type = $request->input('type');

        if (!in_array($type, ['Ticket', 'Solicitud'])) {
            return redirect()->back()->with(['error' => 'Tipo no válido'], 400);
        }

        if ($type === 'Ticket') {

            $files = $request->file('imagen');
            $names = [];

            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    $fileName = uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('tickets', $fileName, 'public');
                    $names[] = $path;
                }
            }

            $ticket = Tickets::create([
                'EmpleadoID' => $request->input('EmpleadoID'),
                'Numero' => $request->input('Numero'),
                'CodeAnyDesk' => $request->input('CodeAnyDesk'),
                'Descripcion' => $request->input('Descripcion'),
                'imagen' => $names
            ]);

            return redirect()->back()->with(['success' => 'Ticket guardado correctamente']);
        }

        if ($type === 'Solicitud') {

            $solicitud = Solicitud::create([
                'EmpleadoID' => $request->input('EmpleadoID'),
                'Motivo' => $request->input('Motivo'),
                'DescripcionMotivo' => $request->input('DescripcionMotivo'),
                'Requerimientos' => $request->input('Requerimientos'),
                'ObraID' => $request->input('ObraID'),
                'SupervisorID' => $request->input('SupervisorID'),
                'GerenciaID' => $request->input('GerenciaID'),
                'PuestoID' => $request->input('PuestoID'),
                'Proyecto' => $request->input('Proyecto'),
            ]);

            return redirect()->back()->with(['success' => 'Solicitud guardada correctamente']);
        }
        return redirect()->back()->with(['error' => 'Ocurrió un error inesperado'], 500);
    }
}
