<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class SolicitudActivo
 * @package App\Models
 * @version February 8, 2026, 10:26 am CST
 *
 * @property \App\Models\Departamento $departamentoid
 * @property \App\Models\Empleado $empleadoid
 * @property \App\Models\Cotizacione $cotizacionid
 * @property \App\Models\Solicitude $solicitudid
 * @property \Illuminate\Database\Eloquent\Collection $departamentoRequerimientos
 * @property integer $SolicitudID
 * @property integer $CotizacionID
 * @property integer $NumeroPropuesta
 * @property integer $UnidadIndex
 * @property string $FacturaPath
 * @property string $FechaEntrega
 * @property integer $EmpleadoID
 * @property integer $DepartamentoID
 */
class SolicitudActivo extends Model
{
    use SoftDeletes;


    public $table = 'solicitud_activos';
    protected $primaryKey = 'SolicitudActivoID';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'SolicitudID',
        'CotizacionID',
        'NumeroPropuesta',
        'UnidadIndex',
        'FacturaPath',
        'FechaEntrega',
        'EmpleadoID',
        'DepartamentoID',
        'serial'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'SolicitudActivoID' => 'integer',
        'SolicitudID' => 'integer',
        'CotizacionID' => 'integer',
        'NumeroPropuesta' => 'integer',
        'UnidadIndex' => 'integer',
        'FacturaPath' => 'string',
        'FechaEntrega' => 'date',
        'EmpleadoID' => 'integer',
        'DepartamentoID' => 'integer',
        'serial' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'SolicitudID' => 'required|integer',
        'CotizacionID' => 'required',
        'NumeroPropuesta' => 'required|integer',
        'UnidadIndex' => 'required|integer',
        'FacturaPath' => 'nullable|string|max:255',
        'FechaEntrega' => 'nullable',
        'EmpleadoID' => 'nullable|integer',
        'DepartamentoID' => 'nullable|integer',
        'created_at' => 'required',
        'updated_at' => 'required',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function departamentos()
    {
        return $this->belongsTo(\App\Models\Departamentos::class, 'DepartamentoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function empleadoAsignado()
    {
        return $this->belongsTo(\App\Models\Empleados::class, 'EmpleadoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function cotizacion()
    {
        return $this->belongsTo(\App\Models\Cotizacion::class, 'CotizacionID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function solicitud()
    {
        return $this->belongsTo(\App\Models\Solicitud::class, 'SolicitudID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function checklists()
    {
        return $this->hasMany(SolicitudActivoCheckList::class, 'SolicitudActivoID', 'SolicitudActivoID');
    }
}
