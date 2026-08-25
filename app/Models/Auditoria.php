<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cabecera de una corrida de auditoría: quién la generó, a qué empleado se auditó y
 * con qué alcance. El tipo de persona y la gerencia se leen del empleado por relación,
 * no se copian aquí. El detalle congelado vive en auditorias_equipos.
 */
class Auditoria extends Model
{
    use HasFactory;

    public $table = 'auditorias';

    protected $fillable = [
        'Folio',
        'id_empleado',
        'generada_por_nombre',
        'EmpleadoID',
        'InventarioID',
        'tipoEquipo',
        'licencias_auditadas',
        'total_licencias_auditadas',
    ];

    protected $casts = [
        'EmpleadoID'          => 'integer',
        'InventarioID'        => 'integer',
        'tipoEquipo'          => 'integer',
        'licencias_auditadas' => 'array',
    ];

    /** El empleado auditado: de aquí salen tipo_persona, puesto y gerencia. */
    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'EmpleadoID', 'EmpleadoID');
    }

    /**
     * El equipo que se revisó en esta visita. Sus datos (marca, modelo, serie,
     * folio) se leen en vivo del inventario: la corrida sólo guarda cuál fue.
     */
    public function equipo()
    {
        return $this->belongsTo(InventarioEquipo::class, 'InventarioID', 'InventarioID');
    }

    /**
     * Las corridas anteriores a la selección de licencias no guardan lista: en ellas
     * se revisó todo el catálogo.
     */
    public function auditoTodasLasLicencias(): bool
    {
        return $this->licencias_auditadas === null;
    }

    public function equipos()
    {
        return $this->hasMany(AuditoriaEquipo::class, 'auditoria_id');
    }
}
