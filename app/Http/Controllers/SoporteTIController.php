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
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
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

            $solicitud = Solicitud::create([
                'EmpleadoID' => $empleado->EmpleadoID,
                'Motivo' => $request->input('Motivo'),
                'DescripcionMotivo' => $request->input('DescripcionMotivo'),
                'Requerimientos' => $request->input('Requerimientos'),
                'ObraID' => $request->input('ObraID'),
                'SupervisorID' => $request->input('SupervisorID'),
                'GerenciaID' => $request->input('GerenciaID'),
                'PuestoID' => $request->input('PuestoID'),
                'Proyecto' => $request->input('Proyecto') ?: '',
                'Estatus' => 'Pendiente Aprobación Supervisor',
                'AprobacionSupervisor' => 'Pendiente',
                'AprobacionGerencia' => 'Pendiente',
                'AprobacionAdministracion' => 'Pendiente',
            ]);

            // Enviar notificación al supervisor
            $this->enviarNotificacionSupervisor($solicitud);

            return redirect()->back()->with([
                'success' => 'Solicitud guardada correctamente',
                'tipo' => 'Solicitud'
            ]);
        }
        return redirect()->back()->with(['error' => 'Ocurrió un error inesperado'], 500);
    }

    /**
     * Enviar notificación al supervisor cuando se crea una solicitud
     */
    private function enviarNotificacionSupervisor($solicitud)
    {
        try {
            $supervisor = Empleados::find($solicitud->SupervisorID);
            if (!$supervisor || !$supervisor->Correo) {
                Log::warning("No se pudo enviar notificación al supervisor: SupervisorID {$solicitud->SupervisorID}");
                return;
            }

            $asunto = "Nueva Solicitud #{$solicitud->SolicitudID} - Requiere Aprobación";
            $contenido = $this->construirContenidoEmailSupervisor($solicitud);

            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);
            
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($supervisor->Correo);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            
            $mail->send();
            
            Log::info("Notificación de nueva solicitud enviada a supervisor {$supervisor->Correo} para solicitud #{$solicitud->SolicitudID}");
        } catch (\Exception $e) {
            Log::error("Error enviando notificación al supervisor: " . $e->getMessage());
        }
    }

    /**
     * Construir contenido de email para supervisor
     */
    private function construirContenidoEmailSupervisor($solicitud)
    {
        $empleado = $solicitud->empleadoid;
        $url = url("/solicitudes/{$solicitud->SolicitudID}/aprobar");
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2 style='color: #2563eb;'>Nueva Solicitud Requiere Aprobación</h2>
            <div style='background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>Solicitud #{$solicitud->SolicitudID}</strong></p>
                <p><strong>Empleado:</strong> {$empleado->NombreEmpleado}</p>
                <p><strong>Correo:</strong> {$empleado->Correo}</p>
                <p><strong>Motivo:</strong> {$solicitud->Motivo}</p>
                <p><strong>Descripción:</strong> {$solicitud->DescripcionMotivo}</p>
                <p><strong>Requerimientos:</strong> {$solicitud->Requerimientos}</p>
                <p><strong>Fecha:</strong> {$solicitud->created_at->format('d/m/Y H:i')}</p>
            </div>
            <p>
                <a href='{$url}' style='background-color: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Ver y Aprobar Solicitud
                </a>
            </p>
        </body>
        </html>
        ";
    }

    /**
     * Configurar PHPMailer
     */
    private function configurarMailer($mail)
    {
        $mail->isSMTP();
        $mail->Host = config('mail.mailers.smtp.host');
        $mail->SMTPAuth = true;
        $mail->Username = config('mail.mailers.smtp.username');
        $mail->Password = config('mail.mailers.smtp.password');
        $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
        $mail->Port = config('mail.mailers.smtp.port');
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;
    }
}
