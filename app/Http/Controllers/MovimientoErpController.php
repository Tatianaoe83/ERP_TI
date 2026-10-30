<?php

namespace App\Http\Controllers;

use App\Models\CargoMovimiento;
use App\Models\Obras;
use App\Models\SolicitudMovimiento;
use App\Models\SolicitudMovimientoArchivo;
use App\Models\SolicitudMovimientoComentario;
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
            ->with(['solicitante', 'obra', 'cargo', 'autorizador', 'residente', 'lineas.cargo'])
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

        return view('movimientos-erp.index', [
            'solicitudes' => $solicitudes,
            'estatus' => $estatus,
            'buscar' => $buscar,
        ]);
    }

    public function show(int $id)
    {
        $solicitud = SolicitudMovimiento::query()
            ->with(['solicitante.puestos', 'obra', 'cargo', 'autorizador.puestos', 'residente', 'archivos', 'comentarios', 'lineas.cargo'])
            ->findOrFail($id);

        return view('movimientos-erp.show', compact('solicitud'));
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

    public function autorizar(Request $request, int $id)
    {
        $data = $request->validate([
            'Texto' => 'required|string|max:2000',
        ]);

        $solicitud = SolicitudMovimiento::query()->findOrFail($id);
        if ($solicitud->Estatus !== 'Por autorizar') {
            return back()->with('error', 'Esta solicitud ya no está pendiente de autorización.');
        }

        DB::transaction(function () use ($solicitud, $data) {
            $solicitud->Estatus = 'Autorizada';
            $solicitud->Respuesta = $data['Texto'];
            $solicitud->autorizado_en = now();
            $solicitud->save();
            SolicitudMovimientoComentario::create([
                'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
                'UserID' => auth()->id(),
                'Autor' => auth()->user()->name ?? auth()->user()->email,
                'Texto' => 'Autorizada: ' . $data['Texto'],
            ]);
        });

        return back()->with('success', 'El cargo quedó autorizado. Control ya puede aplicar el movimiento.');
    }

    public function rechazar(Request $request, int $id)
    {
        $data = $request->validate([
            'Texto' => 'required|string|max:4000',
        ]);

        $solicitud = SolicitudMovimiento::query()->findOrFail($id);
        if ($solicitud->Estatus !== 'Por autorizar') {
            return back()->with('error', 'Esta solicitud ya no está pendiente de autorización.');
        }

        DB::transaction(function () use ($solicitud, $data) {
            $solicitud->Estatus = 'Rechazada';
            $solicitud->save();
            SolicitudMovimientoComentario::create([
                'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
                'UserID' => auth()->id(),
                'Autor' => auth()->user()->name ?? auth()->user()->email,
                'Texto' => 'Rechazada: ' . $data['Texto'],
            ]);
        });

        return back()->with('success', 'La solicitud fue rechazada.');
    }

    public function aplicar(Request $request, int $id)
    {
        $data = $request->validate([
            'Folio' => 'required|string|max:40',
            'correo' => 'nullable|file|max:15360',
        ]);

        $solicitud = SolicitudMovimiento::query()->findOrFail($id);
        if ($solicitud->Estatus !== 'Autorizada') {
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

        DB::transaction(function () use ($request, $solicitud, $folio) {
            $solicitud->Folio = $folio;
            $solicitud->Estatus = 'Aplicada';
            $solicitud->aplicado_en = now();
            $solicitud->save();

            if ($request->hasFile('correo')) {
                $archivo = $request->file('correo');
                $nombre = $folio . '.' . $archivo->getClientOriginalExtension();
                $ruta = $archivo->storeAs('movimientos-erp/' . $solicitud->SolicitudMovimientoID, $nombre, 'public');
                SolicitudMovimientoArchivo::create([
                    'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
                    'Ruta' => $ruta,
                    'Nombre' => $nombre,
                    'Tipo' => 'correo',
                ]);
            }
        });

        return back()->with('success', 'Movimiento aplicado. La copia quedó con el folio ' . $folio . '.');
    }
}
