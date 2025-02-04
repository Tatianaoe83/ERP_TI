<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioEquipo extends Model
{
    use HasFactory;

    use SoftDeletes;


    public $table = 'inventarioequipo';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


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

    public function gerenciaid()
    {
        return $this->belongsTo(\App\Models\Gerencia::class, 'GerenciaEquipoID');
    }

}
