<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Tickets
 * @package App\Models
 * @version August 15, 2025, 5:08 pm UTC
 *
 * @property \App\Models\Empleado $empleadoid
 * @property \App\Models\Tipoticket $tipoid
 * @property integer $CodeAnyDesk
 * @property string $Descripcion
 * @property string $Imagen
 * @property integer $Numero
 * @property string $Prioridad
 * @property string $Estatus
 * @property string $ResponsableTI
 * @property integer $EmpleadoID
 * @property integer $TipoID
 */
class Tickets extends Model
{
    use SoftDeletes;


    public $table = 'tickets';
    
    protected $primaryKey = 'TicketID';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'CodeAnyDesk',
        'Descripcion',
        'imagen',
        'Numero',
        'Prioridad',
        'Estatus',
        'ResponsableTI',
        'EmpleadoID',
        'TipoID'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'TicketID' => 'integer',
        'CodeAnyDesk' => 'integer',
        'Descripcion' => 'string',
        'imagen' => 'array',
        'Numero' => 'integer',
        'Prioridad' => 'string',
        'Estatus' => 'string',
        'ResponsableTI' => 'string',
        'EmpleadoID' => 'integer',
        'TipoID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'CodeAnyDesk' => 'required|integer',
        'Descripcion' => 'required|string',
        'imagen' => 'required|array|max:4',
        'imagen.*' => 'file|mimes:jpg,jpeg,png,pdf,xml,xls,xlsx,docx,doc,webp,ppt,pptx|max:2048',
        'Numero' => 'required|integer',
        'Prioridad' => 'required|string',
        'Estatus' => 'nullable|string',
        'ResponsableTI' => 'nullable|string|max:100',
        'EmpleadoID' => 'required|integer',
        'TipoID' => 'nullable|integer',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function empleadoid()
    {
        return $this->belongsTo(\App\Models\Empleado::class, 'EmpleadoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function tipoid()
    {
        return $this->belongsTo(\App\Models\Tipoticket::class, 'TipoID');
    }
}
