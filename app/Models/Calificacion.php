<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Str;

class Calificacion extends Model
{
    protected $table = 'satisfaction_surveys_tables';

    protected $primaryKey = 'survey_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NOT_ANSWERED = 'not_answered';

    public const FIELD_FASTNESS = 'fastness';
    public const FIELD_RESOLUTION = 'resolution';
    public const FIELD_ATTENTION = 'attention';

    protected $fillable = [
        'uuid',
        'ticket_id',
        'fastness',
        'resolution',
        'attention',
        'status',
        'sent_at',
        'answered_at',
        'expires_at',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'fastness' => 'integer',
        'resolution' => 'integer',
        'attention' => 'integer',
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
        static::creating(function (Calificacion $calificacion): void {
            if (empty($calificacion->uuid)) {
                $calificacion->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public static function allowedFields(): array
    {
        return [
            self::FIELD_FASTNESS,
            self::FIELD_RESOLUTION,
            self::FIELD_ATTENTION,
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    public function isCompleted(): bool
    {
        return $this->fastness !== null
            && $this->resolution !== null
            && $this->attention !== null;
    }

    public function isNotAnswered(): bool
    {
        return $this->status === self::STATUS_NOT_ANSWERED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markAsCompletedIfReady(): void
    {
        if (! $this->isCompleted()) {
            return;
        }

        $this->status = self::STATUS_COMPLETED;
        $this->answered_at = now();
        $this->save();
    }

    public function canBeAnswered(): bool
    {
        return ! $this->isExpired()
            && ! $this->isNotAnswered();
    }
}