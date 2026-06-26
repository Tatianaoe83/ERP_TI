<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketMantenimiento extends Model
{
    use SoftDeletes;

    public $table = 'tickets_mantenimiento';

    protected $primaryKey = 'MantenimientoID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'EmpleadoID',
        'NombreSolicitante',
        'Correo',
        'AreaDepartamento',
        'Asunto',
        'Descripcion',
        'Categoria',
        'Prioridad',
        'Estatus',
        'Responsable',
        'imagen',
        'FechaInicioProgreso',
        'FechaFinProgreso',
    ];

    protected $casts = [
        'MantenimientoID' => 'integer',
        'EmpleadoID' => 'integer',
        'imagen' => 'array',
        'FechaInicioProgreso' => 'datetime',
        'FechaFinProgreso' => 'datetime',
    ];

    public const PRIORIDADES = ['Baja', 'Media', 'Alta', 'Urgente'];

    /** Horario laboral: L-V, 9:00 a 18:00 (9 h/día). */
    public const HORAS_LABORALES_DIA = 9;

    public const HORA_INICIO_LABORAL = 9;

    public const HORA_FIN_LABORAL = 18;

    /** Metas de resolución por prioridad (días laborales). */
    public const SLA_PRIORIDAD = [
        'Baja'    => ['min_dias' => 10, 'max_dias' => 15, 'color' => '#22C55E'],
        'Media'   => ['min_dias' => 5,  'max_dias' => 7,  'color' => '#EAB308'],
        'Alta'    => ['min_dias' => 3,  'max_dias' => 5,  'color' => '#F97316'],
        'Urgente' => ['min_dias' => 1,  'max_dias' => 3,  'color' => '#EF4444'],
    ];

    public const ESTATUS = ['Pendiente', 'En proceso', 'Atendido', 'Pausado', 'Cancelado'];

    /** Transiciones permitidas desde cada estatus (excluye el estatus actual). */
    public const TRANSICIONES = [
        'Pendiente'  => ['En proceso'],
        'En proceso' => ['Atendido', 'Pausado'],
        'Pausado'    => ['Atendido', 'Cancelado'],
        'Atendido'   => [],
        'Cancelado'  => [],
    ];

    public const COLUMNAS_VISTA = [
        'pendiente'  => 'Pendientes',
        'en_proceso' => 'En Proceso',
        'pausado'    => 'Pausado',
        'atendido'   => 'Atendido',
        'cancelado'  => 'Cancelado',
    ];

    public static function esFinalizado(string $estatus): bool
    {
        return in_array($estatus, ['Atendido', 'Cancelado'], true);
    }

    public static function estatusPermitidos(string $actual): array
    {
        if (self::esFinalizado($actual)) {
            return [$actual];
        }

        return array_values(array_unique(array_merge([$actual], self::TRANSICIONES[$actual] ?? [])));
    }

    public static function puedeTransicionar(string $actual, string $nuevo): bool
    {
        if ($actual === $nuevo) {
            return true;
        }

        return in_array($nuevo, self::TRANSICIONES[$actual] ?? [], true);
    }

    public static function agruparPorColumnas($tickets): array
    {
        return [
            'pendiente'  => $tickets->where('Estatus', 'Pendiente')->values(),
            'en_proceso' => $tickets->where('Estatus', 'En proceso')->values(),
            'pausado'    => $tickets->where('Estatus', 'Pausado')->values(),
            'atendido'   => $tickets->where('Estatus', 'Atendido')->values(),
            'cancelado'  => $tickets->where('Estatus', 'Cancelado')->values(),
        ];
    }

    public const CATEGORIAS = [
        'Plomería',
        'Electricidad',
        'Limpieza',
        'Instalaciones',
        'Adquisiciones de equipo/material',
        'Mobiliario',
        'Aire acondicionado',
    ];

    public static function obtenerResponsables(): array
    {
        $empleado = Empleados::query()
            ->join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
            ->where('obras.NombreObra', 'like', '%Compras%')
            ->where('empleados.Estado', 1)
            ->where('empleados.NombreEmpleado', 'like', '%ORDOÑEZ%')
            ->where('empleados.NombreEmpleado', 'like', '%LUIS%')
            ->select('empleados.NombreEmpleado')
            ->first();

        if (!$empleado) {
            return ['LOA' => 'Luis Ordoñez (Compras)'];
        }

        return [
            $empleado->NombreEmpleado => self::formatearNombreResponsable($empleado->NombreEmpleado),
        ];
    }

    public static function formatearNombreResponsable(?string $nombreCompleto): string
    {
        if (!$nombreCompleto) {
            return '';
        }

        $partes = preg_split('/\s+/', trim($nombreCompleto));

        if (count($partes) >= 3) {
            $apellido = mb_convert_case($partes[0], MB_CASE_TITLE, 'UTF-8');
            $nombre = mb_convert_case($partes[2], MB_CASE_TITLE, 'UTF-8');

            return "{$nombre} {$apellido} (Compras)";
        }

        return $nombreCompleto;
    }

    public static function normalizarResponsable(?string $responsable): ?string
    {
        if ($responsable === 'LOA') {
            $responsables = self::obtenerResponsables();

            return array_key_first($responsables) ?: 'LOA';
        }

        return $responsable;
    }

    public function getIdAttribute(): int
    {
        return $this->MantenimientoID;
    }

    public function getEstadoAttribute(): string
    {
        return $this->Estatus ?? '';
    }

    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'EmpleadoID', 'EmpleadoID');
    }

    public function chat()
    {
        return $this->hasMany(MantenimientoChat::class, 'mantenimiento_id', 'MantenimientoID')->orderBy('created_at', 'asc');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($ticket) {
            if (!$ticket->isDirty('Estatus')) {
                return;
            }

            $nuevoEstatus = $ticket->Estatus;

            if ($nuevoEstatus === 'En proceso' && !$ticket->FechaInicioProgreso) {
                $ticket->FechaInicioProgreso = now();
            }

            if (in_array($nuevoEstatus, ['Atendido', 'Cancelado'], true) && $ticket->FechaInicioProgreso && !$ticket->FechaFinProgreso) {
                $ticket->FechaFinProgreso = now();
            }
        });
    }

    public function getTiempoRespuestaAttribute(): ?float
    {
        if (!$this->FechaInicioProgreso) {
            return null;
        }

        return $this->calcularHorasLaborales($this->created_at, $this->FechaInicioProgreso);
    }

    public function getTiempoResolucionAttribute(): ?float
    {
        if (!$this->FechaInicioProgreso || !$this->FechaFinProgreso) {
            return null;
        }

        return $this->calcularHorasLaborales($this->FechaInicioProgreso, $this->FechaFinProgreso);
    }

    public static function slaConfig(?string $prioridad): ?array
    {
        return self::SLA_PRIORIDAD[$prioridad] ?? null;
    }

    public function horasSlaMin(): ?float
    {
        $config = self::slaConfig($this->Prioridad);

        return $config ? $config['min_dias'] * self::HORAS_LABORALES_DIA : null;
    }

    public function horasSlaMax(): ?float
    {
        $config = self::slaConfig($this->Prioridad);

        return $config ? $config['max_dias'] * self::HORAS_LABORALES_DIA : null;
    }

    public function tiempoTranscurridoLaboral(?\Carbon\Carbon $hasta = null): float
    {
        if (!$this->FechaInicioProgreso) {
            return 0;
        }

        $fin = $hasta ?? now();

        return $this->calcularHorasLaborales($this->FechaInicioProgreso, $fin);
    }

    public function evaluarSla(?\Carbon\Carbon $referencia = null): ?array
    {
        if (empty($this->Prioridad) || !$this->FechaInicioProgreso || $this->Estatus === 'Pendiente') {
            return null;
        }

        $config = self::slaConfig($this->Prioridad);

        if (!$config) {
            return null;
        }

        $referencia = $referencia ?? now();
        $minHoras = $config['min_dias'] * self::HORAS_LABORALES_DIA;
        $maxHoras = $config['max_dias'] * self::HORAS_LABORALES_DIA;
        $cerrado = self::esFinalizado($this->Estatus ?? '');

        if ($this->Estatus === 'Cancelado') {
            return [
                'prioridad'                  => $this->Prioridad,
                'min_dias'                   => $config['min_dias'],
                'max_dias'                   => $config['max_dias'],
                'min_horas'                  => $minHoras,
                'max_horas'                  => $maxHoras,
                'horas_transcurridas'        => 0,
                'dias_laborales_transcurridos' => 0,
                'estado_sla'                 => 'cancelado',
                'porcentaje_uso'             => 0,
                'meta_texto'                 => "{$config['min_dias']}-{$config['max_dias']} días laborales",
            ];
        }

        $fechaFin = ($cerrado && $this->FechaFinProgreso)
            ? $this->FechaFinProgreso
            : $referencia;

        $horas = $this->calcularHorasLaborales($this->FechaInicioProgreso, $fechaFin);
        $diasLaborales = round($horas / self::HORAS_LABORALES_DIA, 1);
        $porcentajeUso = $maxHoras > 0 ? round(min(100, ($horas / $maxHoras) * 100), 1) : 0;

        if ($cerrado) {
            $estadoSla = $horas <= $maxHoras ? 'cumplido' : 'incumplido';
        } elseif ($horas > $maxHoras) {
            $estadoSla = 'vencido';
        } elseif ($horas >= $minHoras) {
            $estadoSla = 'en_riesgo';
        } else {
            $estadoSla = 'en_tiempo';
        }

        return [
            'prioridad'                    => $this->Prioridad,
            'min_dias'                     => $config['min_dias'],
            'max_dias'                     => $config['max_dias'],
            'min_horas'                    => $minHoras,
            'max_horas'                    => $maxHoras,
            'horas_transcurridas'          => $horas,
            'dias_laborales_transcurridos' => $diasLaborales,
            'estado_sla'                   => $estadoSla,
            'porcentaje_uso'               => $porcentajeUso,
            'meta_texto'                   => "{$config['min_dias']}-{$config['max_dias']} días laborales",
        ];
    }

    public function resumenSlaTarjeta(): ?array
    {
        $sla = $this->evaluarSla();

        if (!$sla || $sla['estado_sla'] === 'cancelado') {
            return null;
        }

        $horasRestantes = max(0, $sla['max_horas'] - $sla['horas_transcurridas']);
        $diasRestantes = round($horasRestantes / self::HORAS_LABORALES_DIA, 1);
        $horasExcedidas = max(0, $sla['horas_transcurridas'] - $sla['max_horas']);
        $diasExcedidos = round($horasExcedidas / self::HORAS_LABORALES_DIA, 1);
        $estado = $sla['estado_sla'];

        if (in_array($estado, ['cumplido', 'incumplido'], true)) {
            $textoRestante = $estado === 'cumplido'
                ? 'Atendido dentro de meta'
                : 'Atendido fuera de meta (+' . self::formatearDuracionSlaLaboral($diasExcedidos) . ')';
        } elseif ($estado === 'vencido') {
            $textoRestante = 'Vencido hace ' . self::formatearDuracionSlaLaboral($diasExcedidos);
        } else {
            $textoRestante = 'Restan ' . self::formatearDuracionSlaLaboral($diasRestantes);
        }

        $estilos = [
            'en_tiempo'   => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'text' => 'text-blue-700 dark:text-blue-300', 'border' => 'border-blue-100 dark:border-blue-800', 'bar' => 'bg-blue-500'],
            'en_riesgo'   => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'text' => 'text-yellow-700 dark:text-yellow-300', 'border' => 'border-yellow-100 dark:border-yellow-800', 'bar' => 'bg-yellow-500'],
            'vencido'     => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'text' => 'text-red-700 dark:text-red-300', 'border' => 'border-red-100 dark:border-red-800', 'bar' => 'bg-red-500'],
            'cumplido'    => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'text' => 'text-green-700 dark:text-green-300', 'border' => 'border-green-100 dark:border-green-800', 'bar' => 'bg-green-500'],
            'incumplido'  => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'text' => 'text-red-700 dark:text-red-300', 'border' => 'border-red-100 dark:border-red-800', 'bar' => 'bg-red-500'],
        ];

        $style = $estilos[$estado] ?? $estilos['en_tiempo'];

        return [
            'estado_sla'        => $estado,
            'prioridad'         => $this->Prioridad,
            'meta_texto'        => $sla['meta_texto'],
            'max_dias'          => $sla['max_dias'],
            'transcurrido_dias' => $sla['dias_laborales_transcurridos'],
            'restante_dias'     => $diasRestantes,
            'porcentaje_uso'    => $sla['porcentaje_uso'],
            'texto_transcurrido' => self::formatearDuracionSlaLaboral($sla['dias_laborales_transcurridos']) . ' de ' . $sla['max_dias'] . ' días',
            'texto_restante'    => $textoRestante,
            'estilo'            => $style,
        ];
    }

    public static function formatearDuracionSlaLaboral(float $dias): string
    {
        if ($dias >= 1) {
            $entero = (int) floor($dias);
            $decimal = round($dias - $entero, 1);

            if ($decimal >= 0.1) {
                return rtrim(rtrim(number_format($dias, 1, '.', ''), '0'), '.') . ' días';
            }

            return $entero . ($entero === 1 ? ' día' : ' días');
        }

        $horas = max(1, (int) round($dias * self::HORAS_LABORALES_DIA));

        return $horas . ($horas === 1 ? ' hora' : ' horas');
    }

    public static function formatearTicketParaVista(TicketMantenimiento $ticket): array
    {
        return [
            'id'          => $ticket->MantenimientoID,
            'asunto'      => $ticket->Asunto,
            'descripcion' => $ticket->Descripcion,
            'prioridad'   => $ticket->Prioridad,
            'estatus'     => $ticket->Estatus,
            'categoria'   => $ticket->Categoria,
            'responsable' => $ticket->Responsable,
            'solicitante' => $ticket->NombreSolicitante,
            'correo'      => $ticket->Correo,
            'area'        => $ticket->AreaDepartamento,
            'imagen'      => $ticket->imagen,
            'created_at'  => optional($ticket->created_at)->toIso8601String(),
            'sla'         => $ticket->resumenSlaTarjeta(),
        ];
    }

    public static function calcularMetricasSla($tickets): array
    {
        $porPrioridad = [];
        $ticketsCriticos = [];
        $resumen = [
            'total_evaluados' => 0,
            'cumplidos'       => 0,
            'incumplidos'     => 0,
            'en_tiempo'       => 0,
            'en_riesgo'       => 0,
            'vencidos'        => 0,
            'cancelados'      => 0,
        ];

        foreach (self::PRIORIDADES as $prioridad) {
            $config = self::SLA_PRIORIDAD[$prioridad];
            $minHorasMeta = $config['min_dias'] * self::HORAS_LABORALES_DIA;
            $maxHorasMeta = $config['max_dias'] * self::HORAS_LABORALES_DIA;

            $porPrioridad[$prioridad] = [
                'prioridad'       => $prioridad,
                'meta'            => "{$config['min_dias']}-{$config['max_dias']} días",
                'meta_horas'      => "{$minHorasMeta}-{$maxHorasMeta} h",
                'color'           => $config['color'],
                'total'           => 0,
                'atendidos'       => 0,
                'abiertos'        => 0,
                'cumplidos'       => 0,
                'incumplidos'     => 0,
                'en_tiempo'       => 0,
                'en_riesgo'       => 0,
                'vencidos'        => 0,
                'cancelados'      => 0,
                'pct_cumplimiento' => 0,
                'tiempo_promedio_dias' => 0,
            ];
        }

        $sumaDiasAtendidos = [];

        foreach ($tickets as $ticket) {
            $sla = $ticket->evaluarSla();

            if (!$sla || !isset($porPrioridad[$ticket->Prioridad])) {
                continue;
            }

            $p = &$porPrioridad[$ticket->Prioridad];
            $p['total']++;
            $resumen['total_evaluados']++;

            $estado = $sla['estado_sla'];

            if ($estado === 'cancelado') {
                $p['cancelados']++;
                $resumen['cancelados']++;
                continue;
            }

            if ($ticket->Estatus === 'Atendido') {
                $p['atendidos']++;
                $sumaDiasAtendidos[$ticket->Prioridad][] = $sla['dias_laborales_transcurridos'];

                if ($estado === 'cumplido') {
                    $p['cumplidos']++;
                    $resumen['cumplidos']++;
                } else {
                    $p['incumplidos']++;
                    $resumen['incumplidos']++;
                }
            } else {
                $p['abiertos']++;

                if ($estado === 'en_tiempo') {
                    $p['en_tiempo']++;
                    $resumen['en_tiempo']++;
                } elseif ($estado === 'en_riesgo') {
                    $p['en_riesgo']++;
                    $resumen['en_riesgo']++;
                    $ticketsCriticos[] = self::formatearTicketSla($ticket, $sla);
                } else {
                    $p['vencidos']++;
                    $resumen['vencidos']++;
                    $ticketsCriticos[] = self::formatearTicketSla($ticket, $sla);
                }
            }
        }

        foreach ($porPrioridad as $prioridad => &$data) {
            $cerradosEvaluables = $data['cumplidos'] + $data['incumplidos'];
            $data['pct_cumplimiento'] = $cerradosEvaluables > 0
                ? round(($data['cumplidos'] / $cerradosEvaluables) * 100, 1)
                : 0;

            if (!empty($sumaDiasAtendidos[$prioridad])) {
                $data['tiempo_promedio_dias'] = round(
                    array_sum($sumaDiasAtendidos[$prioridad]) / count($sumaDiasAtendidos[$prioridad]),
                    1
                );
            }
        }
        unset($data);

        usort($ticketsCriticos, fn ($a, $b) => $b['porcentaje_uso'] <=> $a['porcentaje_uso']);

        $cerradosGlobal = $resumen['cumplidos'] + $resumen['incumplidos'];

        return [
            'horario'            => 'Lunes a Viernes, 9:00 - 18:00',
            'horas_por_dia'      => self::HORAS_LABORALES_DIA,
            'resumen'            => $resumen,
            'pct_cumplimiento'   => $cerradosGlobal > 0
                ? round(($resumen['cumplidos'] / $cerradosGlobal) * 100, 1)
                : 0,
            'por_prioridad'      => array_values($porPrioridad),
            'tickets_criticos'   => array_slice($ticketsCriticos, 0, 20),
        ];
    }

    protected static function formatearTicketSla(self $ticket, array $sla): array
    {
        return [
            'id'                           => $ticket->MantenimientoID,
            'asunto'                       => $ticket->Asunto,
            'prioridad'                    => $ticket->Prioridad,
            'estatus'                      => $ticket->Estatus,
            'estado_sla'                   => $sla['estado_sla'],
            'meta_texto'                   => $sla['meta_texto'],
            'dias_laborales_transcurridos' => $sla['dias_laborales_transcurridos'],
            'porcentaje_uso'               => $sla['porcentaje_uso'],
            'created_at'                   => optional($ticket->created_at)->format('d/m/Y H:i'),
        ];
    }

    protected function calcularHorasLaborales($fechaInicio, $fechaFin): float
    {
        if (!$fechaInicio || !$fechaFin || $fechaFin->lt($fechaInicio)) {
            return 0;
        }

        $horas = 0;
        $actual = $this->normalizarFechaInicio($fechaInicio->copy());
        $fin = $fechaFin->copy();

        if ($actual->gte($fin)) {
            return 0;
        }

        while ($actual->lt($fin)) {
            $diaSemana = $actual->dayOfWeek;

            if ($diaSemana === 0) {
                $actual->addDay()->setTime(self::HORA_INICIO_LABORAL, 0, 0);
                continue;
            }

            if ($diaSemana === 6) {
                $actual->addDays(2)->setTime(self::HORA_INICIO_LABORAL, 0, 0);
                continue;
            }

            if ($actual->hour < self::HORA_INICIO_LABORAL) {
                $actual->setTime(self::HORA_INICIO_LABORAL, 0, 0);
            }

            $finDelDia = $actual->copy()->setTime(self::HORA_FIN_LABORAL, 0, 0);

            if ($actual->isSameDay($fin)) {
                $finCalculo = $fin->lt($finDelDia) ? $fin : $finDelDia;
                if ($finCalculo->lte($actual)) {
                    break;
                }
                $horas += $actual->diffInSeconds($finCalculo) / 3600;
                break;
            }

            $horas += $actual->diffInSeconds($finDelDia) / 3600;

            $actual->addDay()->setTime(self::HORA_INICIO_LABORAL, 0, 0);
        }

        return round($horas, 2);
    }

    protected function normalizarFechaInicio($fecha)
    {
        $fecha = $fecha->copy();
        $diaSemana = $fecha->dayOfWeek;
        $hora = $fecha->hour;

        if ($diaSemana === 0) {
            return $fecha->addDay()->setTime(self::HORA_INICIO_LABORAL, 0, 0);
        }

        if ($diaSemana === 6) {
            return $fecha->addDays(2)->setTime(self::HORA_INICIO_LABORAL, 0, 0);
        }

        if ($hora < self::HORA_INICIO_LABORAL) {
            return $fecha->setTime(self::HORA_INICIO_LABORAL, 0, 0);
        }

        if ($hora >= self::HORA_FIN_LABORAL) {
            return $fecha->addDay()->setTime(self::HORA_INICIO_LABORAL, 0, 0);
        }

        return $fecha;
    }
}
