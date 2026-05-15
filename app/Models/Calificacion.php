<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

class Calificacion extends Model
{
    protected $primaryKey = 'survey_id';
    protected $fillable = [
        'uuid',
        'ticket_id',
        'fastness',
        'resolution',
        'attention',
        'status',
        'sent_at',
        'answered_at',
        'expires_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'answered_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id', 'TicketID');
    }

    protected static function booted(): void
    {
        static::creating(function (Calificacion $survey): void {
            if (empty($survey->uuid)) {
                $survey->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }
}
