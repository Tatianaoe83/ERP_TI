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

    $tableStyle  = "border-collapse:collapse; font-family:Calibri,Arial,sans-serif; font-size:11px; width:100%;";
    $border      = "1px solid #9EA4BC";

    $thStyle     = "font-weight:bold; background-color:#DAE1F3; color:#000000; border:{$border}; padding:4px 8px; text-align:left; white-space:nowrap;";
    $thCenter    = "font-weight:bold; background-color:#DAE1F3; color:#000000; border:{$border}; padding:4px 8px; text-align:center; white-space:nowrap;";

    $thTitle     = "font-weight:bold; font-size:12px; background-color:#DAE1F3; color:#000000; border:{$border}; padding:5px 8px; text-align:center;";
    $tdPadre     = "font-weight:bold; background-color:#FFFFFF; color:#000000; border:{$border}; padding:4px 8px; text-transform:uppercase;";
    $tdPadreVal  = "font-weight:bold; background-color:#FFFFFF; color:#000000; border:{$border}; padding:4px 8px; text-align:center;";
    $tdHijo      = "background-color:#FFFFFF; color:#000000; border:{$border}; padding:3px 8px; padding-left:24px;";
    $tdHijoVal   = "background-color:#FFFFFF; color:#000000; border:{$border}; padding:3px 8px; text-align:center;";
    $tdTotal     = "font-weight:bold; background-color:#DAE1F3; color:#000000; border:{$border}; padding:4px 8px;";
    $tdTotalVal  = "font-weight:bold; background-color:#DAE1F3; color:#000000; border:{$border}; padding:4px 8px; text-align:center;";
    $tdNormal    = "background-color:#FFFFFF; color:#000000; border:{$border}; padding:4px 8px;";
    $tdNormalVal = "background-color:#FFFFFF; color:#000000; border:{$border}; padding:4px 8px; text-align:center;";
    $tdLabel     = "font-weight:bold; background-color:#FFFFFF; color:#000000; border:{$border}; padding:4px 8px;";
    $tdNote      = "font-style:italic; color:#555555; background-color:#FFFFFF; border:{$border}; padding:4px 8px; font-size:10px;";
    $tdGreen     = "font-weight:bold; color:#375623; background-color:#E2EFDA; border:{$border}; padding:4px 8px;";
    $tdGreenVal  = "font-weight:bold; color:#375623; background-color:#E2EFDA; border:{$border}; padding:4px 8px; text-align:center;";
@endphp

{{-- ══════════════════════════════════════════════════════════════════
     TABLA 1 · Incidencias por gerencia por usuario asignado
══════════════════════════════════════════════════════════════════ --}}
<div style="display:block; clear:both; margin-bottom:20px;">
<table style="{{ $tableStyle }}">
    <tr>
        <td colspan="{{ count($usuarios) + 2 }}" style="{{ $thTitle }}">
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
<div style="display:block; clear:both; margin-bottom:20px;">
<table style="{{ $tableStyle }}">
    <tr>
        <td colspan="3" style="{{ $thTitle }}">
            Incidencias por categoría - {{ $mesNombreTarget }}
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
<div style="display:block; clear:both; margin-bottom:20px;">
<table style="{{ $tableStyle }}">
    <tr>
        <td colspan="3" style="{{ $thTitle }}">
            Tiempos de respuesta promedio {{ $mesNombreTarget }}
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
        <td colspan="3" style="{{ $thTitle }}">
            Tiempos de respuesta promedio {{ $mesNombreAnterior }}
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
<div style="display:block; clear:both; margin-bottom:20px;">
<table style="{{ $tableStyle }}">
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