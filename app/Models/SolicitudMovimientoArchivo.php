<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMovimientoArchivo extends Model
{
    protected $table = 'solicitud_movimiento_archivos';

    protected $primaryKey = 'ArchivoID';

    public $fillable = [
        'SolicitudMovimientoID',
        'Ruta',
        'Nombre',
        'Tipo',
    ];
}
