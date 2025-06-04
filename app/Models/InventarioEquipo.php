<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class InventarioEquipo extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;



    public $table = 'inventarioequipo';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';




    protected $primaryKey = 'InventarioID';
    protected $keyType = 'int';

    public $fillable = [
        'EmpleadoID',
        'CategoriaEquipo',
        'GerenciaEquipoID',
        'Marca',
        'Caracteristicas',
        'Modelo',
        'Precio',
        'FechaAsignacion',
        'NumSerie',
        'Folio',
        'GerenciaEquipo',
        'Comentarios',
        'FechaDeCompra'
    ];

    public function Gerencia()
    {
        return $this->belongsTo(\App\Models\Gerencia::class, 'GerenciaEquipoID');
    }
}
