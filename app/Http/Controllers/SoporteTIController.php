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
use App\Services\SolicitudAprobacionEmailService;
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

        // ==========================================
        // LÓGICA PARA TICKETS (Igual que siempre)
        // ==========================================
        if ($type === 'Ticket') {

             $correo = $request->input('Correo');
             if (empty($correo)) return redirect()->back()->with('error', 'El correo electrónico es requerido');
             
             $empleado = Empleados::where('Correo', $correo)->first();
             if (!$empleado) return redirect()->back()->with('error', 'No se encontró el empleado');
             
             $descripcion = $request->input('Descripcion');
             if (empty($descripcion)) return redirect()->back()->with('error', 'La descripción es requerida');

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
                 $ticketData = [
                     'EmpleadoID' => $empleado->EmpleadoID,
                     'Descripcion' => $descripcion,
                     'Prioridad' => 'Baja',
                     'Estatus' => 'Pendiente',
                 ];
                 if ($request->input('Numero')) $ticketData['Numero'] = $request->input('Numero');
                 if ($request->input('CodeAnyDesk')) $ticketData['CodeAnyDesk'] = $request->input('CodeAnyDesk');
                 if (!empty($names)) $ticketData['imagen'] = json_encode($names);
 
                 Tickets::create($ticketData);
                 return redirect()->back()->with(['success' => 'Ticket guardado correctamente', 'tipo' => 'Ticket']);
             } catch (\Exception $e) {
                 return redirect()->back()->with('error', 'Error al guardar ticket: ' . $e->getMessage());
             }
        }

        // ==========================================
        // LÓGICA PARA SOLICITUDES (CORREGIDA VISUALMENTE)
        // ==========================================
        if ($type === 'Solicitud') {

            $data = $request->validate([
                'Correo' => 'required|email',
                'Motivo' => 'nullable|string',
                'DescripcionMotivo' => 'required|string',
                'Requerimientos' => 'required|string',
                'ObraID' => 'required|integer',
                'PuestoID' => 'required|integer',
                'Proyecto' => 'nullable|string',
                'SupervisorID' => 'nullable', 
            ]);

            $empleadoSolicitante = Empleados::where('Correo', $data['Correo'])->first();
            if (!$empleadoSolicitante) {
                return redirect()->back()->with('error', 'Empleado solicitante no encontrado');
            }

            // 1. Detectar Supervisor
            $supervisor = null;
            if (!empty($data['SupervisorID']) && is_numeric($data['SupervisorID']) && $data['SupervisorID'] > 0) {
                $supervisor = Empleados::find($data['SupervisorID']);
            }

            // 2. Definir Gerencia
            $gerencia = null;
            if ($supervisor) {
                $gerencia = $supervisor->puestos?->departamentos?->gerencia;
            } else {
                $gerencia = $empleadoSolicitante->puestos?->departamentos?->gerencia;
            }

            if (!$gerencia || !$gerencia->NombreGerente) {
                return redirect()->back()->with('error', 'No se pudo determinar la Gerencia o el Gerente');
            }

            $gerente = Empleados::where('NombreEmpleado', $gerencia->NombreGerente)->first();
            
            // Configurar Admin
            $admin = Empleados::where('NombreEmpleado', 'BAAS SANCHEZ JOSE ALBERTO')->first();

            if (!$gerente || !$admin) {
                return redirect()->back()->with('error', 'Faltan aprobadores configurados');
            }

            $firstToken = null;
            $solicitudId = null;
            $primerAprobadorReal = null; 
            $rolPrimerAprobador = '';

            try {
                DB::transaction(function () use (
                    $data, $empleadoSolicitante, $supervisor, $gerencia, 
                    $gerente, $admin, 
                    &$firstToken, &$solicitudId, &$primerAprobadorReal, &$rolPrimerAprobador
                ) {
                    // Crear Solicitud
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
                        'SupervisorID' => $supervisor ? $supervisor->EmpleadoID : null,
                    ]);

                    $solicitudId = $solicitud->SolicitudID;

                    // CONSTRUCCIÓN DE PASOS
                    $steps = [];
                    $ordenActual = 1;

                    // PASO 1: SUPERVISOR
                    if ($supervisor) {
                        // Caso Normal: Pendiente
                        $steps[] = [
                            'stage' => 'supervisor',
                            'approver' => $supervisor->EmpleadoID,
                            'role' => 'Supervisor',
                            'status' => 'pending',          // <-- Pendiente normal
                            'comment' => null,
                            'decided_by' => null,
                            'decided_at' => null
                        ];
                    } else {
                        // Caso Gerente: AUTO-APROBADO
                        // Creamos el registro para que se vea el CHECK VERDE en la vista
                        $steps[] = [
                            'stage' => 'supervisor',
                            'approver' => $empleadoSolicitante->EmpleadoID, // Asignamos al mismo solicitante
                            'role' => 'Supervisor',
                            'status' => 'approved',         // <-- APROBADO DIRECTO
                            'comment' => 'Aprobación automática por jerarquía (Gerencia).',
                            'decided_by' => $empleadoSolicitante->EmpleadoID,
                            'decided_at' => now()
                        ];
                    }

                    // PASO 2: GERENCIA (Siempre Pendiente)
                    $steps[] = [
                        'stage' => 'gerencia',
                        'approver' => $gerente->EmpleadoID,
                        'role' => 'Gerencia',
                        'status' => 'pending',
                        'comment' => null,
                        'decided_by' => null,
                        'decided_at' => null
                    ];

                    // PASO 3: ADMINISTRACIÓN
                    $steps[] = [
                        'stage' => 'administracion',
                        'approver' => $admin->EmpleadoID,
                        'role' => 'Administración',
                        'status' => 'pending',
                        'comment' => null,
                        'decided_by' => null,
                        'decided_at' => null
                    ];

                    // Guardar Pasos
                    $emailEncontrado = false;

                    foreach ($steps as $s) {
                        $step = SolicitudPasos::create([
                            'solicitud_id' => $solicitud->SolicitudID,
                            'step_order' => $ordenActual++,
                            'stage' => $s['stage'],
                            'approver_empleado_id' => $s['approver'],
                            'status' => $s['status'], // 'pending' o 'approved'
                            'comment' => $s['comment'],
                            'decided_by_empleado_id' => $s['decided_by'],
                            'decided_at' => $s['decided_at']
                        ]);

                        // Generar Token
                        $tokenRow = SolicitudTokens::create([
                            'approval_step_id' => $step->id,
                            'token' => Str::uuid(),
                            'expires_at' => now()->addDays(7),
                            // Si ya está aprobado, quemamos el token
                            'used_at' => ($s['status'] === 'approved') ? now() : null,
                        ]);

                        // Detectar a quién enviar el correo
                        // Buscamos el PRIMER paso que esté 'pending'
                        if ($s['status'] === 'pending' && !$emailEncontrado) {
                            $firstToken = $tokenRow->token;
                            $primerAprobadorReal = Empleados::find($s['approver']);
                            $rolPrimerAprobador = $s['role'];
                            $emailEncontrado = true; // Dejamos de buscar
                        }
                    }
                });

                // Enviar Correo al primer aprobador REAL encontrado
                if ($firstToken && $solicitudId && $primerAprobadorReal) {
                    $solicitud = Solicitud::with('empleadoid')->find($solicitudId);
                    if ($solicitud) {
                        app(SolicitudAprobacionEmailService::class)
                            ->enviarRevisionPendiente($primerAprobadorReal, $solicitud, $firstToken, $rolPrimerAprobador);
                    }
                }

                return redirect()->back()->with([
                    'success' => 'Solicitud guardada correctamente',
                    'tipo' => 'Solicitud',
                ]);

            } catch (\Exception $e) {
                \Log::error('Error al guardar: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Error al guardar la solicitud.');
            }
        }

        return redirect()->back()->with(['error' => 'Error inesperado'], 500);
    }
}