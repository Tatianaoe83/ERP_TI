<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class LineasTelefonicas
 * @package App\Models
 * @version January 27, 2025, 4:33 pm UTC
 *
 * @property \App\Models\Obra $obraid
 * @property string $NumTelefonico
 * @property integer $PlanID
 * @property string $CuentaPadre
 * @property string $CuentaHija
 * @property string $TipoLinea
 * @property integer $ObraID
 * @property string $FechaFianza
 * @property number $CostoFianza
 * @property boolean $Activo
 * @property boolean $Disponible
 * @property number $MontoRenovacionFianza
 */
class LineasTelefonicas extends Model
{
    use HasFactory,SoftDeletes;


    public $table = 'lineastelefonicas';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'LineaID';

    public $fillable = [
        'NumTelefonico',
        'PlanID',
        'CuentaPadre',
        'CuentaHija',
        'TipoLinea',
        'ObraID',
        'FechaFianza',
        'CostoFianza',
        'Activo',
        'Disponible',
        'MontoRenovacionFianza'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'LineaID' => 'integer',
        'NumTelefonico' => 'string',
        'PlanID' => 'integer',
        'CuentaPadre' => 'string',
        'CuentaHija' => 'string',
        'TipoLinea' => 'string',
        'ObraID' => 'integer',
        'CostoFianza' => 'decimal:2',
        'Activo' => 'boolean',
        'Disponible' => 'boolean',
        'MontoRenovacionFianza' => 'decimal:2'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NumTelefonico' => 'required|string|max:50',
        'PlanID' => 'required|integer',
        'CuentaPadre' => 'required|string|max:100',
        'CuentaHija' => 'required|string|max:100',
        'TipoLinea' => 'required|string|max:50',
        'ObraID' => 'required|integer',
        'FechaFianza' => 'required',
        'CostoFianza' => 'required|numeric',
        'Activo' => 'required|boolean',
        'Disponible' => 'required|boolean',
        'MontoRenovacionFianza' => 'nullable|numeric'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function obraid()
    {
        return $this->belongsTo(\App\Models\Obras::class, 'ObraID');
    }

    public function planid()
    {
        return $this->belongsTo(\App\Models\Planes::class, 'PlanID');
    }

}
