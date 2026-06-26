<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MantenimientoChat extends Model
{
    protected $fillable = [
        'mantenimiento_id',
        'mensaje',
        'remitente',
        'correo_remitente',
        'nombre_remitente',
        'contenido_correo',
        'message_id',
        'thread_id',
        'adjuntos',
        'es_correo',
        'leido',
        'notificaciones_pendientes',
    ];

    protected $casts = [
        'adjuntos' => 'array',
        'es_correo' => 'boolean',
        'leido' => 'boolean',
    ];

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(TicketMantenimiento::class, 'mantenimiento_id', 'MantenimientoID');
    }
}
