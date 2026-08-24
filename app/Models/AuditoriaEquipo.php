<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Detalle congelado de una corrida: una fila por LICENCIA de cada equipo auditado.
 * Un equipo con tres licencias deja tres filas; uno sin ninguna deja una sola con
 * NombreLicencia nulo y tiene_licencia en false.
 *
 * Guarda copia de los datos del equipo para que el reporte siga siendo legible
 * aunque el equipo se reasigne, se transfiera o se dé de baja después.
 */
class AuditoriaEquipo extends Model
{
    use HasFactory;

    public $table = 'auditorias_equipos';

    /** Grupos de la columna "grupo". */
    public const GRUPO_LAPTOP = 0;
    public const GRUPO_PC     = 1;
    public const GRUPO_OTROS  = 2;

    protected $fillable = [
        'auditoria_id',
        'InventarioID',
        'CategoriaEquipo',
        'Marca',
        'Modelo',
        'NumSerie',
        'Folio',
        'GerenciaEquipo',
        'NombreEmpleado',
        'tipoEquipo',
        'grupo',
        'NombreLicencia',
        'tiene_licencia',
        'pirata',
        'en_dominio',
    ];

    protected $casts = [
        'tipoEquipo'     => 'integer',
        'grupo'          => 'integer',
        'tiene_licencia' => 'boolean',
        'pirata'         => 'boolean',
        'en_dominio'     => 'boolean',
    ];

    public function auditoria()
    {
        return $this->belongsTo(Auditoria::class, 'auditoria_id');
    }
}
