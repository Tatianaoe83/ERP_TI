<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutlookToken extends Model
{
    use HasFactory;

    protected $table = 'outlook_tokens';
    
    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
        'scope',
        'token_type'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * Verificar si el token está expirado
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Obtener el token actual válido
     */
    public static function getValidToken()
    {
        return static::where('expires_at', '>', now())->first();
    }

    /**
     * Obtener el último token (incluso si está expirado)
     */
    public static function getLatestToken()
    {
        return static::orderBy('created_at', 'desc')->first();
    }
}
