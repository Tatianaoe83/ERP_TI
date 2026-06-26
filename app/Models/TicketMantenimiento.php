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
                $actual->addDay()->setTime(9, 0, 0);
                continue;
            }

            $horaFin = ($diaSemana === 6) ? 14 : 18;

            if ($actual->hour < 9) {
                $actual->setTime(9, 0, 0);
            }

            $finDelDia = $actual->copy()->setTime($horaFin, 0, 0);

            if ($actual->isSameDay($fin)) {
                $finCalculo = $fin->lt($finDelDia) ? $fin : $finDelDia;
                if ($finCalculo->lte($actual)) {
                    break;
                }
                $horas += $actual->diffInSeconds($finCalculo) / 3600;
                break;
            }

            $horas += $actual->diffInSeconds($finDelDia) / 3600;

            if ($diaSemana === 6) {
                $actual->addDays(2)->setTime(9, 0, 0);
            } else {
                $actual->addDay()->setTime(9, 0, 0);
            }
        }

        return round($horas, 2);
    }

    protected function normalizarFechaInicio($fecha)
    {
        $fecha = $fecha->copy();
        $diaSemana = $fecha->dayOfWeek;
        $hora = $fecha->hour;

        if ($diaSemana === 0) {
            return $fecha->addDay()->setTime(9, 0, 0);
        }

        $horaFin = ($diaSemana === 6) ? 14 : 18;

        if ($hora < 9) {
            return $fecha->setTime(9, 0, 0);
        }

        if ($hora >= $horaFin) {
            return $diaSemana === 6
                ? $fecha->addDays(2)->setTime(9, 0, 0)
                : $fecha->addDay()->setTime(9, 0, 0);
        }

        return $fecha;
    }
}
