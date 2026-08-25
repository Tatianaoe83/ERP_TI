<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Detalle congelado de una corrida: una fila por EQUIPO que el empleado resguardaba
 * al generarla.
 *
 * Sólo guarda cuál equipo era y qué se encontró. Marca, modelo, serie y folio se leen
 * del inventario en vivo por relación, así que nunca se desfasan de la ficha actual.
 */
class AuditoriaEquipo extends Model
{
    use HasFactory;

    public $table = 'auditorias_equipos';

    protected $fillable = [
        'auditoria_id',
        'InventarioID',
        'presente',
        'observaciones',
    ];

    /**
     * "presente" es tri-estado: true está, false no apareció, null sin revisar.
     * Por eso no lleva cast a boolean, que convertiría el null en false.
     */
    protected $casts = [
        'InventarioID' => 'integer',
    ];

    public function auditoria()
    {
        return $this->belongsTo(Auditoria::class, 'auditoria_id');
    }

    /** La ficha del equipo, leída del inventario en vivo. */
    public function equipo()
    {
        return $this->belongsTo(InventarioEquipo::class, 'InventarioID', 'InventarioID');
    }
}
