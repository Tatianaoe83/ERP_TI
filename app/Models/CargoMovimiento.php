<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoMovimiento extends Model
{
    protected $table = 'cargos_movimiento';

    protected $primaryKey = 'CargoID';

    public $fillable = [
        'Clave',
        'Descripcion',
        'UnidadNegocio',
        'Autoriza',
    ];

    public function etiquetaAutoriza(): string
    {
        return [
            'jefe_costos' => 'Jefe de costos',
            'gerente_presupuestos' => 'Gerente de presupuestos',
            'gerente_compras' => 'Gerente de compras',
            'director_proyectos' => 'Director de proyectos',
        ][$this->Autoriza] ?? 'Jefe de costos';
    }
}
