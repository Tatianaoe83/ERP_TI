<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Detalle congelado de una corrida: una fila por LICENCIA del empleado auditado.
 *
 * La licencia es del resguardante, no del equipo, así que no se repite por cada
 * máquina: una sola fila por producto, aunque el empleado tenga tres computadoras.
 */
class AuditoriaLicencia extends Model
{
    use HasFactory;

    public $table = 'auditorias_licencias';

    protected $fillable = [
        'auditoria_id',
        'NombreLicencia',
        'tiene_licencia',
        'original',
        'observaciones',
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
