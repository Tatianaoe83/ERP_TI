<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use Illuminate\Http\Request;

class TicketSatisfactionAnswerController extends Controller
{
    public function store(Request $request, string $survey, string $field, int $rating)
    {
        if (! $request->hasValidSignature()) {
            return view('tickets.satisfaction.invalid', [
                'message' => 'El enlace no es válido o ha expirado.',
            ]);
        }

        if (! in_array($field, Calificacion::allowedFields(), true)) {
            return view('tickets.satisfaction.invalid', [
                'message' => 'El apartado solicitado no es válido.',
            ]);
        }

        if ($rating < 1 || $rating > 5) {
            return view('tickets.satisfaction.invalid', [
                'message' => 'La calificación debe estar entre 1 y 5.',
            ]);
        }

        $calificacion = Calificacion::where('uuid', $survey)->first();

        if (! $calificacion) {
            return view('tickets.satisfaction.invalid', [
                'message' => 'La encuesta no existe.',
            ]);
        }

        if ($calificacion->isExpired()) {
            return view('tickets.satisfaction.invalid', [
                'message' => 'Este enlace ha expirado y ya no se pueden registrar calificaciones.',
            ]);
        }

        if ($calificacion->isNotAnswered()) {
            return view('tickets.satisfaction.invalid', [
                'message' => 'Esta encuesta ya no está disponible.',
            ]);
        }

        // Si toda la encuesta ya está completada, mostrar vista de completada
        if ($calificacion->isCompleted()) {
            return view('tickets.satisfaction.completed', [
                'calificacion' => $calificacion,
            ]);
        }

        // Si este campo específico ya fue contestado, no permitir re-contestar
        if ($calificacion->{$field} !== null) {
            $labels = [
                'fastness'   => 'Rapidez',
                'resolution' => 'Resolución',
                'attention'  => 'Atención',
            ];

            return view('tickets.satisfaction.completed', [
                'calificacion'    => $calificacion,
                'alreadyAnswered' => true,
                'fieldLabel'      => $labels[$field],
                'fieldRating'     => $calificacion->{$field},
            ]);
        }

        $calificacion->{$field} = $rating;
        $calificacion->save();

        $calificacion->markAsCompletedIfReady();

        $labels = [
            'fastness'   => 'Rapidez',
            'resolution' => 'Resolución',
            'attention'  => 'Atención',
        ];

        return view('tickets.satisfaction.thanks', [
            'calificacion' => $calificacion,
            'fieldLabel'   => $labels[$field],
            'rating'       => $rating,
        ]);
    }
}
