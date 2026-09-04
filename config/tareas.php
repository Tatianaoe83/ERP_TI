<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Avisos de tareas programadas (métricas mensuales)
    |--------------------------------------------------------------------------
    |
    | Usernames del personal de soporte que recibe los correos: uno el día que se
    | genera la tarea programada y otro cuando esa tarea se vuelve crítica.
    | El correo sale de users.email y, si viene vacío, del empleado con el mismo
    | nombre. Se agrega TAREAS_NOTIFICADOS_EXTRA (separado por comas) para copias
    | puntuales sin tocar el código.
    |
    */

    'notificados' => [
        'RVALENCIA',
        'MCOYOC',
        'MMUGARTE',
        'AENCALADA',
    ],

    /*
    | Días hacia atrás que se consideran para el aviso de creación. Cubre las métricas
    | generadas fuera del horario del cron sin revivir tareas viejas.
    */
    'ventana_aviso_creacion_dias' => 3,

    'correos_extra' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TAREAS_NOTIFICADOS_EXTRA', ''))
    ))),

];
