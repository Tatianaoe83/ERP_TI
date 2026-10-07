<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dirección de la estructura empresarial.
 * En Capital Humano puede no existir; en TI la cadena es lineal y sí se exige.
 */
class Direccion extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'direcciones';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['deleted_at'];

    protected $primaryKey = 'DireccionID';
    protected $keyType = 'int';

    public $fillable = [
        'NombreDireccion',
        'UnidadNegocioID',
        'estado',
    ];

    protected $casts = [
        'DireccionID' => 'integer',
        'NombreDireccion' => 'string',
        'UnidadNegocioID' => 'integer',
        'estado' => 'boolean',
    ];

    public static $rules = [
        'NombreDireccion' => 'required|string|max:100',
        'UnidadNegocioID' => 'required|integer|exists:unidadesdenegocio,UnidadNegocioID',
        'estado' => 'boolean',
    ];

    public function unidadesdenegocio()
    {
        return $this->belongsTo(UnidadesDeNegocio::class, 'UnidadNegocioID');
    }

    public function gerencias()
    {
        return $this->hasMany(Gerencia::class, 'DireccionID');
    }

    public function departamentos()
    {
        return $this->hasManyThrough(
            Departamentos::class,
            Gerencia::class,
            'DireccionID',
            'GerenciaID',
            'DireccionID',
            'GerenciaID'
        );
    }
}
