<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketTareaHistorial extends Model
{
    protected $table = 'ticket_tarea_historial';

    protected $fillable = [
        'tarea_id',
        'user_id',
        'accion',
        'motivo',
        'fecha_compromiso_anterior',
        'fecha_compromiso_nueva',
        'asignado_anterior_id',
        'asignado_nuevo_id',
        'notas',
    ];

    protected $casts = [
        'fecha_compromiso_anterior' => 'date',
        'fecha_compromiso_nueva' => 'date',
    ];

    public function tarea()
    {
        return $this->belongsTo(TicketTarea::class, 'tarea_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asignadoAnterior()
    {
        return $this->belongsTo(Empleados::class, 'asignado_anterior_id', 'EmpleadoID');
    }

    public function asignadoNuevo()
    {
        return $this->belongsTo(Empleados::class, 'asignado_nuevo_id', 'EmpleadoID');
    }
}
