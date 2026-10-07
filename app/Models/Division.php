<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * División de la estructura empresarial.
 * En TI la unidad de negocio siempre cuelga de una división.
 */
class Division extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'divisiones';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['deleted_at'];

    protected $primaryKey = 'DivisionID';
    protected $keyType = 'int';

    public $fillable = [
        'NombreDivision',
    ];

    protected $casts = [
        'DivisionID' => 'integer',
        'NombreDivision' => 'string',
    ];

    public static $rules = [
        'NombreDivision' => 'required|string|max:100',
    ];

    public function unidadesdenegocio()
    {
        return $this->hasMany(UnidadesDeNegocio::class, 'DivisionID');
    }
}
