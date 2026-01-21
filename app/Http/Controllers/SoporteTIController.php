<?php

namespace App\Http\Controllers;

use App\Models\Empleados;
use App\Models\Gerencia;
use App\Models\Obras;
use App\Models\Proyecto;
use App\Models\Solicitud;
use App\Models\SolicitudPasos;
use App\Models\SolicitudTokens;
use App\Models\Tickets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public function buscarEmpleadoPorCorreo(Request $request): JsonResponse
    {
        $correo = $request->input('correo');
        $type = $request->input('type'); // 'Ticket' o 'Solicitud'

        if (empty($correo)) {
            return response()->json(['error' => 'Correo requerido'], 400);
        }

        $empleado = Empleados::where('Correo', $correo)->first();

        if (!$empleado) {
            return response()->json(['error' => 'No se encontró correo, contacta a soporte'], 404);
        }

        $response = [
            'EmpleadoID' => $empleado->EmpleadoID,
            'NombreEmpleado' => $empleado->NombreEmpleado,
            'Correo' => $empleado->Correo,
            'NumTelefono' => $empleado->NumTelefono,
            'PuestoID' => $empleado->PuestoID,
            'ObraID' => $empleado->ObraID,
        ];

        // Si el tipo es Solicitud, incluir información completa de Gerencia, Puesto y Obra
        if ($type === 'Solicitud') {
            $empleado->load(['puestos.departamentos.gerencia', 'obras']);

            $response['GerenciaID'] = $empleado->puestos->departamentos->gerencia->GerenciaID ?? null;
            $response['NombreGerencia'] = $empleado->puestos->departamentos->gerencia->NombreGerencia ?? null;
            $response['PuestoID'] = $empleado->puestos->PuestoID ?? null;
            $response['NombrePuesto'] = $empleado->puestos->NombrePuesto ?? null;
            $response['ObraID'] = $empleado->obras->ObraID ?? null;
            $response['NombreObra'] = $empleado->obras->NombreObra ?? null;
        }

        return response()->json($response);
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
            \Log::warning('Tipo no válido: ' . $type);
            return redirect()->back()->with(['error' => 'Tipo no válido'], 400);
        }

        if ($type === 'Ticket') {

            // Validar que el correo esté presente
            $correo = $request->input('Correo');
            if (empty($correo)) {
                return redirect()->back()->with('error', 'El correo electrónico es requerido');
            }

            // Buscar el empleado por correo para obtener el EmpleadoID
            $empleado = Empleados::where('Correo', $correo)->first();
            if (!$empleado) {
                return redirect()->back()->with('error', 'No se encontró el empleado con el correo proporcionado');
            }

            // Validar que la descripción esté presente
            $descripcion = $request->input('Descripcion');
            if (empty($descripcion)) {
                return redirect()->back()->with('error', 'La descripción es requerida');
            }

            $files = $request->file('imagen');
            $names = [];

            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = uniqid() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs('tickets', $fileName, 'public');
                        $names[] = $path;
                    }
                }
            }

            try {
                // Preparar datos para el ticket
                $ticketData = [
                    'EmpleadoID' => $empleado->EmpleadoID,
                    'Descripcion' => $descripcion,
                    'Prioridad' => 'Baja', // Valor por defecto para tickets desde el formulario público
                    'Estatus' => 'Pendiente', // Valor por defecto
                ];

                // Agregar campos opcionales solo si tienen valor
                if ($request->input('Numero')) {
                    $ticketData['Numero'] = $request->input('Numero');
                }

                if ($request->input('CodeAnyDesk')) {
                    $ticketData['CodeAnyDesk'] = $request->input('CodeAnyDesk');
                }

                if (!empty($names)) {
                    $ticketData['imagen'] = json_encode($names);
                }

                // Crear el ticket

                $ticket = Tickets::create($ticketData);


                return redirect()->back()->with([
                    'success' => 'Ticket guardado correctamente',
                    'tipo' => 'Ticket'
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('Error SQL al crear ticket: ' . $e->getMessage());
                \Log::error('SQL: ' . $e->getSql());
                \Log::error('Bindings: ' . json_encode($e->getBindings()));
                return redirect()->back()->with('error', 'Error al guardar el ticket. Por favor verifica los datos e intenta nuevamente.');
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('Error de validación al crear ticket: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Error de validación: ' . $e->getMessage());
            } catch (\Exception $e) {
                \Log::error('Error al crear ticket: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return redirect()->back()->with('error', 'Error al guardar el ticket: ' . $e->getMessage());
            }
        }

        if ($type === 'Solicitud') {

            $data = $request->validate([
                'Correo' => 'required|email',
                'Motivo' => 'nullable|string',
                'DescripcionMotivo' => 'required|string',
                'Requerimientos' => 'required|string',
                'ObraID' => 'required|integer',
                'PuestoID' => 'required|integer',
                'Proyecto' => 'nullable|string',
                'SupervisorID' => 'required|integer',
            ]);

            $empleadoSolicitante = Empleados::where('Correo', $data['Correo'])->first();
            if (!$empleadoSolicitante) {
                return redirect()->back()->with('error', 'Empleado solicitante no encontrado');
            }

            $supervisor = Empleados::find($data['SupervisorID']);
            if (! $supervisor) {
                return redirect()->back()->with('error', 'Supervisor no encontrado');
            }

            $gerencia = $supervisor->puestos?->departamentos?->gerencia;

            if (! $gerencia || ! $gerencia->NombreGerente) {
                return redirect()->back()->with('error', 'El supervisor no tiene gerencia válida');
            }

            $gerente = Empleados::where('NombreEmpleado', $gerencia->NombreGerente)->first();
            if (! $gerente) {
                return redirect()->back()->with('error', 'Gerente no encontrado');
            }

            $gerenciaAdmin = Gerencia::where('NombreGerencia', 'Administración')->first();
            if (! $gerenciaAdmin || ! $gerenciaAdmin->NombreGerente) {
                return redirect()->back()->with('error', 'Gerencia Administración mal configurada');
            }

            $admin = Empleados::where('NombreEmpleado', $gerenciaAdmin->NombreGerente)->first();
            if (! $admin) {
                return redirect()->back()->with('error', 'Administrador no encontrado');
            }

            try {
                DB::transaction(function () use (
                    $data,
                    $empleadoSolicitante,
                    $supervisor,
                    $gerencia,
                    $gerente,
                    $admin
                ) {

                    $solicitud = Solicitud::create([
                        'EmpleadoID' => $empleadoSolicitante->EmpleadoID,
                        'Motivo' => $data['Motivo'] ?? null,
                        'DescripcionMotivo' => $data['DescripcionMotivo'],
                        'Requerimientos' => $data['Requerimientos'],
                        'ObraID' => $data['ObraID'],
                        'GerenciaID' => $gerencia->GerenciaID,
                        'PuestoID' => $data['PuestoID'],
                        'Proyecto' => $data['Proyecto'] ?? '',
                        'Estatus' => 'Pendiente',
                    ]);

                    $steps = [
                        ['order' => 1, 'stage' => 'supervisor',     'approver' => $supervisor->EmpleadoID],
                        ['order' => 2, 'stage' => 'gerencia',       'approver' => $gerente->EmpleadoID],
                        ['order' => 3, 'stage' => 'administracion', 'approver' => $admin->EmpleadoID],
                    ];

                    foreach ($steps as $s) {
                        $step = SolicitudPasos::create([
                            'solicitud_id' => $solicitud->SolicitudID,
                            'step_order' => $s['order'],
                            'stage' => $s['stage'],
                            'approver_empleado_id' => $s['approver'],
                            'status' => 'pending',
                        ]);

                        SolicitudTokens::create([
                            'approval_step_id' => $step->id,
                            'token' => Str::uuid(),
                            'expires_at' => now()->addDays(7),
                        ]);
                    }
                });

                return redirect()->back()->with('success', 'Solicitud guardada correctamente');
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('Error SQL al crear solicitud: ' . $e->getMessage());
                \Log::error('SQL: ' . $e->getSql());
                \Log::error('Bindings: ' . json_encode($e->getBindings()));
                return redirect()->back()->with('error', 'Error al guardar la solicitud. Por favor verifica los datos e intenta nuevamente.');
            } catch (\Exception $e) {
                \Log::error('Error al crear solicitud: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return redirect()->back()->with('error', 'Error al guardar la solicitud: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with(['error' => 'Ocurrió un error inesperado'], 500);
    }
}
