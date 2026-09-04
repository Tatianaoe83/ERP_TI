<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketTareaMetrica extends Model
{
    protected $table = 'ticket_tarea_metricas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'creado_por_user_id',
        'dia_compromiso',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'dia_compromiso' => 'integer',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por_user_id');
    }

    public function tareas()
    {
        return $this->hasMany(TicketTarea::class, 'metrica_id');
    }
}
