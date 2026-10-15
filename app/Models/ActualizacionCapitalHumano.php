<?php

namespace App\Models;

use Eloquent as Model;

class ActualizacionCapitalHumano extends Model
{
    public $table = 'actualizaciones_capital_humano';

    public $fillable = [
        'origen',
        'origen_id',
        'tipo',
        'empleado_ti_id',
        'nombre',
        'fecha',
        'resumen',
        'estado',
    ];

    protected $casts = [
        'origen_id' => 'integer',
        'empleado_ti_id' => 'integer',
        'fecha' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'empleado_ti_id', 'EmpleadoID');
    }
}
