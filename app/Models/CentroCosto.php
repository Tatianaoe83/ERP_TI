<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentroCosto extends Model
{
    use SoftDeletes;

    public $table = 'centrosdecostos';

    protected $primaryKey = 'CentroCostoID';

    public $fillable = [
        'NombreCentro',
    ];

    protected $casts = [
        'CentroCostoID' => 'integer',
        'NombreCentro' => 'string',
    ];
}
