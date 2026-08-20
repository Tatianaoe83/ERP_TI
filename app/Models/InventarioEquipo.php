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
        'FechaDeCompra',
        'tipoEquipo',
        'MesDePago'
    ];

    protected $casts = [
        'tipoEquipo' => 'integer',
    ];

    /** Modalidades de la columna tipoEquipo. */
    public const TIPO_NO_PRESUPUESTADO = 0;
    public const TIPO_PRESUPUESTADO    = 1;
    public const TIPO_PROPIO           = 2;

    /**
     * Los equipos propios se listan junto a los no presupuestados: ambos son
     * inventario actual, sólo el presupuestado (1) es proyección futura.
     */
    public const TIPOS_STOCK = [self::TIPO_NO_PRESUPUESTADO, self::TIPO_PROPIO];

    public function empleados()
    {
        return $this->belongsTo(\App\Models\Empleados::class, 'EmpleadoID');
    }

    public function gerencia()
    {
        return $this->belongsTo(Gerencia::class, 'GerenciaEquipoID', 'GerenciaID');
    }

   /*  public function gerenciaid()
    {
        return $this->belongsTo(Gerencia::class, 'GerenciaID');
    } */
}
