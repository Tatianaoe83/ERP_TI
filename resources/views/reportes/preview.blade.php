<table class="table table-sm table-bordered">
    <thead>
        <tr>
            @foreach($columns as $col)
            <th>{{ $col['title'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($data as $fila)
        <tr>
            @foreach($columns as $col)
            <td>{{ $fila[$col['field']] ?? '' }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>