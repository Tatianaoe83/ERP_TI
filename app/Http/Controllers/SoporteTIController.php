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
use App\Models\TicketMantenimiento;
use App\Models\CargoMovimiento;
use App\Models\SolicitudMovimiento;
use App\Models\SolicitudMovimientoArchivo;
use App\Models\SolicitudMovimientoLinea;
use App\Services\MovimientoErpService;
use App\Services\SolicitudAprobacionEmailService;
use App\Services\SolicitudFlujoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Events\TicketUpdatedEvent;

class SoporteTIController extends Controller
{
    public function index()
    {
        return view('soporte.index');
    }

    public function autoCompleteEmpleado(Request $request): JsonResponse
    {
        $query = $request->get('query');

        $resultados = Empleados::query()
            ->fisicosActivos()
            ->select('EmpleadoID', 'NombreEmpleado')
            ->where('NombreEmpleado', 'like', '%' . $query . '%')
            ->orderBy('NombreEmpleado')
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

        $empleado = Empleados::query()->fisicosActivos()->where('Correo', $correo)->first();

        if (!$empleado) {
            $otroTipo = Empleados::where('Correo', $correo)->where('Estado', 1)->where('tipo_persona', '!=', 'FISICA')->exists();
            if ($otroTipo) {
                return response()->json(['error' => 'Solo se puede dar de alta a una persona física activa.'], 422);
            }

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

        // Si el tipo es Solicitud o Mantenimiento, incluir departamento
        if (in_array($type, ['Solicitud', 'Mantenimiento'], true)) {
            $empleado->load(['puestos.departamentos.gerencia', 'obras']);

            $response['NombreDepartamento'] = $empleado->puestos->departamentos->NombreDepartamento ?? null;
        }

        if ($type === 'Movimiento') {
            $empleado->load(['puestos', 'obras']);
            $puesto = (string) ($empleado->puestos->NombrePuesto ?? '');
            if (!app(MovimientoErpService::class)->puedeSolicitar($puesto)) {
                return response()->json([
                    'error' => 'Esta solicitud solo la puede levantar un auxiliar de compras, un residente de compras o un residente administrativo.',
                ], 422);
            }
            $response['NombrePuesto'] = $puesto;
            $response['ObraID'] = $empleado->ObraID;
            $response['NombreObra'] = $empleado->obras->NombreObra ?? null;
        }

        if ($type === 'Solicitud') {
            $flujo = app(SolicitudFlujoService::class);
            $jefe = $flujo->jefeInmediato($empleado);
            $destino = $flujo->destinoTrasSupervisor($empleado);

            $response['GerenciaID'] = $empleado->puestos->departamentos->gerencia->GerenciaID ?? null;
            $response['NombreGerencia'] = $empleado->puestos->departamentos->gerencia->NombreGerencia ?? null;
            $response['PuestoID'] = $empleado->puestos->PuestoID ?? null;
            $response['NombrePuesto'] = $empleado->puestos->NombrePuesto ?? null;
            $response['ObraID'] = $empleado->obras->ObraID ?? null;
            $response['NombreObra'] = $empleado->obras->NombreObra ?? null;
            $response['JefeDirectoID'] = $jefe->EmpleadoID ?? null;
            $response['NombreJefe'] = $jefe->NombreEmpleado ?? null;
            $response['DestinoID'] = $destino['empleado']->EmpleadoID ?? null;
            $response['DestinoNombre'] = $destino['empleado']->NombreEmpleado ?? null;
            $response['DestinoRol'] = $destino['rol'];
            $response['DestinoDetalle'] = $destino['detalle'];
        }

        return response()->json($response);
    }

    public function destinoDeJefe(Request $request): JsonResponse
    {
        $persona = Empleados::query()->fisicosActivos()->find((int) $request->input('empleado_id'));
        if (!$persona) {
            return response()->json(['error' => 'El jefe debe ser una persona física activa.'], 422);
        }

        $destino = app(SolicitudFlujoService::class)->destinoVisible($persona);

        $destinoId = (int) ($destino['empleado']->EmpleadoID ?? 0);

        return response()->json([
            'DestinoID' => $destinoId ?: null,
            'DestinoNombre' => $destino['empleado']->NombreEmpleado ?? null,
            'DestinoRol' => $destino['rol'],
            'DestinoDetalle' => $destino['detalle'],
            'Solo' => $destinoId > 0 && $destinoId === (int) $persona->EmpleadoID,
        ]);
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

        if (!in_array($type, ['Ticket', 'Solicitud', 'Mantenimiento', 'Movimiento'])) {
            \Log::warning('Tipo no válido: ' . $type);
            return redirect()->back()->with(['error' => 'Tipo no válido'], 400);
        }

        // ==========================================
        // LÓGICA PARA TICKETS (Igual que siempre)
        // ==========================================
        if ($type === 'Ticket') {

             $correo = $request->input('Correo');
             if (empty($correo)) return redirect()->back()->with('error', 'El correo electrónico es requerido');
             
             $empleado = Empleados::query()->fisicosActivos()->where('Correo', $correo)->first();
             if (!$empleado) return redirect()->back()->with('error', 'Solo se puede dar de alta a una persona física activa.');
             
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
                     'Estatus' => 'Pendiente',
                 ];
                 if ($request->input('Numero')) $ticketData['Numero'] = $request->input('Numero');
                 if ($request->input('CodeAnyDesk')) $ticketData['CodeAnyDesk'] = $request->input('CodeAnyDesk');
                 if (!empty($names)) $ticketData['imagen'] = json_encode($names);
 
                 Tickets::create($ticketData);
                 event(new TicketUpdatedEvent()); // Disparamos el evento para notificar a los componentes que se refresquen
                 
                 return redirect()->back()->with(['success' => 'Ticket guardado correctamente', 'tipo' => 'Ticket']);
             } catch (\Exception $e) {
                 return redirect()->back()->with('error', 'Error al guardar ticket: ' . $e->getMessage());
             }
        }

        // ==========================================
        // LÓGICA PARA MANTENIMIENTOS DE COMPRAS
        // ==========================================
        if ($type === 'Mantenimiento') {
            $data = $request->validate([
                'NombreSolicitante' => 'required|string|max:255',
                'Correo'            => 'required|email|max:255',
                'AreaDepartamento'  => 'nullable|string|max:255',
                'Descripcion'       => 'required|string',
            ]);

            $empleado = Empleados::query()->fisicosActivos()->where('Correo', $data['Correo'])->first();
            if (!$empleado) {
                return redirect()->back()->with('error', 'Solo se puede dar de alta a una persona física activa.');
            }

            $files = $request->file('imagen');
            $names = [];
            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = uniqid() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs('tickets-mantenimiento', $fileName, 'public');
                        $names[] = $path;
                    }
                }
            }

            try {
                TicketMantenimiento::create([
                    'EmpleadoID'    => $empleado->EmpleadoID,
                    'ResponsableID' => TicketMantenimiento::RESPONSABLE[0] ?? null,
                    'Descripcion'   => $data['Descripcion'],
                    'Categoria'     => null,
                    'Prioridad'     => null,
                    'Estatus'       => 'Pendiente',
                    'imagen'        => !empty($names) ? $names : null,
                ]);

                return redirect()->back()->with(['success' => 'Solicitud de mantenimiento guardada correctamente', 'tipo' => 'Mantenimiento']);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error al guardar solicitud: ' . $e->getMessage());
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

            $empleadoSolicitante = Empleados::query()->fisicosActivos()->where('Correo', $data['Correo'])->first();
            if (!$empleadoSolicitante) {
                return redirect()->back()->with('error', 'Solo se puede dar de alta a una persona física activa.');
            }

            $flujo = app(SolicitudFlujoService::class);

            // El jefe inmediato viene propuesto; si la persona lo cambia, se usa el que eligió.
            // Si lo deja vacío, el visto bueno de supervisor se aprueba solo.
            $supervisor = null;
            if (!empty($data['SupervisorID']) && is_numeric($data['SupervisorID']) && $data['SupervisorID'] > 0) {
                $supervisor = Empleados::query()->fisicosActivos()->find($data['SupervisorID']);
                if (!$supervisor) {
                    return redirect()->back()->with('error', 'El jefe inmediato debe ser una persona física activa.');
                }
            }

            $gerencia = $empleadoSolicitante->puestos?->departamentos?->gerencia;
            // Si eligieron otro jefe, el siguiente visto bueno es el de esa persona.
            $destino = $supervisor
                ? $flujo->destinoVisible($supervisor)
                : $flujo->destinoTrasSupervisor($empleadoSolicitante);
            $gerente = $destino['empleado'];
            $gerencia = $gerencia ?: $destino['gerencia'];

            if (!$gerencia || !$gerente) {
                return redirect()->back()->with('error', $destino['detalle'] ?: 'No se pudo determinar a quién se envía después del visto bueno del supervisor.');
            }
            
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
                        if ($s['stage'] === 'gerencia' || $s['stage'] === 'administracion') {
                            // Detenemos la búsqueda sin asignar nada.
                            // $firstToken queda null → no se envía ningún correo.
                            $emailEncontrado = true;
                        } else {
                            $firstToken = $tokenRow->token;
                            $primerAprobadorReal = Empleados::find($s['approver']);
                            $rolPrimerAprobador = $s['role'];
                            $emailEncontrado = true;
                        }
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

                // Aviso al buzón de soporte: las solicitudes nuevas se les pasaban de largo.
                // Si el correo falla no se cae el alta, que ya quedó guardada.
                if ($solicitudId) {
                    try {
                        $solicitudAviso = Solicitud::with('empleadoid')->find($solicitudId);
                        if ($solicitudAviso) {
                            app(SolicitudAprobacionEmailService::class)->enviarAvisoNuevaSolicitudASoporte(
                                $solicitudAviso,
                                $primerAprobadorReal,
                                $rolPrimerAprobador
                            );
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("No se pudo avisar a soporte de la solicitud #{$solicitudId}: " . $e->getMessage());
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

        if ($type === 'Movimiento') {
            return $this->guardarMovimientoErp($request);
        }

        return redirect()->back()->with(['error' => 'Error inesperado'], 500);
    }

    private function guardarMovimientoErp(Request $request)
    {
        $data = $request->validate([
            'Correo' => 'required|email',
            'ObraID' => 'required|integer',
            'Folio' => 'nullable|string|max:40',
            'Justificacion' => 'required|string|max:5000',
            'lineas' => 'required|array|min:1|max:23',
            'lineas.*.CargoID' => 'required|integer',
            'lineas.*.CodigoInsumo' => 'required|string|max:40',
            'lineas.*.Descripcion' => 'required|string|max:180',
            'lineas.*.Partida' => 'required|string|max:120',
            'lineas.*.Cantidad' => 'required|numeric',
            'lineas.*.Precio' => 'required|numeric',
            'lineas.*.Justificacion' => 'required|string|max:500',
        ]);

        $solicitante = Empleados::query()->fisicosActivos()->with('puestos')->where('Correo', $data['Correo'])->first();
        if (!$solicitante) {
            return redirect()->back()->with('error', 'Solo se puede dar de alta a una persona física activa.');
        }

        $servicio = app(MovimientoErpService::class);
        if (!$servicio->puedeSolicitar($solicitante->puestos->NombrePuesto ?? '')) {
            return redirect()->back()->with('error', 'Esta solicitud solo la puede levantar un auxiliar de compras, un residente de compras o un residente administrativo.');
        }

        $folio = trim((string) ($data['Folio'] ?? ''));
        if ($folio !== '' && SolicitudMovimiento::query()->where('Folio', $folio)->exists()) {
            return redirect()->back()->with('error', 'Ese folio ya está usado en otra solicitud.')->withInput();
        }

        $cargos = CargoMovimiento::query()->whereIn('CargoID', collect($data['lineas'])->pluck('CargoID'))->get()->keyBy('CargoID');
        if ($cargos->count() !== collect($data['lineas'])->pluck('CargoID')->unique()->count()) {
            return redirect()->back()->with('error', 'Hay un cargo que no es válido.');
        }

        $primerCargo = $cargos->get((int) $data['lineas'][0]['CargoID']);
        $roles = $cargos->map(fn ($cargo) => $cargo->etiquetaAutoriza())->unique()->implode(', ');
        $autorizadores = collect();
        foreach ($cargos as $cargo) {
            $autorizadores = $autorizadores->merge($servicio->autorizadores($cargo, (int) $data['ObraID']));
        }
        $autorizadores = $autorizadores->unique('EmpleadoID')->values();

        $solicitud = DB::transaction(function () use ($data, $solicitante, $primerCargo, $roles, $autorizadores, $folio, $cargos) {
            $solicitud = SolicitudMovimiento::create([
                'EmpleadoID' => $solicitante->EmpleadoID,
                'ObraID' => $data['ObraID'],
                'CargoID' => $primerCargo->CargoID,
                'ResidenteControlID' => null,
                'AutorizadorID' => $autorizadores->first()->EmpleadoID ?? null,
                'AutorizadorRol' => $roles,
                'Justificacion' => $data['Justificacion'],
                'Folio' => $folio !== '' ? $folio : null,
                'Estatus' => 'Por autorizar',
            ]);

            foreach (array_values($data['lineas']) as $indice => $linea) {
                $cantidad = (float) $linea['Cantidad'];
                $precio = (float) $linea['Precio'];
                SolicitudMovimientoLinea::create([
                    'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
                    'Orden' => $indice + 1,
                    'CargoID' => $cargos->get((int) $linea['CargoID'])->CargoID,
                    'CodigoInsumo' => $linea['CodigoInsumo'],
                    'Descripcion' => $linea['Descripcion'],
                    'Partida' => $linea['Partida'],
                    'Cantidad' => $cantidad,
                    'Precio' => $precio,
                    'Importe' => round($cantidad * $precio, 2),
                    'Justificacion' => $linea['Justificacion'],
                ]);
            }

            return $solicitud;
        });

        $servicio->avisar($solicitud);

        return redirect()->back()->with([
            'success' => 'Solicitud de movimiento registrada. Se avisó al autorizador, al auxiliar de control y al residente del proyecto.',
            'tipo' => 'Movimiento',
        ]);
    }
}