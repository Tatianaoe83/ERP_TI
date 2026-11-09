<?php

namespace App\Services;

use App\Models\CargoMovimiento;
use App\Models\Empleados;
use App\Models\Gerencia;
use App\Models\SolicitudMovimiento;
use App\Models\SolicitudMovimientoPaso;
use App\Models\SolicitudMovimientoToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MovimientoErpService
{
    public function autorizador(CargoMovimiento $cargo, ?int $obraId): ?Empleados
    {
        return $this->autorizadores($cargo, $obraId)->first();
    }

    public function autorizadores(CargoMovimiento $cargo, ?int $obraId): Collection
    {
        return match ($cargo->Autoriza) {
            'gerente_compras' => $this->porPuesto('GERENTE DE COMPRAS'),
            'gerente_presupuestos' => $this->porPuesto('GERENTE DE PRESUPUESTOS'),
            'director_proyectos' => collect([$this->directorProyectos()])->filter()->values(),
            default => $this->jefesDeCostos($obraId),
        };
    }

    public function autorizadoresDe(SolicitudMovimiento $solicitud): Collection
    {
        $solicitud->loadMissing('lineas.cargo', 'cargo');
        $cargos = $solicitud->lineas->pluck('cargo')->filter();
        if ($cargos->isEmpty() && $solicitud->cargo) {
            $cargos = collect([$solicitud->cargo]);
        }

        $personas = collect();
        foreach ($cargos->unique('CargoID') as $cargo) {
            $personas = $personas->merge($this->autorizadores($cargo, (int) $solicitud->ObraID));
        }

        return $personas->unique('EmpleadoID')->values();
    }

    public function puedeSolicitar(?string $puesto): bool
    {
        $puesto = mb_strtoupper(trim((string) $puesto));

        return in_array($puesto, ['RESIDENTE DE COMPRAS', 'RESIDENTE ADMINISTRATIVO', 'AUXILIAR DE COMPRAS'], true);
    }

    public function residentesControl(): Collection
    {
        return $this->porPuesto('RESIDENTE DE CONTROL');
    }

    public function auxiliaresControl(): Collection
    {
        return $this->porPuesto('AUXILIAR DE CONTROL');
    }

    public function avisar(SolicitudMovimiento $solicitud): void
    {
        $solicitud->loadMissing(['solicitante', 'obra', 'cargo', 'residente', 'autorizador', 'lineas.cargo']);

        $residentes = $solicitud->ResidenteControlID
            ? collect([$solicitud->residente])
            : $this->residentesControl();

        $destinatarios = $this->auxiliaresControl()
            ->merge($residentes)
            ->filter()
            ->unique('EmpleadoID')
            ->reject(fn ($persona) => (int) $persona->EmpleadoID === (int) $solicitud->AutorizadorID);

        $folio = $solicitud->Folio ?: ('solicitud ' . $solicitud->SolicitudMovimientoID);
        $asunto = 'Solicitud de movimiento ERP ' . $folio;
        $lineas = $solicitud->lineas->map(function ($linea) {
            return ($linea->Orden) . '. ' . ($linea->cargo->Clave ?? '')
                . ' | ' . $linea->CodigoInsumo
                . ' | ' . $linea->Descripcion
                . ' | ' . $linea->Partida
                . ' | ' . $linea->Cantidad . ' x ' . $linea->Precio
                . ' = ' . $linea->Importe
                . ' | ' . $linea->Justificacion;
        })->implode("\n");

        $cuerpo = "Hay una solicitud de movimiento en el ERP.\n\n"
            . 'Solicitante: ' . ($solicitud->solicitante->NombreEmpleado ?? '') . "\n"
            . 'Proyecto: ' . ($solicitud->obra->NombreObra ?? '') . "\n"
            . 'Autoriza: ' . $solicitud->AutorizadorRol . "\n"
            . 'Residente de control: ' . ($solicitud->residente->NombreEmpleado ?? 'residentes de control') . "\n\n"
            . $solicitud->Justificacion . "\n\n"
            . $lineas;

        foreach ($destinatarios as $persona) {
            if (empty($persona->Correo)) {
                continue;
            }
            try {
                Mail::raw($cuerpo, function ($mensaje) use ($persona, $asunto) {
                    $mensaje->to($persona->Correo, $persona->NombreEmpleado)->subject($asunto);
                });
            } catch (\Throwable $e) {
                Log::warning('No se pudo avisar el movimiento ERP a ' . $persona->Correo . ': ' . $e->getMessage());
            }
        }
    }

    private function porPuestoExacto(string $puesto): ?Empleados
    {
        return $this->porPuesto($puesto)->first();
    }

    private function porPuesto(string $puesto): Collection
    {
        return Empleados::query()
            ->with('puestos')
            ->fisicosActivos()
            ->whereHas('puestos', fn ($q) => $q->where('NombrePuesto', $puesto))
            ->orderBy('NombreEmpleado')
            ->get();
    }

    private function jefesDeCostos(?int $obraId): Collection
    {
        if ($obraId) {
            $residentes = Empleados::query()
                ->with('puestos')
                ->fisicosActivos()
                ->where('ObraID', $obraId)
                ->whereHas('puestos', fn ($q) => $q->where('NombrePuesto', 'like', 'RESIDENTE DE COSTOS%'))
                ->orderBy('NombreEmpleado')
                ->get();
            if ($residentes->isNotEmpty()) {
                return $residentes;
            }

            $enObra = $this->porPuesto('JEFE DE COSTOS')
                ->filter(fn ($jefe) => (int) $jefe->ObraID === (int) $obraId)
                ->values();
            if ($enObra->isNotEmpty()) {
                return $enObra;
            }
        }

        return $this->porPuesto('JEFE DE COSTOS')->take(1)->values();
    }

    private function directorProyectos(): ?Empleados
    {
        $nombre = Gerencia::query()->where('NombreGerencia', 'PROYECTOS')->value('NombreGerente');
        if ($nombre) {
            $director = Empleados::query()->with('puestos')->fisicosActivos()->where('NombreEmpleado', $nombre)->first();
            if ($director) {
                return $director;
            }
        }

        return Empleados::query()
            ->with('puestos')
            ->fisicosActivos()
            ->whereHas('puestos', fn ($q) => $q->where('NombrePuesto', 'like', '%DIRECTOR%PROYECT%'))
            ->orderBy('NombreEmpleado')
            ->first();
    }

    public function abrirFirma(SolicitudMovimiento $solicitud, Empleados $firmante): SolicitudMovimientoToken
    {
        $vobo = SolicitudMovimientoPaso::create([
            'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
            'step_order' => 1,
            'stage' => 'vobo',
            'stage_label' => 'Visto bueno',
            'approver_empleado_id' => $firmante->EmpleadoID,
            'status' => 'pending',
        ]);

        SolicitudMovimientoPaso::create([
            'SolicitudMovimientoID' => $solicitud->SolicitudMovimientoID,
            'step_order' => 2,
            'stage' => 'control',
            'stage_label' => 'Control aplica',
            'status' => 'pending',
        ]);

        return SolicitudMovimientoToken::create([
            'paso_id' => $vobo->id,
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function renovarFirma(SolicitudMovimiento $solicitud): ?SolicitudMovimientoToken
    {
        $paso = $solicitud->pasos()->where('stage', 'vobo')->where('status', 'pending')->first();
        if (!$paso) {
            return null;
        }

        SolicitudMovimientoToken::query()
            ->where('paso_id', $paso->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return SolicitudMovimientoToken::create([
            'paso_id' => $paso->id,
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function cerrarPaso(SolicitudMovimiento $solicitud, string $stage, string $status, ?string $comment, ?int $empleadoId): void
    {
        $paso = $solicitud->pasos()->where('stage', $stage)->first();
        if (!$paso || $paso->status !== 'pending') {
            return;
        }

        $paso->status = $status;
        $paso->comment = $comment;
        $paso->decided_at = now();
        $paso->decided_by_empleado_id = $empleadoId;
        $paso->save();

        $token = SolicitudMovimientoToken::query()
            ->where('paso_id', $paso->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();
        if ($token) {
            $token->used_at = now();
            $token->save();
        }
    }

    public function enviarLigaFirma(SolicitudMovimiento $solicitud, Empleados $firmante, SolicitudMovimientoToken $liga): bool
    {
        if (empty($firmante->Correo)) {
            return false;
        }

        $solicitud->loadMissing(['solicitante', 'obra']);

        try {
            Mail::send('emails.movimientos.firma', [
                'solicitud' => $solicitud,
                'firmante' => $firmante,
                'url' => url('/revision-movimiento/' . $liga->token),
                'vence' => optional($liga->expires_at)->format('d/m/Y H:i'),
            ], function ($mensaje) use ($firmante, $solicitud) {
                $mensaje->to($firmante->Correo, $firmante->NombreEmpleado)
                    ->subject('Visto bueno de movimiento ERP #' . $solicitud->SolicitudMovimientoID);
            });

            return true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar la liga de firma del movimiento ' . $solicitud->SolicitudMovimientoID . ': ' . $e->getMessage());

            return false;
        }
    }

    public function generarFormato(SolicitudMovimiento $solicitud): string
    {
        $solicitud->loadMissing(['solicitante', 'obra', 'lineas.cargo']);
        $plantilla = public_path('formatos/solicitud-movimientos-erp.xlsx');
        $zip = new \ZipArchive();
        $copia = tempnam(sys_get_temp_dir(), 'mov');
        copy($plantilla, $copia);
        $zip->open($copia);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');

        $fecha = $solicitud->created_at ?: now();
        $serial = gregoriantojd((int) $fecha->format('n'), (int) $fecha->format('j'), (int) $fecha->format('Y')) - gregoriantojd(12, 30, 1899);

        $valores = [
            'B2' => ['texto', (string) ($solicitud->obra->NombreObra ?? '')],
            'B4' => ['texto', (string) ($solicitud->solicitante->NombreEmpleado ?? '')],
            'B6' => ['numero', (string) $serial],
        ];

        foreach ($solicitud->lineas->take(23)->values() as $indice => $linea) {
            $fila = 9 + $indice;
            $valores['B' . $fila] = ['texto', (string) ($linea->cargo->Clave ?? '')];
            $valores['C' . $fila] = ['texto', (string) $linea->CodigoInsumo];
            $valores['D' . $fila] = ['texto', (string) $linea->Descripcion];
            $valores['E' . $fila] = ['texto', (string) $linea->Partida];
            $valores['F' . $fila] = ['numero', (string) $linea->Cantidad];
            $valores['G' . $fila] = ['numero', (string) $linea->Precio];
            $valores['I' . $fila] = ['texto', (string) $linea->Justificacion];
            $valores['H' . $fila] = ['formula', 'F' . $fila . '*G' . $fila];
        }

        $xml = preg_replace_callback(
            '/<c r="([A-Z]+\d+)"([^>]*?)(?:\/>|>(.*?)<\/c>)/',
            function ($coincidencia) use ($valores) {
                $ref = $coincidencia[1];
                if (!isset($valores[$ref])) {
                    return $coincidencia[0];
                }
                $estilo = '';
                if (preg_match('/\ss="[^"]*"/', $coincidencia[2], $encontrado)) {
                    $estilo = $encontrado[0];
                }
                [$tipo, $valor] = $valores[$ref];
                if ($tipo === 'texto') {
                    $texto = htmlspecialchars($valor, ENT_XML1);
                    return '<c r="' . $ref . '"' . $estilo . ' t="inlineStr"><is><t>' . $texto . '</t></is></c>';
                }
                if ($tipo === 'formula') {
                    return '<c r="' . $ref . '"' . $estilo . '><f>' . $valor . '</f></c>';
                }
                return '<c r="' . $ref . '"' . $estilo . '><v>' . $valor . '</v></c>';
            },
            $xml
        );

        $xml = str_replace('<calcPr calcId="191028"/>', '<calcPr calcId="191028" fullCalcOnLoad="1"/>', $xml);
        if (strpos($xml, 'fullCalcOnLoad') === false) {
            $libro = $zip->getFromName('xl/workbook.xml');
            $libro = str_replace('<calcPr calcId="191028"/>', '<calcPr calcId="191028" fullCalcOnLoad="1"/>', $libro);
            $zip->addFromString('xl/workbook.xml', $libro);
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();
        $contenido = file_get_contents($copia);
        @unlink($copia);

        return $contenido;
    }
}
