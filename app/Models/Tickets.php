<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Tickets
 * @package App\Models
 * @version October 14, 2025, 5:38 pm UTC
 *
 * @property \App\Models\Empleado $responsableti
 * @property \App\Models\Empleado $empleadoid
 * @property integer $CodeAnyDesk
 * @property string $Descripcion
 * @property string $imagen
 * @property integer $Numero
 * @property string $Prioridad
 * @property string $Estatus
 * @property integer $ResponsableTI
 * @property integer $EmpleadoID
 * @property integer $TipoID
 */
class Tickets extends Model
{
    use SoftDeletes;

    public $table = 'tickets';
    
    protected $primaryKey = 'TicketID';
    public $incrementing = true;
    protected $keyType = 'int';

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
        'imagen' => 'string',
        'Numero' => 'integer',
        'Prioridad' => 'string',
        'Estatus' => 'string',
        'ResponsableTI' => 'integer',
        'EmpleadoID' => 'integer',
        'TipoID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'CodeAnyDesk' => 'nullable',
        'Descripcion' => 'required|string',
        'imagen' => 'nullable|string',
        'Numero' => 'nullable',
        'Prioridad' => 'required|string',
        'Estatus' => 'nullable|string',
        'ResponsableTI' => 'nullable|integer',
        'EmpleadoID' => 'required|integer',
        'TipoID' => 'nullable|integer',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'EmpleadoID', 'EmpleadoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function responsableTI()
    {
        return $this->belongsTo(Empleados::class, 'ResponsableTI', 'EmpleadoID');
    }

    /**
     * Relación con los mensajes del chat
     */
    public function chat()
    {
        return $this->hasMany(TicketChat::class, 'ticket_id', 'TicketID')->orderBy('created_at', 'asc');
    }
}
