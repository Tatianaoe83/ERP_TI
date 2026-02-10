<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class SolicitudActivoCheckList
 * @package App\Models
 * @version February 8, 2026, 10:28 am CST
 *
 * @property \App\Models\DepartamentoRequerimiento $departamentorequerimientoid
 * @property \App\Models\SolicitudActivo $solicitudactivoid
 * @property integer $SolicitudActivoID
 * @property integer $DepartamentoRequerimientoID
 * @property boolean $completado
 * @property string $responsable
 */
class SolicitudActivoCheckList extends Model
{
    use SoftDeletes;


    public $table = 'solicitud_activo_checklists';
    protected $primaryKey = 'SolicitudActivoChecklistID';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'SolicitudActivoID',
        'DepartamentoRequerimientoID',
        'completado',
        'responsable'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'SolicitudActivoChecklistID' => 'integer',
        'SolicitudActivoID' => 'integer',
        'DepartamentoRequerimientoID' => 'integer',
        'completado' => 'boolean',
        'responsable' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'SolicitudActivoID' => 'required',
        'DepartamentoRequerimientoID' => 'required',
        'completado' => 'required|boolean',
        'responsable' => 'nullable|string|max:80',
        'created_at' => 'required',
        'updated_at' => 'required',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function requerimientos()
    {
        return $this->belongsTo(\App\Models\DepartamentoRequerimientos::class, 'DepartamentoRequerimientoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function activo()
    {
        return $this->belongsTo(\App\Models\SolicitudActivo::class, 'SolicitudActivoID');
    }
}
