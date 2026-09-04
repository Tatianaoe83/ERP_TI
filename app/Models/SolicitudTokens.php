<?php

namespace App\Models;

use Eloquent as Model;

/**
 * Class SolicitudTokens
 * @package App\Models
 * @version January 18, 2026, 1:56 am CST
 *
 * @property \App\Models\SolicitudApprovalStep $approvalStep
 * @property integer $approval_step_id
 * @property string $token
 * @property string|\Carbon\Carbon $expires_at
 * @property string|\Carbon\Carbon $revoked_at
 * @property string|\Carbon\Carbon $used_at
 */
class SolicitudTokens extends Model
{

    public $table = 'solicitud_public_review_tokens';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** Días que dura el enlace de cada etapa, contados desde que se envía su correo. */
    const VIGENCIA_DIAS = 7;

    /**
     * Recordatorios diarios como máximo. Al llegar al tope se deja de insistir y el
     * enlace se agota solo; más allá de esto el correo ya es ruido que nadie abre.
     */
    const MAX_RECORDATORIOS = 5;

    /**
     * No se recuerda un enlace al que le quedan menos de estas horas: el aprobador
     * abriría el correo y se toparía con el enlace ya expirado.
     */
    const MIN_HORAS_PARA_RECORDAR = 24;


    protected $dates = ['deleted_at'];



    public $fillable = [
        'approval_step_id',
        'token',
        'expires_at',
        'revoked_at',
        'used_at',
        'notified_at',
        'last_reminder_at',
        'reminders_sent'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'approval_step_id' => 'integer',
        'token' => 'string',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'used_at' => 'datetime',
        'notified_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'reminders_sent' => 'integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function approvalStep()
    {
        return $this->belongsTo(\App\Models\SolicitudPasos::class, 'approval_step_id');
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
