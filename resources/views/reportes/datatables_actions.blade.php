<a href="{{ route('reportes.show', $id) }}" class="btn btn-sm btn-info" title="Ver">
    🔍
</a>

<a href="{{ route('reportes.edit', $id) }}" class="btn btn-sm btn-primary" title="Editar">
    ✏️
</a>

<form method="POST" action="{{ route('reportes.destroy', $id) }}" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este reporte?')" title="Eliminar">
        🗑️
    </button>
</form>