<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class TicketTarea extends Model
{
    public const ESTATUS_PENDIENTE = 'pendiente';
    public const ESTATUS_COMPLETADA = 'completada';
    public const ESTATUS_CANCELADA = 'cancelada';

    public const TIPO_EVENTO = 'evento';
    public const TIPO_METRICA = 'metrica';

    public const PRIORIDAD_NORMAL = 'normal';
    public const PRIORIDAD_CRITICA = 'critica';

    protected $table = 'ticket_tareas';

    protected $fillable = [
        'titulo',
        'razon',
        'asignado_id',
        'asignados_ids',
        'creado_por_user_id',
        'fecha_compromiso',
        'estatus',
        'tipo',
        'metrica_id',
        'periodo_mes',
        'periodo_anio',
        'prioridad',
        'completada_at',
        'notificado_creacion_at',
        'notificado_critica_at',
    ];

    protected $casts = [
        'fecha_compromiso' => 'date',
        'completada_at' => 'datetime',
        'notificado_creacion_at' => 'datetime',
        'notificado_critica_at' => 'datetime',
        'periodo_mes' => 'integer',
        'periodo_anio' => 'integer',
    ];

    protected static function booted(): void
    {
        static::ensureColumnaAsignados();
    }

    /**
     * Columna extra, nullable. No altera filas existentes: esas siguen con asignado_id.
     */
    public static function ensureColumnaAsignados(): void
    {
        static $listo = false;
        if ($listo) {
            return;
        }
        $listo = true;

        if (! Schema::hasTable('ticket_tareas') || Schema::hasColumn('ticket_tareas', 'asignados_ids')) {
            return;
        }

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->string('asignados_ids', 255)->nullable()->after('asignado_id');
        });
    }

    public function asignado()
    {
        return $this->belongsTo(Empleados::class, 'asignado_id', 'EmpleadoID');
    }

    /** IDs de todos los responsables de esta misma tarea (incluye el principal). */
    public function idsAsignados(): array
    {
        $ids = [];
        if ($this->asignado_id) {
            $ids[] = (int) $this->asignado_id;
        }

        $raw = $this->attributes['asignados_ids'] ?? '';
        if (is_string($raw) && $raw !== '') {
            foreach (explode(',', $raw) as $id) {
                $id = (int) trim($id);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public static function serializarAsignados(array $ids): ?string
    {
        $csv = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->implode(',');

        return $csv !== '' ? $csv : null;
    }

    public function etiquetaResponsables($nombresPorId = null): string
    {
        $ids = $this->idsAsignados();
        if ($ids === []) {
            return 'Por asignar';
        }

        $mapa = $nombresPorId instanceof Collection
            ? $nombresPorId
            : collect($nombresPorId ?? []);

        $nombres = collect($ids)->map(function ($id) use ($mapa) {
            return $mapa->get($id)
                ?? $mapa->get((string) $id)
                ?? (((int) $this->asignado_id === (int) $id && $this->asignado)
                    ? $this->asignado->NombreEmpleado
                    : null);
        })->filter()->unique()->values();

        return $nombres->isEmpty() ? 'Por asignar' : $nombres->implode(', ');
    }

    public function tieneResponsable(): bool
    {
        return $this->idsAsignados() !== [];
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por_user_id');
    }

    public function metrica()
    {
        return $this->belongsTo(TicketTareaMetrica::class, 'metrica_id');
    }

    public function historial()
    {
        return $this->hasMany(TicketTareaHistorial::class, 'tarea_id')->orderByDesc('created_at');
    }

    public function scopePendientes($query)
    {
        return $query->where('estatus', self::ESTATUS_PENDIENTE);
    }

    public function scopeAtrasadas($query)
    {
        return $query->pendientes()
            ->whereNotNull('fecha_compromiso')
            ->whereDate('fecha_compromiso', '<', Carbon::today());
    }

    public function scopeProximas($query)
    {
        return $query->pendientes()
            ->whereNotNull('fecha_compromiso')
            ->whereDate('fecha_compromiso', '>', Carbon::today());
    }

    public function scopeDeHoy($query)
    {
        return $query->pendientes()->where(function ($q) {
            $q->whereDate('fecha_compromiso', Carbon::today()->toDateString())
                ->orWhereNull('fecha_compromiso');
        });
    }

    public function estaVencida(): bool
    {
        if ($this->estatus !== self::ESTATUS_PENDIENTE || ! $this->fecha_compromiso) {
            return false;
        }

        return $this->fecha_compromiso->lt(Carbon::today());
    }

    public function esCriticaPorTiempo(): bool
    {
        if ($this->estatus !== self::ESTATUS_PENDIENTE || ! $this->fecha_compromiso) {
            return false;
        }

        return $this->fecha_compromiso->copy()->addDays(2)->lt(Carbon::today());
    }

    public function etiquetaPrioridad(): string
    {
        if ($this->prioridad === self::PRIORIDAD_CRITICA) {
            return 'Crítica';
        }

        if ($this->estaVencida()) {
            return 'Vencida';
        }

        return 'Normal';
    }
}
