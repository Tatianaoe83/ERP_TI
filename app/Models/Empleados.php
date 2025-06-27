<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;


/**
 * Class Empleados
 * @package App\Models
 * @version January 24, 2025, 9:39 pm UTC
 *
 * @property \App\Models\Obra $obraid
 * @property \App\Models\Puesto $puestoid
 * @property string $NombreEmpleado
 * @property integer $PuestoID
 * @property integer $ObraID
 * @property string $NumTelefono
 * @property string $Correo
 * @property boolean $Estado
 */
class Empleados extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;


    public $table = 'empleados';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'EmpleadoID';
    protected $keyType = 'int';

    public $fillable = [
        'NombreEmpleado',
        'PuestoID',
        'ObraID',
        'NumTelefono',
        'Correo',
        'Estado'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'EmpleadoID' => 'integer',
        'NombreEmpleado' => 'string',
        'PuestoID' => 'integer',
        'ObraID' => 'integer',
        'NumTelefono' => 'string',
        'Correo' => 'string',
        'Estado' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreEmpleado' => 'required|string|max:100',
        'PuestoID' => 'required|integer',
        'ObraID' => 'required|integer',
        'NumTelefono' => 'required|string|max:50',
        'Correo' => 'required|string|max:150',
        'Estado' => 'required|boolean',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function obras()
    {
        return $this->belongsTo(\App\Models\Obras::class, 'ObraID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function puestos()
    {
        return $this->belongsTo(Puestos::class, 'PuestoID');
    }

    public function inventarioequipo()
    {
        return $this->hasMany(InventarioEquipo::class);
    }

    public function inventarioinsumo()
    {
        return $this->hasMany(InventarioInsumo::class);
    }

    public function inventariolineas()
    {
        return $this->hasMany(InventarioLineas::class);
    }

    public function departamentos()
    {
        return $this->hasOneThrough(
            Departamentos::class,
            Puestos::class,
            'PuestoID',
            'DepartamentoID',
            'PuestoID',
            'DepartamentoID',
        );
    }

    public function gerencia()
    {
        return $this->hasOneThrough(
            Gerencia::class,
            Departamentos::class,
            'DepartamentoID',
            'GerenciaID',
            'DepartamentoID',
            'GerenciaID',
        );
    }

    public function unidadesdenegocio()
    {
        return $this->hasOneThrough(
            UnidadesDeNegocio::class,
            Gerencia::class,
            'GerenciaID',
            'UnidadNegocioID',
            'GerenciaID',
            'UnidadNegocioID',
        );
    }
}
