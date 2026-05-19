<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class TicketSatisfactionAnswerController extends Controller
{
    /**
     * Vista unificada de la encuesta (wizard).
     */
    public function show(Request $request, string $survey)
    {
        $calificacion = Calificacion::where('uuid', $survey)->first();

        if (! $calificacion) {
            return view('tickets.satisfaction.invalid', [
                'message' => 'La encuesta no existe.',
            ]);
        }

        if ($calificacion->isNotAnswered()) {
            return view('tickets.satisfaction.invalid', [
                'message' => 'Esta encuesta ya no está disponible.',
            ]);
        }

        $expiresAt = $calificacion->expires_at;
        $isExpired = $calificacion->isExpired();

        $signedUrls = [];

        if (! $isExpired) {
            foreach (Calificacion::allowedFields() as $field) {
                for ($i = 1; $i <= 5; $i++) {
                    $signedUrls[$field][$i] = URL::temporarySignedRoute(
                        'tickets.satisfaction.answer',
                        $expiresAt,
                        ['survey' => $survey, 'field' => $field, 'rating' => $i]
                    );
                }
            }
        }

        $calificacion->loadMissing('ticket.responsableTI');

        return view('tickets.satisfaction.survey', [
            'calificacion' => $calificacion,
            'signedUrls'   => $signedUrls,
            'expiresAt'    => $expiresAt,
            'isExpired'    => $isExpired,
        ]);
    }

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

        // Si toda la encuesta ya está completada, redirigir al wizard
        if ($calificacion->isCompleted()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'completed' => true]);
            }
            return redirect()->route('tickets.satisfaction.survey', ['survey' => $survey]);
        }

        // Si este campo específico ya fue contestado, redirigir al wizard
        if ($calificacion->{$field} !== null) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'completed' => $calificacion->isCompleted()]);
            }
            return redirect()->route('tickets.satisfaction.survey', ['survey' => $survey]);
        }

        $calificacion->{$field} = $rating;
        $calificacion->save();

        $calificacion->updateProgressStatus();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'completed' => $calificacion->isCompleted()]);
        }

        return redirect()->route('tickets.satisfaction.survey', ['survey' => $survey]);
    }
}
