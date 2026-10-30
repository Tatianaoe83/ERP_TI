<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMovimientoLinea extends Model
{
    protected $table = 'solicitud_movimiento_lineas';

    protected $primaryKey = 'LineaID';

    public $fillable = [
        'SolicitudMovimientoID',
        'Orden',
        'CargoID',
        'CodigoInsumo',
        'Descripcion',
        'Partida',
        'Cantidad',
        'Precio',
        'Importe',
        'Justificacion',
    ];

    public function cargo()
    {
        return $this->belongsTo(CargoMovimiento::class, 'CargoID', 'CargoID');
    }
}
