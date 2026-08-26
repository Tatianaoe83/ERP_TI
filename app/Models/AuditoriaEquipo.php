<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Detalle congelado de una corrida: una fila por EQUIPO que el empleado resguardaba
 * al generarla.
 *
 * Marca, modelo, serie y folio se leen del inventario en vivo por relación mientras
 * el equipo siga ahí (para no desfasarse si se corrige un dato); CategoriaEquipo,
 * Marca, Modelo, NumSerie y Folio también se congelan en esta misma fila para que,
 * si el equipo se borra alguna vez del inventario, la corrida no se quede sin forma
 * de decir cuál era.
 */
class AuditoriaEquipo extends Model
{
    use HasFactory;

    public $table = 'auditorias_equipos';

    protected $fillable = [
        'auditoria_id',
        'InventarioID',
        'CategoriaEquipo',
        'Marca',
        'Modelo',
        'NumSerie',
        'Folio',
        'observaciones',
    ];

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
