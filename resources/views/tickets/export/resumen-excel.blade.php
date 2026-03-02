@php
    $usuarios = $usuariosUnicos ?? [];
    
    // 1. Aplanar la jerarquía (2 Niveles: Gerencia -> Tertipo)
    $filasGerencia = [];
    if (isset($tablaAgrupada)) {
        foreach ($tablaAgrupada as $principal => $datos) {
            $filasGerencia[] = [
                'tipo'    => 'padre',
                'nombre'  => $principal,
                'totales' => $datos['total_principal'] ?? []
            ];
            
            if (isset($datos['tertipos'])) {
                foreach ($datos['tertipos'] as $ter => $totalesTer) {
                    $filasGerencia[] = [
                        'tipo'    => 'hijo',
                        'nombre'  => $ter,
                        'totales' => $totalesTer
                    ];
                }
            }
        }
    } else {
        $gerencias = $tablaGerencia ?? [];
        foreach ($gerencias as $g => $usrs) {
            $filasGerencia[] = ['tipo' => 'padre', 'nombre' => $g, 'totales' => $usrs];
        }
    }

    $categoriaNombres = array_keys($tablaCategoria ?? []);

    // Paleta moderna alineada con la página web
    $primaryBlue   = '#1E40AF';
    $lightBlue     = '#EFF6FF';
    $accentBlue    = '#3B82F6';
    $successGreen  = '#059669';
    $lightGreen    = '#D1FAE5';
    $purpleAccent  = '#7C3AED';
    $lightPurple   = '#EDE9FE';
    $amberAccent   = '#D97706';
    $lightAmber    = '#FEF3C7';
    $slateBorder   = '#64748B';
    $slateText     = '#475569';

    $tableStyle  = "border-collapse:collapse; font-family:'Segoe UI',Calibri,Arial,sans-serif; font-size:11px; width:100%;";
    $border      = "1px solid #E2E8F0";
    $borderAccent = "2px solid {$primaryBlue}";

    $thStyle     = "font-weight:600; background-color:{$lightBlue}; color:{$primaryBlue}; border:{$border}; padding:8px 12px; text-align:left; white-space:nowrap;";
    $thCenter    = "font-weight:600; background-color:{$lightBlue}; color:{$primaryBlue}; border:{$border}; padding:8px 12px; text-align:center; white-space:nowrap;";
    $thTitle     = "font-weight:700; font-size:13px; background-color:{$primaryBlue}; color:#FFFFFF; border:{$border}; padding:10px 12px; text-align:center;";
    $tdPadre     = "font-weight:600; background-color:#F8FAFC; color:#0F172A; border:{$border}; padding:6px 12px; text-transform:uppercase;";
    $tdPadreVal  = "font-weight:600; background-color:#F8FAFC; color:#0F172A; border:{$border}; padding:6px 12px; text-align:center;";
    $tdHijo      = "background-color:#FFFFFF; color:{$slateText}; border:{$border}; padding:5px 12px; padding-left:28px;";
    $tdHijoVal   = "background-color:#FFFFFF; color:{$slateText}; border:{$border}; padding:5px 12px; text-align:center;";
    $tdTotal     = "font-weight:700; background-color:{$lightBlue}; color:{$primaryBlue}; border:{$border}; padding:6px 12px;";
    $tdTotalVal  = "font-weight:700; background-color:{$lightBlue}; color:{$primaryBlue}; border:{$border}; padding:6px 12px; text-align:center;";
    $tdNormal    = "background-color:#FFFFFF; color:{$slateText}; border:{$border}; padding:6px 12px;";
    $tdNormalVal = "background-color:#FFFFFF; color:{$slateText}; border:{$border}; padding:6px 12px; text-align:center;";
    $tdLabel     = "font-weight:600; background-color:#F8FAFC; color:#0F172A; border:{$border}; padding:6px 12px;";
    $tdNote      = "font-style:italic; color:{$slateText}; background-color:#F8FAFC; border:{$border}; padding:6px 12px; font-size:10px;";
    $tdGreen     = "font-weight:700; color:{$successGreen}; background-color:{$lightGreen}; border:{$border}; padding:6px 12px;";
    $tdGreenVal  = "font-weight:700; color:{$successGreen}; background-color:{$lightGreen}; border:{$border}; padding:6px 12px; text-align:center;";

    $cardStyle   = "font-size:12px; font-weight:600; padding:12px 16px; border-radius:0;";
@endphp

{{-- ══════════════════════════════════════════════════════════════════
     ENCABEZADO Y TARJETAS KPI DE RESUMEN
══════════════════════════════════════════════════════════════════ --}}
<table style="{{ $tableStyle }} margin-bottom:24px;">
    <tr>
        <td colspan="5" style="background:linear-gradient(135deg, #1E3A8A 0%, #1E40AF 100%); color:#FFFFFF; font-size:18px; font-weight:700; padding:16px 20px; border:1px solid #1E40AF; letter-spacing:0.5px;">
            Reporte de Productividad
        </td>
    </tr>
    <tr>
        <td colspan="5" style="background-color:{{ $lightBlue ?? '#EFF6FF' }}; color:{{ $primaryBlue ?? '#1E40AF' }}; font-size:12px; font-weight:600; padding:10px 20px; border:1px solid #BFDBFE;">
            Período: {{ $mesNombreTarget ?? '' }}
        </td>
    </tr>
    {{-- Fila de etiquetas KPI (una celda = un concepto, sin pegar texto) --}}
    <tr>
        <td style="{{ $cardStyle }} background-color:#EFF6FF; color:{{ $primaryBlue ?? '#1E40AF' }}; border:1px solid #BFDBFE; text-align:center; min-width:140px;">Total de Tickets</td>
        <td style="{{ $cardStyle }} background-color:{{ $lightGreen ?? '#D1FAE5' }}; color:{{ $successGreen ?? '#059669' }}; border:1px solid #6EE7B7; text-align:center; min-width:140px;">Tickets Cerrados</td>
        <td style="{{ $cardStyle }} background-color:{{ $lightPurple ?? '#EDE9FE' }}; color:{{ $purpleAccent ?? '#7C3AED' }}; border:1px solid #C4B5FD; text-align:center; min-width:160px;">Tiempo Prom. Resolución</td>
        <td style="{{ $cardStyle }} background-color:{{ $lightAmber ?? '#FEF3C7' }}; color:{{ $amberAccent ?? '#D97706' }}; border:1px solid #FCD34D; text-align:center; min-width:160px;">Tiempo Prom. Respuesta</td>
        <td style="{{ $cardStyle }} background-color:{{ $lightGreen ?? '#D1FAE5' }}; color:{{ $successGreen ?? '#059669' }}; border:1px solid #6EE7B7; text-align:center; min-width:120px;">Cumplimiento</td>
    </tr>
    {{-- Fila de valores KPI --}}
    <tr>
        <td style="font-size:14px; font-weight:700; padding:10px 16px; background-color:#EFF6FF; border:1px solid #BFDBFE; text-align:center;">{{ $totalTickets ?? 0 }}</td>
        <td style="font-size:14px; font-weight:700; padding:10px 16px; background-color:{{ $lightGreen ?? '#D1FAE5' }}; border:1px solid #6EE7B7; text-align:center;">{{ $ticketsCerrados ?? 0 }}<br><span style="font-size:10px; font-weight:500;">{{ $totalTickets > 0 ? round(($ticketsCerrados ?? 0) / $totalTickets * 100, 1) : 0 }}% del total</span></td>
        <td style="font-size:14px; font-weight:700; padding:10px 16px; background-color:{{ $lightPurple ?? '#EDE9FE' }}; border:1px solid #C4B5FD; text-align:center;">{{ $promResolucionHoras ?? '0' }}<br><span style="font-size:10px; font-weight:500;">horas laborales</span></td>
        <td style="font-size:14px; font-weight:700; padding:10px 16px; background-color:{{ $lightAmber ?? '#FEF3C7' }}; border:1px solid #FCD34D; text-align:center;">{{ $promRespuestaHoras ?? '0' }}<br><span style="font-size:10px; font-weight:500;">horas laborales</span></td>
        <td style="font-size:14px; font-weight:700; padding:10px 16px; background-color:{{ $lightGreen ?? '#D1FAE5' }}; border:1px solid #6EE7B7; text-align:center;">{{ $cumplimiento ?? '0%' }}</td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════════════════════
     TABLA 1 · Incidencias por gerencia por usuario asignado
══════════════════════════════════════════════════════════════════ --}}
<div style="display:block; clear:both; margin-bottom:28px;">
<table style="{{ $tableStyle }}">
    <tr>
        <td colspan="{{ count($usuarios) + 2 }}" style="font-weight:700; font-size:13px; background-color:{{ $primaryBlue ?? '#1E40AF' }}; color:#FFFFFF; border:1px solid {{ $primaryBlue ?? '#1E40AF' }}; padding:10px 12px; text-align:center;">
            Incidencias por gerencia por usuario asignado
        </td>
    </tr>
    <tr>
        <td style="{{ $thStyle }}">Etiquetas de fila</td>
        <td style="{{ $thCenter }}">Total general</td>
        @foreach($usuarios as $usr)
            <td style="{{ $thCenter }}">{{ $usr }}</td>
        @endforeach
    </tr>

    @foreach ($filasGerencia as $filaActual)
        @php
            $esPadre           = $filaActual['tipo'] === 'padre';
            $estiloNombre      = $esPadre ? $tdPadre  : $tdHijo;
            $estiloValor       = $esPadre ? $tdPadreVal : $tdHijoVal;
            $totalFilaGerencia = 0;
        @endphp
        <tr>
            <td style="{{ $estiloNombre }}">{{ $filaActual['nombre'] }}</td>

            @foreach($usuarios as $usr)
                @php $totalFilaGerencia += ($filaActual['totales'][$usr] ?? 0); @endphp
            @endforeach
            <td style="{{ $estiloValor }}">{{ $totalFilaGerencia > 0 ? $totalFilaGerencia : '' }}</td>

            @foreach($usuarios as $usr)
                @php $valG = $filaActual['totales'][$usr] ?? 0; @endphp
                <td style="{{ $estiloValor }}">{{ $valG > 0 ? $valG : '' }}</td>
            @endforeach
        </tr>
    @endforeach

    <tr>
        <td style="{{ $tdTotal }}">Total general</td>
        @php $granTotalGerencias = 0; @endphp
        @foreach($usuarios as $usr)
            @php
                foreach($filasGerencia as $fila) {
                    if ($fila['tipo'] === 'padre') {
                        $granTotalGerencias += ($fila['totales'][$usr] ?? 0);
                    }
                }
            @endphp
        @endforeach
        <td style="{{ $tdTotalVal }}">{{ $granTotalGerencias }}</td>

        @foreach($usuarios as $usr)
            @php
                $totUsr = 0;
                foreach($filasGerencia as $fila) {
                    if ($fila['tipo'] === 'padre') {
                        $totUsr += ($fila['totales'][$usr] ?? 0);
                    }
                }
            @endphp
            <td style="{{ $tdTotalVal }}">{{ $totUsr > 0 ? $totUsr : '' }}</td>
        @endforeach
    </tr>
</table>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     GRÁFICA — Movida debajo de la Tabla 1
══════════════════════════════════════════════════════════════════ --}}
<div style="display:block; clear:both; width:100%; margin-top:10px; margin-bottom:28px;">
    {{-- Ejemplo: <img src="{{ $graficaUrl }}" style="max-width:100%; height:auto;"> --}}
    {{-- O tu componente Blade/Livewire de gráfica aquí --}}
</div>

{{-- ══════════════════════════════════════════════════════════════════
     TABLA 2 · Incidencias por categoría
══════════════════════════════════════════════════════════════════ --}}
<div style="display:block; clear:both; margin-bottom:28px;">
<table style="{{ $tableStyle }}">
    <tr>
        <td colspan="3" style="font-weight:700; font-size:13px; background-color:{{ $primaryBlue ?? '#1E40AF' }}; color:#FFFFFF; border:1px solid {{ $primaryBlue ?? '#1E40AF' }}; padding:10px 12px; text-align:center;">
            Incidencias por categoría — {{ $mesNombreTarget }}
        </td>
    </tr>
    <tr>
        <td style="{{ $thStyle }}">Etiquetas de fila</td>
        <td style="{{ $thCenter }}">Cuenta de ID</td>
        <td style="{{ $thCenter }}">Tiempo Prom. de Resolución</td>
    </tr>

    @foreach($categoriaNombres as $categoria)
        <tr>
            <td style="{{ $tdNormal }}">{{ $categoria }}</td>
            <td style="{{ $tdNormalVal }}">{{ $tablaCategoria[$categoria]['total'] }}</td>
            <td style="{{ $tdNormalVal }}">{{ $tablaCategoria[$categoria]['promedio_resolucion'] }}</td>
        </tr>
    @endforeach

    <tr>
        <td style="{{ $tdTotal }}">Total general</td>
        <td style="{{ $tdTotalVal }}">{{ $totalTickets }}</td>
        <td style="{{ $tdTotal }}"></td>
    </tr>
</table>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     TABLA 3 · Tiempos de respuesta promedio
══════════════════════════════════════════════════════════════════ --}}
<div style="display:block; clear:both; margin-bottom:28px;">
<table style="{{ $tableStyle }}">
    <tr>
        <td colspan="3" style="font-weight:700; font-size:13px; background-color:{{ $primaryBlue ?? '#1E40AF' }}; color:#FFFFFF; border:1px solid {{ $primaryBlue ?? '#1E40AF' }}; padding:10px 12px; text-align:center;">
            Tiempos de respuesta promedio — {{ $mesNombreTarget }}
        </td>
    </tr>
    <tr>
        <td style="{{ $tdLabel }}">Tiempo Promedio de primer respuesta</td>
        <td style="{{ $tdNormalVal }}">{{ $promPrimerRespuesta }}</td>
        <td style="{{ $tdNote }}">Tiempo de respuesta promedio de tickets normales</td>
    </tr>
    <tr>
        <td style="{{ $tdLabel }}">Tiempo promedio de resolución</td>
        <td style="{{ $tdNormalVal }}">{{ $promResolucionNormal }}</td>
        <td style="{{ $tdNote }}">Tiempo de resolución promedio (tickets menores a 8 horas)</td>
    </tr>
    <tr>
        <td style="{{ $tdLabel }}">Tiempo promedio Total</td>
        <td style="{{ $tdNormalVal }}">{{ $promResolucionTotal }}</td>
        <td style="{{ $tdNote }}">Tiempo total (incluyendo tickets de duración anormal)</td>
    </tr>
    <tr>
        <td style="{{ $tdGreen }}">Porcentaje de cumplimiento</td>
        <td style="{{ $tdGreenVal }}">{{ $cumplimiento }}</td>
        <td style="{{ $tdNote }}">{{ collect(explode(' ', $textoAnormales))->take(10)->implode(' ') }}...</td>
    </tr>

    <tr><td colspan="3" style="padding:4px; border:none; background:#FFFFFF;"></td></tr>

    <tr>
        <td colspan="3" style="font-weight:700; font-size:13px; background-color:{{ $primaryBlue ?? '#1E40AF' }}; color:#FFFFFF; border:1px solid {{ $primaryBlue ?? '#1E40AF' }}; padding:10px 12px; text-align:center;">
            Tiempos de respuesta promedio — {{ $mesNombreAnterior }}
        </td>
    </tr>
    <tr>
        <td style="{{ $tdLabel }}">Tiempo Promedio de primer respuesta</td>
        <td style="{{ $tdNormalVal }}">{{ $promPrimerRespuestaAnt }}</td>
        <td style="{{ $tdNote }}">Tiempo de respuesta promedio de tickets normales</td>
    </tr>
    <tr>
        <td style="{{ $tdLabel }}">Tiempo promedio de resolución</td>
        <td style="{{ $tdNormalVal }}">{{ $promResolucionNormalAnt }}</td>
        <td style="{{ $tdNote }}">Tiempo de resolución promedio (tickets menores a 8 horas)</td>
    </tr>
    <tr>
        <td style="{{ $tdLabel }}">Tiempo promedio Total</td>
        <td style="{{ $tdNormalVal }}">{{ $promResolucionTotalAnt }}</td>
        <td style="{{ $tdNote }}">Tiempo total (incluyendo tickets de duración anormal)</td>
    </tr>
    <tr>
        <td style="{{ $tdGreen }}">Porcentaje de cumplimiento</td>
        <td style="{{ $tdGreenVal }}">{{ $cumplimientoAnt }}</td>
        <td style="{{ $tdNote }}">{{ collect(explode(' ', $textoAnormales))->take(10)->implode(' ') }}...</td>
    </tr>
    <tr>
        <td colspan="3" style="font-style:italic; font-size:10px; color:#555555; text-align:justify; border:{{ $border }}; padding:5px; background-color:#FFFFFF;">
            "{{ $textoAnormales }}"
        </td>
    </tr>
</table>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     TABLA 4 · Comparativo de meses por usuario
══════════════════════════════════════════════════════════════════ --}}
<div style="display:block; clear:both; margin-bottom:28px;">
<table style="{{ $tableStyle }}">
    <tr>
        <td colspan="4" style="font-weight:700; font-size:13px; background-color:{{ $primaryBlue ?? '#1E40AF' }}; color:#FFFFFF; border:1px solid {{ $primaryBlue ?? '#1E40AF' }}; padding:10px 12px; text-align:center;">
            Comparativo de meses por usuario
        </td>
    </tr>
    <tr>
        <td style="{{ $thStyle }}">Etiquetas de fila</td>
        <td style="{{ $thCenter }}; text-transform:capitalize;">{{ $mesAnteriorCorto }}</td>
        <td style="{{ $thCenter }}; text-transform:capitalize;">{{ $mesActualCorto }}</td>
        <td style="{{ $thCenter }}">Total general</td>
    </tr>

    @foreach($usuariosAmbosMeses as $usrM)
        @php
            $valAnt = $tablaMesesUsuarios[$usrM][$mesAnteriorCorto] ?? 0;
            $valAct = $tablaMesesUsuarios[$usrM][$mesActualCorto] ?? 0;
        @endphp
        <tr>
            <td style="{{ $tdLabel }}">{{ $usrM }}</td>
            <td style="{{ $tdNormalVal }}">{{ $valAnt > 0 ? $valAnt : '' }}</td>
            <td style="{{ $tdNormalVal }}">{{ $valAct > 0 ? $valAct : '' }}</td>
            <td style="{{ $tdTotalVal }}">{{ $valAnt + $valAct }}</td>
        </tr>
    @endforeach
</table>
</div>