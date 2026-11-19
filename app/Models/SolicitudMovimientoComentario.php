<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMovimientoComentario extends Model
{
    protected $table = 'solicitud_movimiento_comentarios';

    protected $primaryKey = 'ComentarioID';

    public $fillable = [
        'SolicitudMovimientoID',
        'UserID',
        'Autor',
        'Texto',
    ];
}
