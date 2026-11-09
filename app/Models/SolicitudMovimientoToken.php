<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMovimientoToken extends Model
{
    protected $table = 'solicitud_movimiento_tokens';

    protected $dates = ['expires_at', 'used_at', 'revoked_at'];

    public $fillable = [
        'paso_id',
        'token',
        'expires_at',
        'used_at',
        'revoked_at',
    ];

    public function paso()
    {
        return $this->belongsTo(SolicitudMovimientoPaso::class, 'paso_id', 'id');
    }

    public function vigente(): bool
    {
        return !$this->used_at
            && !$this->revoked_at
            && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
