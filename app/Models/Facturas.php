<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Facturas
 * @package App\Models
 */
class Facturas extends Model
{
    use SoftDeletes;

    public $table = 'facturas';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['deleted_at'];

    public $fillable = [
        'Nombre',
        'SolicitudID',
        'Importe',
        'Costo',
        'Mes',
        'Anio',
        'InsumoID',
        'ArchivoRuta',  // ← XML
        'PdfRuta',      // ← PDF
        'UUID',
        'Emisor',
    ];

    protected $casts = [
        'FacturasID'  => 'integer',
        'Nombre'      => 'string',
        'SolicitudID' => 'integer',
        'Importe'     => 'decimal:2',
        'Costo'       => 'decimal:2',
        'Mes'         => 'integer',   // tinyint(3) — número 1-12
        'Anio'        => 'integer',   // smallint(5)
        'InsumoID'    => 'integer',
        'ArchivoRuta' => 'string',
        'PdfRuta'     => 'string',
        'UUID'        => 'string',
        'Emisor'      => 'string',
    ];

    public static $rules = [
        'Nombre'      => 'nullable|string|max:300',
        'SolicitudID' => 'nullable|integer',
        'Importe'     => 'nullable|numeric',
        'Costo'       => 'nullable|numeric',
        'Mes'         => 'nullable|integer|min:1|max:12',
        'Anio'        => 'nullable|integer',
        'InsumoID'    => 'nullable|integer',
        'ArchivoRuta' => 'nullable|string|max:500',
        'PdfRuta'     => 'nullable|string|max:500',
        'UUID'        => 'nullable|string|max:36',
        'Emisor'      => 'nullable|string|max:300',
    ];

    public function insumoid()
    {
        return $this->belongsTo(\App\Models\Insumo::class, 'InsumoID');
    }

    public function solicitud()
    {
        return $this->belongsTo(\App\Models\Solicitud::class, 'SolicitudID');
    }
}