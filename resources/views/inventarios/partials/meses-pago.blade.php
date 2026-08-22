@php
    $mesesPagoId = $mesesPagoId ?? 'editMesDePago';
    $mesesPagoLabel = $mesesPagoLabel ?? 'Meses de pago';
    $mesesPagoAyuda = $mesesPagoAyuda ?? 'Un mes = ese mes. Varios = parcialidad. Los 12 = anual.';
@endphp
<div class="pago-meses" data-pago-meses="{{ $mesesPagoId }}">
    <div class="pago-meses__head">
        <label class="pago-meses__label">{{ $mesesPagoLabel }}</label>
        <div class="pago-meses__actions">
            <button type="button" class="pago-meses__btn" data-meses-accion="anual">Anual (12)</button>
            <button type="button" class="pago-meses__btn" data-meses-accion="limpiar">Limpiar</button>
        </div>
    </div>
    <div class="pago-meses__grid">
        @foreach(\App\Helpers\PagoMeses::MESES as $mesPago)
        <label class="pago-meses__chip">
            <input type="checkbox" value="{{ $mesPago }}">
            <span>{{ $mesPago }}</span>
        </label>
        @endforeach
    </div>
    <p class="pago-meses__hint" data-meses-hint>{{ $mesesPagoAyuda }}</p>
    <input type="hidden" id="{{ $mesesPagoId }}" name="MesDePago" value="">
</div>
