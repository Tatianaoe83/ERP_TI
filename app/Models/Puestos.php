<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Puestos
 * @package App\Models
 * @version January 24, 2025, 7:37 pm UTC
 *
 * @property \App\Models\Departamento $departamentoid
 * @property \Illuminate\Database\Eloquent\Collection $obras
 * @property string $NombrePuesto
 * @property integer $DepartamentoID
 */
class Puestos extends Model
{
    use HasFactory, SoftDeletes;


    public $table = 'puestos';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'PuestoID';
    protected $keyType = 'int';

    public $fillable = [
        'NombrePuesto',
        'DepartamentoID'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'PuestoID' => 'integer',
        'NombrePuesto' => 'string',
        'DepartamentoID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombrePuesto' => 'nullable|string|max:75',
        'DepartamentoID' => 'nullable|integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function Departamentos()
    {
        return $this->belongsTo(\App\Models\Departamentos::class, 'DepartamentoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function Obras()
    {
        return $this->belongsToMany(\App\Models\Obra::class, 'Empleados');
    }
}
