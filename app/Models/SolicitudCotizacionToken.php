<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudCotizacionToken extends Model
{
    protected $table = 'solicitud_cotizacion_tokens';

    protected $fillable = [
        'solicitud_id',
        'token',
        'expires_at',
        'revoked_at',
        'used_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'used_at' => 'datetime'
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id', 'SolicitudID');
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNull('revoked_at')
            ->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at) 
            && is_null($this->used_at)
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }
}
