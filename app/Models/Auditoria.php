<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cabecera de una corrida de auditoría: quién la generó, a qué empleado se auditó y
 * con qué alcance.
 *
 * La corrida es la visita a un resguardante, no a una máquina: cubre TODOS sus equipos
 * y las licencias elegidas. El tipo de persona y la gerencia se leen del empleado por
 * relación, no se copian aquí. El detalle congelado vive en auditorias_equipos
 * (una fila por equipo) y auditorias_licencias (una por licencia).
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
        'tipoEquipo',
        'licencias_auditadas',
        'total_licencias_auditadas',
    ];

    protected $casts = [
        'EmpleadoID'          => 'integer',
        'tipoEquipo'          => 'integer',
        'licencias_auditadas' => 'array',
    ];

    /** El empleado auditado: de aquí salen tipo_persona, puesto y gerencia. */
    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'EmpleadoID', 'EmpleadoID');
    }

    /**
     * Las corridas anteriores a la selección de licencias no guardan lista: en ellas
     * se revisó todo el catálogo.
     */
    public function auditoTodasLasLicencias(): bool
    {
        return $this->licencias_auditadas === null;
    }

    /** Los equipos que resguardaba el empleado al generarse la corrida. */
    public function equipos()
    {
        return $this->hasMany(AuditoriaEquipo::class, 'auditoria_id');
    }

    /** Las licencias revisadas en la corrida. */
    public function licencias()
    {
        return $this->hasMany(AuditoriaLicencia::class, 'auditoria_id');
    }
}
