<div class="table-responsive-sm">
<table class="table table-striped ">
    <thead>
        <tr>
            <th>Categoría</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Serie</th>
            <th>Asignacion</th>

        </tr>
    </thead>
    <tbody>
        @foreach ($datos as $item)
        <tr>
           
            <td>{{ $item->categoria }}</td>
            <td>{{ $item->Marca }}</td>
            <td>{{ $item->Modelo }}</td>
            <td>{{ $item->NumSerie }}</td>
            <td>{{ $item->FechaAsignacion }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>