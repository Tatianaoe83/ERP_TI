<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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

    public function asignado()
    {
        return $this->belongsTo(Empleados::class, 'asignado_id', 'EmpleadoID');
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
