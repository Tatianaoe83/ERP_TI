<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Avisos de tareas críticas (métricas y eventos)
    |--------------------------------------------------------------------------
    |
    | Buzón del área que recibe los avisos de críticas: uno por tarea el día que
    | se vuelve crítica y un recordatorio diario con el concentrado de las que
    | siguen sin cerrar. El aviso de asignación no usa esto: va al correo del
    | responsable. Acepta varios correos separados por comas en TAREAS_CORREO_SOPORTE.
    |
    */

    'destinatarios' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TAREAS_CORREO_SOPORTE', 'soporte@proser.com.mx'))
    ))),

];
