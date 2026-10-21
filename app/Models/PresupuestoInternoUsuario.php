<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresupuestoInternoUsuario extends Model
{
    public $table = 'presupuesto_interno_usuarios';

    public $fillable = [
        'PresupuestoInternoID',
        'users_id',
    ];
}
