<?php

namespace App\Console\Commands;

use App\Models\Calificacion;
use Illuminate\Console\Command;

class ExpireTicketSatisfactionSurveys extends Command
{
    protected $signature = 'tickets:surveys-expire';

    protected $description = 'Marca como not_answered las encuestas de satisfacción pendientes que han vencido.';

    public function handle(): int
    {
        $updated = Calificacion::query()
            ->where('status', Calificacion::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where(function ($query) {
                $query->whereNull('fastness')
                    ->orWhereNull('resolution')
                    ->orWhereNull('attention');
            })
            ->update([
                'status'     => Calificacion::STATUS_NOT_ANSWERED,
                'updated_at' => now(),
            ]);

        $this->info("Encuestas vencidas marcadas como not_answered: {$updated}");

        return self::SUCCESS;
    }
}
