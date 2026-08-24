<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Detalle congelado de una corrida: una fila por LICENCIA del empleado auditado.
 * Sin licencias queda una sola fila con NombreLicencia nulo y tiene_licencia en false.
 *
 * No copia datos del equipo ni del empleado: se leen del empleado de la cabecera por
 * relación, así que nunca se desfasan de lo que hay en el inventario.
 */
class AuditoriaEquipo extends Model
{
    use HasFactory;

    public $table = 'auditorias_equipos';

    protected $fillable = [
        'auditoria_id',
        'NombreLicencia',
        'tiene_licencia',
        'original',
    ];

    /**
     * "original" es tri-estado: true original, false no original, null sin revisar.
     * Por eso no lleva cast a boolean, que convertiría el null en false.
     */
    protected $casts = [
        'tiene_licencia' => 'boolean',
    ];

    public function auditoria()
    {
        return $this->belongsTo(Auditoria::class, 'auditoria_id');
    }
}
