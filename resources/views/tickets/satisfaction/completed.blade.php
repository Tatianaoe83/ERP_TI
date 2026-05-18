<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuesta completada</title>
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
            max-width: 520px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%);
            padding: 40px 32px 32px;
            text-align: center;
            border-radius: 24px 24px 0 0;
        }
        .card-header .icon-circle {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 24px;
        }
        .card-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }
        .card-header p {
            color: #E0E7FF;
            font-size: 14.5px;
        }
        .card-body {
            padding: 32px;
        }
        .message {
            text-align: center;
            color: #334155;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .message strong {
            color: #0F172A;
            font-weight: 700;
        }
        .ratings-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            overflow: hidden;
        }
        .ratings-table thead th {
            background-color: #F8FAFC;
            padding: 12px 20px;
            color: #64748B;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
        }
        .ratings-table thead th:last-child {
            text-align: right;
        }
        .ratings-table tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 14px;
        }
        .ratings-table tbody tr:last-child td {
            border-bottom: none;
        }
        .ratings-table .field-name {
            color: #1E293B;
            font-weight: 600;
        }
        .ratings-table .field-icon {
            margin-right: 10px;
        }
        .ratings-table .rating-value {
            text-align: right;
            font-weight: 700;
        }
        .rating-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 700;
        }
        .rating-high { background: #DCFCE7; color: #166534; }
        .rating-mid { background: #FEF9C3; color: #854D0E; }
        .rating-low { background: #FEE2E2; color: #991B1B; }
        .rating-na { background: #F1F5F9; color: #64748B; }
        
        .alert-box {
            margin-top: 24px;
            padding: 16px 20px;
            border-radius: 16px;
            font-size: 13.5px;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .alert-info {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-left: 4px solid #3B82F6;
        }
        .alert-info .alert-icon {
            font-size: 20px;
        }
        .alert-info p {
            margin: 0;
            color: #334155;
        }
        .alert-info strong {
            color: #0F172A;
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
            <div class="icon-circle">✅</div>
            <h1>
                @if(isset($alreadyAnswered) && $alreadyAnswered)
                    Apartado ya calificado
                @else
                    Encuesta completada
                @endif
            </h1>
            <p>
                @if(isset($alreadyAnswered) && $alreadyAnswered)
                    Ya registraste tu calificación para este apartado
                @else
                    Ya tenemos registrada tu calificación completa
                @endif
            </p>
        </div>

        <div class="card-body">
            @if(isset($alreadyAnswered) && $alreadyAnswered)
                <p class="message">
                    Ya habías calificado <strong>{{ $fieldLabel }}</strong> con un
                    <strong>{{ $fieldRating }}/5</strong>.<br>No es posible modificar una calificación registrada.
                </p>
            @else
                <p class="message">
                    Ya tenemos registrada tu calificación completa para este ticket. <br>¡Gracias por tomarte el tiempo de responder!
                </p>
            @endif

            <table class="ratings-table">
                <thead>
                    <tr>
                        <th>Apartado evaluado</th>
                        <th>Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rows = [
                            ['icon' => '⚡', 'label' => 'Rapidez', 'value' => $calificacion->fastness],
                            ['icon' => '✅', 'label' => 'Resolución', 'value' => $calificacion->resolution],
                            ['icon' => '💬', 'label' => 'Atención', 'value' => $calificacion->attention],
                        ];
                    @endphp
                    @foreach($rows as $row)
                    <tr>
                        <td class="field-name">
                            <span class="field-icon">{{ $row['icon'] }}</span>{{ $row['label'] }}
                        </td>
                        <td class="rating-value">
                            @if($row['value'] !== null)
                                @php
                                    $badgeClass = $row['value'] >= 4 ? 'rating-high' : ($row['value'] === 3 ? 'rating-mid' : 'rating-low');
                                @endphp
                                <span class="rating-badge {{ $badgeClass }}">{{ $row['value'] }}/5</span>
                            @else
                                <span class="rating-badge rating-na">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if(!$calificacion->isCompleted())
                <div class="alert-box alert-info">
                    <div class="alert-icon">✉️</div>
                    <p>Aún tienes <strong>apartados pendientes</strong> por calificar. Usa los enlaces del correo que recibiste para completar tu evaluación.</p>
                </div>
            @endif
        </div>

        <div class="card-footer">
            <p>Soporte TI · Sistema de tickets</p>
        </div>
    </div>
</body>
</html>
