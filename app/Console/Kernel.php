<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Verificar tickets excedidos cada 15 minutos
        /*$schedule->command('tickets:verificar-excedidos')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();*/
        
        // Procesar respuestas automáticas cada 5 minutos
        $schedule->command('tickets:procesar-respuestas-automaticas')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Marcar encuestas de satisfacción vencidas una vez al día
        $schedule->command('tickets:surveys-expire')->everyFourHours()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
