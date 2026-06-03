<?php

namespace App\Console\Commands;

use App\Models\Calificacion;
use Illuminate\Console\Command;
use App\Services\TicketSatisfactionSurveyService;

class ExpireTicketSatisfactionSurveys extends Command
{
    protected $signature = 'tickets:surveys-expire';

    protected $description = 'Marca como not_answered las encuestas de satisfacción pendientes que han vencido.';

    public function handle(TicketSatisfactionSurveyService $surveys): int
    {
        $updated = $surveys->expirePendingSurveys();
        $this->info("Encuestas vencidas marcadas como not_answered: {$updated}");

        return self::SUCCESS;
    }
}
