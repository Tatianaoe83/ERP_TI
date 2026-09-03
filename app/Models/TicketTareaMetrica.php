<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketTareaMetrica extends Model
{
    protected $table = 'ticket_tarea_metricas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'dia_compromiso',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'dia_compromiso' => 'integer',
    ];

    public function tareas()
    {
        return $this->hasMany(TicketTarea::class, 'metrica_id');
    }
}
