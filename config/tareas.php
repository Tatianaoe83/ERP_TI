<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Avisos de tareas programadas (métricas mensuales)
    |--------------------------------------------------------------------------
    |
    | Buzón del área que recibe los correos: uno el día que se genera la tarea
    | programada y otro cuando esa tarea se vuelve crítica. Se manda al buzón de
    | soporte, no a personas, para que el aviso no dependa de altas y bajas de
    | usuarios. Acepta varios correos separados por comas en TAREAS_CORREO_SOPORTE.
    |
    */

    'destinatarios' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TAREAS_CORREO_SOPORTE', 'soporte@proser.com.mx'))
    ))),

    /*
    | Días hacia atrás que se consideran para el aviso de creación. Cubre las métricas
    | generadas fuera del horario del cron sin revivir tareas viejas.
    */
    'ventana_aviso_creacion_dias' => 3,

];
