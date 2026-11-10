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
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\PHPMailer;

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

        $solicitud->loadMissing(['solicitante', 'obra', 'lineas.cargo']);
        $folio = '#' . $solicitud->SolicitudMovimientoID;
        $vence = optional($liga->expires_at)->format('d/m/Y H:i');
        $lineas = $solicitud->lineas->map(function ($linea) {
            return ($linea->cargo->Clave ?? '') . ' · ' . $linea->CodigoInsumo . ' · ' . $linea->Descripcion
                . ' · ' . $linea->Cantidad . ' x ' . $linea->Precio;
        })->implode("\n");

        $html = view('emails.movimientos.firma', [
            'accent' => '#101D49',
            'accentSoft' => '#E8EEF8',
            'eyebrow' => 'Visto bueno',
            'titulo' => 'Necesitamos tu firma para un movimiento en el ERP',
            'preheader' => 'Solicitud ' . $folio . ' de ' . ($solicitud->solicitante->NombreEmpleado ?? 'Proser'),
            'folio' => $folio,
            'saludo' => $firmante->NombreEmpleado,
            'intro' => 'Hay un movimiento en el ERP pendiente de tu aprobación. Revisa el detalle y firma o rechaza el visto bueno.',
            'url' => url('/revision-movimiento/' . $liga->token),
            'boton' => 'Revisar y firmar',
            'nota' => 'La liga es personal y vence el ' . $vence . '. Cuando firmes, deja de servir.',
            'logoCid' => 'logoProser',
            'marca' => 'Proser',
            'filas' => [
                'Solicitud' => $folio,
                'Solicitó' => $solicitud->solicitante->NombreEmpleado ?? '',
                'Proyecto' => $solicitud->obra->NombreObra ?? '',
                'Estatus' => SolicitudMovimiento::PENDIENTE,
            ],
            'bloques' => array_filter([
                'Mensaje' => $solicitud->Justificacion,
                'Partidas' => $lineas,
            ]),
        ])->render();

        try {
            $smtp = config('mail.mailers.smtp', []);
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp['host'] ?? config('email_tickets.smtp.host');
            $mail->Port = (int) ($smtp['port'] ?? config('email_tickets.smtp.port'));
            $mail->SMTPSecure = $smtp['encryption'] ?? config('email_tickets.smtp.encryption');
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'] ?? config('email_tickets.smtp.username');
            $mail->Password = $smtp['password'] ?? config('email_tickets.smtp.password');
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = (int) config('email_tickets.smtp.timeout', 30);
            $mail->SMTPAutoTLS = ($mail->SMTPSecure !== 'ssl');
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            $desde = config('mail.from.address') ?: config('email_tickets.smtp.from_address');
            $nombreDesde = config('mail.from.name') ?: 'Proser';
            $mail->setFrom($desde, $nombreDesde);
            $mail->clearAllRecipients();
            $mail->clearReplyTos();
            $mail->addAddress($firmante->Correo, $firmante->NombreEmpleado);
            $logo = public_path('img/LogoAzul.png');
            if (is_file($logo)) {
                $mail->addEmbeddedImage($logo, 'logoProser', 'LogoAzul.png');
            }
            $mail->isHTML(true);
            $mail->Subject = 'Visto bueno de movimiento ERP ' . $folio;
            $mail->Body = $html;
            $mail->AltBody = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
            $mail->send();

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

    public function productividad(array $filtros = []): array
    {
        $anio = (int) ($filtros['anio'] ?? 0);
        $mes = (int) ($filtros['mes'] ?? 0);
        $obraId = (int) ($filtros['obra_id'] ?? 0);
        $estatus = trim((string) ($filtros['estatus'] ?? ''));
        $nombresMes = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];

        $anios = SolicitudMovimiento::query()
            ->whereNotNull('created_at')
            ->selectRaw('YEAR(created_at) as anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($valor) => (int) $valor)
            ->filter()
            ->values();
        if (! $anios->contains((int) now()->year)) {
            $anios->prepend((int) now()->year);
        }

        $obraIds = SolicitudMovimiento::query()->whereNotNull('ObraID')->distinct()->pluck('ObraID');
        $obras = \App\Models\Obras::query()
            ->whereIn('ObraID', $obraIds)
            ->orderBy('NombreObra')
            ->get(['ObraID', 'NombreObra']);

        $filas = SolicitudMovimiento::query()
            ->with(['obra:ObraID,NombreObra'])
            ->when($anio > 0, fn ($q) => $q->whereYear('created_at', $anio))
            ->when($mes >= 1 && $mes <= 12, fn ($q) => $q->whereMonth('created_at', $mes))
            ->when($obraId > 0, fn ($q) => $q->where('ObraID', $obraId))
            ->when($estatus !== '', fn ($q) => $q->where('Estatus', $estatus))
            ->get([
                'SolicitudMovimientoID',
                'ObraID',
                'Estatus',
                'aplicado_por',
                'created_at',
                'autorizado_en',
                'aplicado_en',
            ]);

        $contar = fn (string $valor) => $filas->where('Estatus', $valor)->count();
        $promedio = function ($coleccion, string $desde, string $hasta) {
            $horas = $coleccion->filter(fn ($fila) => $fila->{$desde} && $fila->{$hasta})
                ->map(fn ($fila) => $fila->{$desde}->diffInMinutes($fila->{$hasta}) / 60);
            if ($horas->isEmpty()) {
                return null;
            }

            return round($horas->avg(), 1);
        };

        $meses = collect(range(1, 12))->map(function ($numero) use ($filas, $nombresMes, $anio) {
            $delMes = $filas->filter(function ($fila) use ($numero, $anio) {
                if (optional($fila->created_at)->month !== $numero) {
                    return false;
                }
                if ($anio > 0) {
                    return optional($fila->created_at)->year === $anio;
                }

                return true;
            });

            return [
                'nombre' => $nombresMes[$numero],
                'total' => $delMes->count(),
                'aplicadas' => $delMes->where('Estatus', SolicitudMovimiento::APLICADA)->count(),
                'rechazadas' => $delMes->where('Estatus', SolicitudMovimiento::RECHAZADA)->count(),
            ];
        });

        $agrupar = function ($coleccion, callable $nombre) {
            return $coleccion->groupBy($nombre)
                ->map(fn ($grupo, $etiqueta) => ['nombre' => $etiqueta, 'total' => $grupo->count()])
                ->sortByDesc('total')
                ->values()
                ->take(8);
        };

        return [
            'total' => $filas->count(),
            'pendientes' => $contar(SolicitudMovimiento::PENDIENTE),
            'aprobadas' => $contar(SolicitudMovimiento::APROBADA),
            'aplicadas' => $contar(SolicitudMovimiento::APLICADA),
            'rechazadas' => $contar(SolicitudMovimiento::RECHAZADA),
            'horas_firma' => $promedio($filas, 'created_at', 'autorizado_en'),
            'horas_neodata' => $promedio($filas->where('Estatus', SolicitudMovimiento::APLICADA), 'autorizado_en', 'aplicado_en'),
            'meses' => $meses,
            'personas' => $agrupar($filas->where('Estatus', SolicitudMovimiento::APLICADA), fn ($fila) => $fila->aplicado_por ?: 'Sin registro'),
            'obras' => $agrupar($filas, fn ($fila) => optional($fila->obra)->NombreObra ?: 'Sin proyecto'),
            'estatus' => [
                ['nombre' => SolicitudMovimiento::PENDIENTE, 'total' => $contar(SolicitudMovimiento::PENDIENTE)],
                ['nombre' => SolicitudMovimiento::APROBADA, 'total' => $contar(SolicitudMovimiento::APROBADA)],
                ['nombre' => SolicitudMovimiento::APLICADA, 'total' => $contar(SolicitudMovimiento::APLICADA)],
                ['nombre' => SolicitudMovimiento::RECHAZADA, 'total' => $contar(SolicitudMovimiento::RECHAZADA)],
            ],
            'filtros' => [
                'anio' => $anio,
                'mes' => $mes,
                'obra_id' => $obraId,
                'estatus' => $estatus,
            ],
            'catalogo' => [
                'anios' => $anios,
                'obras' => $obras,
                'meses' => $nombresMes,
            ],
        ];
    }
}
