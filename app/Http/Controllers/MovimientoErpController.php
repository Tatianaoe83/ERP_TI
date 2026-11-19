<?php

namespace App\Http\Controllers;

use App\Models\CargoMovimiento;
use App\Models\Empleados;
use App\Models\Obras;
use App\Models\SolicitudMovimiento;
use App\Models\SolicitudMovimientoArchivo;
use App\Models\SolicitudMovimientoComentario;
use App\Models\SolicitudMovimientoToken;
use App\Services\MovimientoErpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimientoErpController extends Controller
{
    public function catalogo(MovimientoErpService $servicio): JsonResponse
    {
        $cargos = CargoMovimiento::query()->orderBy('Clave')->get()->map(function (CargoMovimiento $cargo) {
            return [
                'id' => $cargo->CargoID,
                'clave' => $cargo->Clave,
                'descripcion' => $cargo->Descripcion,
                'unidad' => $cargo->UnidadNegocio,
                'autoriza' => $cargo->Autoriza,
                'autoriza_etiqueta' => $cargo->etiquetaAutoriza(),
            ];
        });

        $obras = Obras::query()->orderBy('NombreObra')->get(['ObraID', 'NombreObra']);
        $residentes = $servicio->residentesControl()->map(fn ($persona) => [
            'id' => $persona->EmpleadoID,
            'nombre' => $persona->NombreEmpleado,
        ]);

        return response()->json([
            'cargos' => $cargos,
            'obras' => $obras,
            'residentes' => $residentes,
        ]);
    }

    public function autorizadores(Request $request, MovimientoErpService $servicio): JsonResponse
    {
        $obraId = (int) $request->input('obra_id');
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('cargos', []))));
        $cargos = CargoMovimiento::query()->whereIn('CargoID', $ids)->get();
        $lista = [];

        foreach ($cargos as $cargo) {
            foreach ($servicio->autorizadores($cargo, $obraId) as $persona) {
                $lista[$persona->EmpleadoID] = [
                    'id' => $persona->EmpleadoID,
                    'nombre' => $persona->NombreEmpleado,
                    'puesto' => $persona->puestos->NombrePuesto ?? '',
                    'rol' => $cargo->etiquetaAutoriza(),
                ];
            }
        }

        return response()->json(['autorizadores' => array_values($lista)]);
    }

    public function index(Request $request)
    {
        $estatus = $request->input('estatus');
        $buscar = trim((string) $request->input('q'));

        $solicitudes = SolicitudMovimiento::query()
            ->with(['solicitante', 'obra', 'cargo', 'autorizador', 'residente', 'lineas.cargo', 'pasos.approver', 'pasos.decidedBy'])
            ->when($estatus, fn ($q) => $q->where('Estatus', $estatus))
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($inner) use ($buscar) {
                    $inner->where('Folio', 'like', '%' . $buscar . '%')
                        ->orWhere('SolicitudMovimientoID', $buscar)
                        ->orWhereHas('solicitante', fn ($s) => $s->where('NombreEmpleado', 'like', '%' . $buscar . '%'))
                        ->orWhereHas('obra', fn ($s) => $s->where('NombreObra', 'like', '%' . $buscar . '%'))
                        ->orWhereHas('cargo', fn ($s) => $s->where('Clave', 'like', '%' . $buscar . '%'))
                        ->orWhereHas('lineas', fn ($s) => $s->where('CodigoInsumo', 'like', '%' . $buscar . '%'));
                });
            })
            ->orderByDesc('SolicitudMovimientoID')
            ->paginate(15)
            ->withQueryString();

        $productividad = null;
        if ($request->input('tab') === 'productividad') {
            $productividad = app(MovimientoErpService::class)->productividad([
                'anio' => (int) $request->input('anio', 0),
                'mes' => (int) $request->input('mes', 0),
                'obra_id' => (int) $request->input('obra_id', 0),
                'estatus' => trim((string) $request->input('estatus_prod', '')),
            ]);
        }

        return view('movimientos-erp.index', [
            'solicitudes' => $solicitudes,
            'estatus' => $estatus,
            'buscar' => $buscar,
            'productividad' => $productividad,
        ]);
    }

    public function show(int $id)
    {
        $solicitud = SolicitudMovimiento::query()
            ->with(['solicitante.puestos', 'obra', 'cargo', 'autorizador.puestos', 'residente', 'comentarios', 'lineas.cargo', 'pasos.approver', 'pasos.decidedBy', 'pasos.token'])
            ->findOrFail($id);

        return view('movimientos-erp.show', compact('solicitud'));
    }

    public function datos(int $id)
    {
        $solicitud = SolicitudMovimiento::query()
            ->with(['solicitante.puestos', 'obra', 'lineas.cargo', 'pasos.approver', 'pasos.decidedBy'])
            ->findOrFail($id);

        $etiquetas = ['approved' => 'Aprobado', 'rejected' => 'Rechazado', 'pending' => 'Pendiente'];

        return response()->json([
            'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
            'Estatus' => $solicitud->Estatus,
            'Folio' => $solicitud->Folio,
            'Justificacion' => $solicitud->Justificacion,
            'fechaCreacion' => optional($solicitud->created_at)->format('d/m/Y H:i'),
            'aplicadoPor' => $solicitud->aplicado_por,
            'solicitante' => $solicitud->solicitante->NombreEmpleado ?? '',
            'correo' => $solicitud->solicitante->Correo ?? '',
            'puesto' => $solicitud->solicitante->puestos->NombrePuesto ?? '',
            'obra' => $solicitud->obra->NombreObra ?? '',
            'pasos' => $solicitud->pasos->map(function ($paso) use ($solicitud, $etiquetas) {
                $decidio = optional($paso->decidedBy)->NombreEmpleado;
                if ($paso->stage === 'control' && !$decidio) {
                    $decidio = $solicitud->aplicado_por;
                }

                return [
                    'stage' => $paso->stage,
                    'stageLabel' => $paso->stage === 'control' ? 'Neodata' : ($paso->stage_label ?: 'Visto bueno'),
                    'status' => $paso->status,
                    'statusLabel' => $etiquetas[$paso->status] ?? $paso->status,
                    'approverNombre' => optional($paso->approver)->NombreEmpleado,
                    'decidedByNombre' => $decidio,
                    'decidedAt' => optional($paso->decided_at)->format('d/m/Y H:i'),
                    'comment' => $paso->comment,
                ];
            })->values(),
            'lineas' => $solicitud->lineas->map(function ($linea) {
                return [
                    'cargo' => $linea->cargo->Clave ?? '',
                    'codigo' => $linea->CodigoInsumo,
                    'descripcion' => $linea->Descripcion,
                    'partida' => $linea->Partida,
                    'cantidad' => number_format((float) $linea->Cantidad, 2),
                    'importe' => number_format((float) $linea->Importe, 2),
                ];
            })->values(),
        ]);
    }

    public function formato(int $id, MovimientoErpService $servicio)
    {
        $solicitud = SolicitudMovimiento::query()->with(['solicitante', 'obra', 'lineas.cargo'])->findOrFail($id);
        $obra = preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', (string) ($solicitud->obra->NombreObra ?? 'proyecto'));
        $nombre = 'Solicitud Movimientos - ' . trim($obra) . '.xlsx';

        return response($servicio->generarFormato($solicitud), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
        ]);
    }

    public function comentar(Request $request, int $id)
    {
        $data = $request->validate([
            'Texto' => 'required|string|max:4000',
        ]);

        $solicitud = SolicitudMovimiento::query()->findOrFail($id);
        SolicitudMovimientoComentario::create([
            'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
            'UserID' => auth()->id(),
            'Autor' => auth()->user()->name ?? auth()->user()->email,
            'Texto' => $data['Texto'],
        ]);

        return redirect()->route('movimientos-erp.show', $solicitud->SolicitudMovimientoID)
            ->with('success', 'Comentario registrado.');
    }

    public function autorizar(Request $request, int $id, MovimientoErpService $servicio)
    {
        $data = $request->validate([
            'Texto' => 'required|string|max:2000',
        ]);

        $solicitud = SolicitudMovimiento::query()->findOrFail($id);
        if ($solicitud->Estatus !== \App\Models\SolicitudMovimiento::PENDIENTE) {
            return back()->with('error', 'Esta solicitud ya no está pendiente de autorización.');
        }

        DB::transaction(function () use ($solicitud, $data, $servicio) {
            $solicitud->Estatus = \App\Models\SolicitudMovimiento::APROBADA;
            $solicitud->Respuesta = $data['Texto'];
            $solicitud->autorizado_en = now();
            $solicitud->save();
            $servicio->cerrarPaso($solicitud, 'vobo', 'approved', $data['Texto'], $this->empleadoDeSesion());
            SolicitudMovimientoComentario::create([
                'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
                'UserID' => auth()->id(),
                'Autor' => auth()->user()->name ?? auth()->user()->email,
                'Texto' => 'Autorizada: ' . $data['Texto'],
            ]);
        });

        return back()->with('success', 'El cargo quedó autorizado. Control ya puede aplicar el movimiento.');
    }

    public function rechazar(Request $request, int $id, MovimientoErpService $servicio)
    {
        $data = $request->validate([
            'Texto' => 'required|string|max:4000',
        ]);

        $solicitud = SolicitudMovimiento::query()->findOrFail($id);
        if ($solicitud->Estatus !== \App\Models\SolicitudMovimiento::PENDIENTE) {
            return back()->with('error', 'Esta solicitud ya no está pendiente de autorización.');
        }

        DB::transaction(function () use ($solicitud, $data, $servicio) {
            $solicitud->Estatus = 'Rechazada';
            $solicitud->save();
            $servicio->cerrarPaso($solicitud, 'vobo', 'rejected', $data['Texto'], $this->empleadoDeSesion());
            SolicitudMovimientoComentario::create([
                'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
                'UserID' => auth()->id(),
                'Autor' => auth()->user()->name ?? auth()->user()->email,
                'Texto' => 'Rechazada: ' . $data['Texto'],
            ]);
        });

        return back()->with('success', 'La solicitud fue rechazada.');
    }

    public function aplicar(Request $request, int $id, MovimientoErpService $servicio)
    {
        $data = $request->validate([
            'Folio' => 'required|string|max:40',
        ]);

        $solicitud = SolicitudMovimiento::query()->findOrFail($id);
        if ($solicitud->Estatus !== \App\Models\SolicitudMovimiento::APROBADA) {
            return back()->with('error', 'El movimiento solo se aplica cuando el cargo ya está autorizado.');
        }

        $folio = trim($data['Folio']);
        $ocupado = SolicitudMovimiento::query()
            ->where('Folio', $folio)
            ->where('SolicitudMovimientoID', '!=', $solicitud->SolicitudMovimientoID)
            ->exists();
        if ($ocupado) {
            return back()->with('error', 'Ese folio ya está usado en otra solicitud.')->withInput();
        }

        $empleadoId = $this->empleadoDeSesion();
        $nombre = $empleadoId
            ? Empleados::query()->where('EmpleadoID', $empleadoId)->value('NombreEmpleado')
            : null;
        $nombre = $nombre ?: (auth()->user()->name ?? auth()->user()->email ?? 'Usuario del sistema');

        DB::transaction(function () use ($solicitud, $folio, $servicio, $empleadoId, $nombre) {
            $solicitud->Folio = $folio;
            $solicitud->Estatus = \App\Models\SolicitudMovimiento::APLICADA;
            $solicitud->aplicado_en = now();
            $solicitud->aplicado_por = $nombre;
            $solicitud->save();
            $servicio->cerrarPaso($solicitud, 'control', 'approved', 'Verificado en Neodata. Folio ' . $folio, $empleadoId);
        });

        return back()->with('success', 'Movimiento aplicado en Neodata por ' . $nombre . '.');
    }

    public function reenviarFirma(int $id, MovimientoErpService $servicio)
    {
        $solicitud = SolicitudMovimiento::query()->with(['pasos', 'autorizador', 'solicitante', 'obra'])->findOrFail($id);
        $firmante = $solicitud->autorizador;
        if (!$firmante || $solicitud->Estatus !== \App\Models\SolicitudMovimiento::PENDIENTE) {
            return back()->with('error', 'Esta solicitud ya no está esperando el visto bueno.');
        }

        $liga = $servicio->renovarFirma($solicitud);
        if (!$liga || !$servicio->enviarLigaFirma($solicitud, $firmante, $liga)) {
            return back()->with('error', 'No se pudo reenviar la liga de firma.');
        }

        return back()->with('success', 'Se envió una liga nueva a ' . $firmante->NombreEmpleado . '.');
    }

    public function firma(string $token)
    {
        $liga = $this->liga($token);
        if (!$liga) {
            abort(404);
        }

        $paso = $liga->paso;
        $solicitud = $paso->solicitud;

        return view('movimientos-erp.firmar', [
            'liga' => $liga,
            'paso' => $paso,
            'solicitud' => $solicitud,
            'puedeFirmar' => $liga->vigente() && $paso->status === 'pending' && $solicitud->Estatus === \App\Models\SolicitudMovimiento::PENDIENTE,
        ]);
    }

    public function decidir(Request $request, string $token, MovimientoErpService $servicio)
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'Texto' => 'required|string|max:2000',
        ]);

        $liga = $this->liga($token);
        if (!$liga || !$liga->vigente()) {
            return back()->with('error', 'Esta liga ya no sirve. Pide que te envíen otra.');
        }

        $paso = $liga->paso;
        $solicitud = $paso->solicitud;
        if ($paso->status !== 'pending' || $solicitud->Estatus !== \App\Models\SolicitudMovimiento::PENDIENTE) {
            return back()->with('error', 'Esta solicitud ya no está pendiente de firma.');
        }

        DB::transaction(function () use ($solicitud, $data, $servicio, $paso) {
            if ($data['decision'] === 'approved') {
                $solicitud->Estatus = \App\Models\SolicitudMovimiento::APROBADA;
                $solicitud->Respuesta = $data['Texto'];
                $solicitud->autorizado_en = now();
            } else {
                $solicitud->Estatus = 'Rechazada';
            }
            $solicitud->save();
            $servicio->cerrarPaso(
                $solicitud,
                'vobo',
                $data['decision'],
                $data['Texto'],
                (int) $paso->approver_empleado_id
            );
            SolicitudMovimientoComentario::create([
                'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
                'Autor' => $paso->approver->NombreEmpleado ?? 'Visto bueno',
                'Texto' => ($data['decision'] === 'approved' ? 'Firmó el visto bueno: ' : 'Rechazó el visto bueno: ') . $data['Texto'],
            ]);
        });

        return redirect()->route('movimientos-erp.public.show', $token);
    }

    private function liga(string $token): ?SolicitudMovimientoToken
    {
        return SolicitudMovimientoToken::query()
            ->with(['paso.solicitud.solicitante.puestos', 'paso.solicitud.obra', 'paso.solicitud.lineas.cargo', 'paso.approver'])
            ->where('token', $token)
            ->first();
    }

    private function empleadoDeSesion(): ?int
    {
        $correo = auth()->user()->email ?? null;
        if (!$correo) {
            return null;
        }

        $id = Empleados::query()->where('Correo', $correo)->value('EmpleadoID');

        return $id ? (int) $id : null;
    }
}
