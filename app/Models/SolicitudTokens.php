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


    protected $dates = ['deleted_at'];



    public $fillable = [
        'approval_step_id',
        'token',
        'expires_at',
        'revoked_at',
        'used_at'
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
        'used_at' => 'datetime'
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
