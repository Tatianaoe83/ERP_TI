<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class UnidadesDeNegocio
 * @package App\Models
 * @version January 21, 2025, 11:06 pm UTC
 *
 * @property string $NombreEmpresa
 * @property string $RFC
 * @property string $Direccion
 * @property string $NumTelefono
 */
class UnidadesDeNegocio extends Model
{
    use HasFactory, SoftDeletes;


    public $table = 'unidadesdenegocio';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'UnidadNegocioID';
    protected $keyType = 'int';

    public $fillable = [
        'NombreEmpresa',
        'RFC',
        'Direccion',
        'NumTelefono',
        'estado'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'UnidadNegocioID' => 'integer',
        'NombreEmpresa' => 'string',
        'RFC' => 'string',
        'Direccion' => 'string',
        'NumTelefono' => 'string',
        'estado' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreEmpresa' => 'required|string|max:100',
        'RFC' => 'required|string|max:100',
        'Direccion' => 'required|string|max:150',
        'NumTelefono' => 'required|string|max:100',
        'estado' => 'boolean'
       
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function gerencia()
    {
        return $this->belongsTo(\App\Models\Gerencia::class, 'GerenciaID');
    }
}
