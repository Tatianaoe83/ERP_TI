<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlace no disponible</title>
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
            max-width: 480px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);
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
            color: #FEE2E2;
            font-size: 14.5px;
        }
        .card-body {
            padding: 32px;
            text-align: center;
        }
        .card-body .message {
            color: #334155;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .info-box {
            background-color: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 12px;
            padding: 16px;
            text-align: left;
        }
        .info-box p {
            color: #991B1B;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.5;
            margin: 0;
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
            <div class="icon-circle">⚠️</div>
            <h1>Enlace no disponible</h1>
            <p>No se puede procesar la solicitud</p>
        </div>

        <div class="card-body">
            <p class="message">{{ $message }}</p>

            <div class="info-box">
                <p>
                    Si crees que esto es un error, contacta al equipo de Soporte TI para obtener asistencia.
                </p>
            </div>
        </div>

        <div class="card-footer">
            <p>Soporte TI · Sistema de tickets</p>
        </div>
    </div>
</body>
</html>
