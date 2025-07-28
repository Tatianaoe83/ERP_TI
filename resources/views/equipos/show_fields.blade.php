<!-- Categoriaid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CategoriaID', 'Categoria:') !!}
    <p>{{ $equipos->categorias->Categoria }}</p>
</div>

<!-- Marca Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Marca', 'Marca:') !!}
    <p>{{ $equipos->Marca }}</p>
</div>

<!-- Caracteristicas Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Caracteristicas', 'Caracteristicas:') !!}
    <p>{{ $equipos->Caracteristicas }}</p>
</div>

<!-- Modelo Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Modelo', 'Modelo:') !!}
    <p>{{ $equipos->Modelo }}</p>
</div>

<!-- Precio Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Precio', 'Precio:') !!}
    <p>{{ $equipos->Precio }}</p>
</div>