<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Solicitud
 * @package App\Models
 * @version August 15, 2025, 5:06 pm UTC
 *
 * @property \App\Models\Obra $obraid
 * @property \App\Models\Empleado $empleadoid
 * @property \App\Models\Proyecto $proyectoid
 * @property \App\Models\Gerencium $gerenciaid
 * @property \App\Models\Puesto $puestoid
 * @property string|\Carbon\Carbon $FechaSolicitud
 * @property string $Supervisor
 * @property string $Motivo
 * @property string $DescripcionMotivo
 * @property string $Requerimentos
 * @property number $Presupuesto
 * @property string $Estatus
 * @property integer $ObraID
 * @property integer $GerenciaID
 * @property integer $PuestoID
 * @property integer $EmpleadoID
 * @property integer $ProyectoID
 */
class Solicitud extends Model
{
    use SoftDeletes;


    public $table = 'solicitudes';
    protected $primaryKey = 'SolicitudID';


    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'Motivo',
        'DescripcionMotivo',
        'Requerimientos',
        'Presupuesto',
        'Estatus',
        'ObraID',
        'GerenciaID',
        'PuestoID',
        'EmpleadoID',
        'Proyecto',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'SolicitudID' => 'integer',
        'Motivo' => 'string',
        'DescripcionMotivo' => 'string',
        'Requerimientos' => 'string',
        'Presupuesto' => 'float',
        'Estatus' => 'string',
        'ObraID' => 'integer',
        'GerenciaID' => 'integer',
        'PuestoID' => 'integer',
        'EmpleadoID' => 'integer',
        'Proyecto' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'Motivo' => 'nullable|string',
        'DescripcionMotivo' => 'required|string',
        'Requerimientos' => 'required|string',
        'Presupuesto' => 'nullable|numeric',
        'Estatus' => 'required|string',
        'ObraID' => 'required|integer',
        'GerenciaID' => 'required|integer',
        'PuestoID' => 'required|integer',
        'EmpleadoID' => 'required|integer',
        'Proyecto' => 'required|string',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function obraid()
    {
        return $this->belongsTo(\App\Models\Obras::class, 'ObraID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function empleadoid()
    {
        return $this->belongsTo(\App\Models\Empleados::class, 'EmpleadoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function gerenciaid()
    {
        return $this->belongsTo(\App\Models\Gerencia::class, 'GerenciaID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function puestoid()
    {
        return $this->belongsTo(\App\Models\Puestos::class, 'PuestoID');
    }

    /**
     * Relación con cotizaciones
     */
    public function cotizaciones()
    {
        return $this->hasMany(\App\Models\Cotizacion::class, 'SolicitudID', 'SolicitudID');
    }

    /**
     * Indica si todos los productos (agrupados por NumeroPropuesta / NombreEquipo) tienen exactamente un ganador (Estatus Seleccionada).
     * Se usa para saber si la solicitud está totalmente aprobada (un ganador por producto).
     */
    public function todosProductosTienenGanador(): bool
    {
        $cotizaciones = $this->cotizaciones ?? collect();
        if ($cotizaciones->isEmpty()) {
            return false;
        }
        $porProducto = [];
        foreach ($cotizaciones as $c) {
            $clave = 'np_' . (int) ($c->NumeroPropuesta ?? 0);
            if (! isset($porProducto[$clave])) {
                $porProducto[$clave] = 0;
            }
            if ($c->Estatus === 'Seleccionada') {
                $porProducto[$clave]++;
            }
        }
        foreach ($porProducto as $count) {
            if ($count !== 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Relación con pasos de aprobación
     */
    public function pasosAprobacion()
    {
        return $this->hasMany(\App\Models\SolicitudPasos::class, 'solicitud_id', 'SolicitudID');
    }

    /**
     * Obtener el paso de aprobación del supervisor
     */
    public function pasoSupervisor()
    {
        return $this->hasOne(\App\Models\SolicitudPasos::class, 'solicitud_id', 'SolicitudID')
            ->where('stage', 'supervisor');
    }

    /**
     * Obtener el paso de aprobación de gerencia
     */
    public function pasoGerencia()
    {
        return $this->hasOne(\App\Models\SolicitudPasos::class, 'solicitud_id', 'SolicitudID')
            ->where('stage', 'gerencia');
    }

    /**
     * Obtener el paso de aprobación de administración
     */
    public function pasoAdministracion()
    {
        return $this->hasOne(\App\Models\SolicitudPasos::class, 'solicitud_id', 'SolicitudID')
            ->where('stage', 'administracion');
    }
}
