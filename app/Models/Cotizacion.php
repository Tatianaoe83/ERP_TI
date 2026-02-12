<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $primaryKey = 'CotizacionID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $table = 'cotizaciones';

    public $fillable = [
        'SolicitudID',
        'Proveedor',
        'Descripcion',
        'Precio',
        'TiempoEntrega',
        'Observaciones',
        'Estatus',
        'NumeroPropuesta',
        'NombreEquipo',
        'NumeroProducto',
        'NumeroParte',
        'Cantidad',
        'Unidad',
        'CostoEnvio'
    ];

    protected $casts = [
        'CotizacionID' => 'integer',
        'SolicitudID' => 'integer',
        'Precio' => 'decimal:2',
        'TiempoEntrega' => 'integer',
        'NumeroPropuesta' => 'integer',
        'Cantidad' => 'integer',
        'CostoEnvio' => 'decimal:2',
        'NumeroProducto' => 'integer',
    ];

    /**
     * Relación con solicitud
     */
    public function solicitud()
    {
        return $this->belongsTo(\App\Models\Solicitud::class, 'SolicitudID', 'SolicitudID');
    }
}
