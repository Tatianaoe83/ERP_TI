<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Inventario
 * @package App\Models
 * @version January 29, 2025, 4:51 pm UTC
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
class Inventario extends Model
{
    use HasFactory, SoftDeletes;


    public $table = 'empleados';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'EmpleadoID';

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
        'Estado' => 'required|boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function obraid()
    {
        return $this->belongsTo(\App\Models\Obra::class, 'ObraID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function puestoid()
    {
        return $this->belongsTo(\App\Models\Puesto::class, 'PuestoID');
    }

    public function lineas()
    {
        return $this->belongsTo(LineasTelefonicas::class, 'LineaID');
    }
}
