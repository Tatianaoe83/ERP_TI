<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'mensaje',
        'remitente',
        'correo_remitente',
        'nombre_remitente',
        'contenido_correo',
        'message_id',
        'thread_id',
        'adjuntos',
        'es_correo',
        'leido'
    ];

    protected $casts = [
        'adjuntos' => 'array',
        'es_correo' => 'boolean',
        'leido' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación con el ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Tickets::class, 'ticket_id', 'TicketID');
    }

    /**
     * Scope para mensajes no leídos
     */
    public function scopeNoLeidos($query)
    {
        return $query->where('leido', false);
    }

    /**
     * Scope para mensajes de correo
     */
    public function scopeCorreos($query)
    {
        return $query->where('es_correo', true);
    }

    /**
     * Scope para mensajes internos
     */
    public function scopeInternos($query)
    {
        return $query->where('es_correo', false);
    }
}
