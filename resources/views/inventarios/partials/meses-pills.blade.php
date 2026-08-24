@php
    $mesesPills = \App\Helpers\PagoMeses::parse($mesesValor ?? '', $mesesFrecuencia ?? null);
    $abreviar = $abreviar ?? true;
@endphp
@if(count($mesesPills) === 0)
    <span class="text-muted">—</span>
@elseif(count($mesesPills) === 12)
    <span class="inv-mes-pill">Anual</span>
@else
    <span class="inv-meses-pills">
        @foreach($mesesPills as $mesPill)
            <span class="inv-mes-pill">{{ $abreviar ? mb_substr($mesPill, 0, 3) : $mesPill }}</span>
        @endforeach
    </span>
@endif
