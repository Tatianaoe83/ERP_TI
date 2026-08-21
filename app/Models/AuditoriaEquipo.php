<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Detalle congelado de un equipo dentro de una corrida de auditoría. Guarda copia de
 * los datos del equipo para que el reporte siga siendo legible aunque el equipo se
 * reasigne, se transfiera o se dé de baja después.
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
        'licencias',
        'licencias_piratas',
    ];

    protected $casts = [
        'tipoEquipo'        => 'integer',
        'grupo'             => 'integer',
        'licencias_piratas' => 'integer',
    ];

    public function auditoria()
    {
        return $this->belongsTo(Auditoria::class, 'auditoria_id');
    }

    /** Las licencias se guardan como lista separada por " | " para leerlas directo. */
    public function listaLicencias(): array
    {
        return array_filter(array_map('trim', explode('|', (string) $this->licencias)));
    }
}
