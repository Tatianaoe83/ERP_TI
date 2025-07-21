<!-- Companiaid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CompaniaID', 'Compania:') !!}
    <p>{{ $planes->companiaslineastelefonicas->Compania }}</p>
</div>

<!-- Nombreplan Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombrePlan', 'Nombre plan:') !!}
    <p>{{ $planes->NombrePlan }}</p>
</div>

<!-- Precioplan Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('PrecioPlan', 'Precio plan:') !!}
    <p>{{ $planes->PrecioPlan }}</p>
</div>