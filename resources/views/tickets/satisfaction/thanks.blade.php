<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gracias por calificar</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #F1F5F9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding: 24px 16px;
            -webkit-font-smoothing: antialiased;
        }
        .card {
            width: 100%;
            max-width: 500px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
            padding: 40px 32px 32px;
            text-align: center;
            border-radius: 24px 24px 0 0;
        }
        .rating-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .rating-circle span {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
        }
        .card-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .card-header .subtitle {
            color: #D1FAE5;
            font-size: 14.5px;
        }
        .card-body {
            padding: 32px;
        }
        .success-message {
            text-align: center;
            margin-bottom: 28px;
        }
        .success-message p {
            color: #334155;
            font-size: 15px;
            line-height: 1.6;
            margin-top: 16px;
        }
        .success-message strong {
            color: #0F172A;
        }
        .field-badge {
            display: inline-block;
            background: #ECFDF5;
            color: #059669;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-card {
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-top: 24px;
        }
        .status-pending {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-left: 4px solid #3B82F6;
        }
        .status-completed {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-left: 4px solid #10B981;
        }
        .status-icon {
            font-size: 24px;
            line-height: 1;
        }
        .status-text p {
            color: #334155;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
        }
        .status-text strong {
            color: #0F172A;
            font-weight: 700;
        }
        .progress-bar-container {
            margin-top: 24px;
            background: #E2E8F0;
            border-radius: 100px;
            height: 6px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 100px;
            background: linear-gradient(90deg, #3B82F6, #6366F1);
            transition: width 0.3s ease;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .progress-label span {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
        }
        .progress-label strong {
            color: #1E293B;
        }
        .card-footer {
            padding: 24px 32px;
            text-align: center;
            background-color: #F8FAFC;
            border-top: 1px solid #E2E8F0;
            border-radius: 0 0 24px 24px;
        }
        .card-footer p {
            color: #94A3B8;
            font-size: 12px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div class="rating-circle">
                <span>{{ $rating }}</span>
            </div>
            <h1>¡Calificación registrada!</h1>
            <p class="subtitle">Tu opinión ha sido guardada correctamente</p>
        </div>

        <div class="card-body">
            <div class="success-message">
                <span class="field-badge">{{ $fieldLabel }}</span>
                <p>
                    Has calificado este apartado con <strong>{{ $rating }} de 5</strong> estrellas.
                </p>
            </div>

            @php
                $answered = collect(['fastness', 'resolution', 'attention'])
                    ->filter(fn($f) => $calificacion->{$f} !== null)
                    ->count();
                $total = 3;
                $percent = round(($answered / $total) * 100);
            @endphp

            {{-- Barra de progreso --}}
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: {{ $percent }}%;"></div>
            </div>
            <div class="progress-label">
                <span>Progreso general</span>
                <span><strong>{{ $answered }}</strong> / <strong>{{ $total }}</strong> completados</span>
            </div>

            @if(! $calificacion->isCompleted())
                <div class="status-card status-pending">
                    <div class="status-icon">✉️</div>
                    <div class="status-text">
                        <p>
                            Aún tienes <strong>{{ $total - $answered }} apartado{{ ($total - $answered) > 1 ? 's' : '' }}</strong> pendiente{{ ($total - $answered) > 1 ? 's' : '' }}. <br>Vuelve al correo para completar tu evaluación.
                        </p>
                    </div>
                </div>
            @else
                <div class="status-card status-completed">
                    <div class="status-icon">🎉</div>
                    <div class="status-text">
                        <p>
                            <strong>¡Has completado la encuesta!</strong><br>
                            Gracias por calificar todos los apartados.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-footer">
            <p>Soporte TI · Sistema de tickets</p>
        </div>
    </div>
</body>
</html>
