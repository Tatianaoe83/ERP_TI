<label for="buscarPermiso">Permisos para este Rol</label>
<div class="crud-perm-tools">
    <input type="search" id="buscarPermiso" class="form-control" placeholder="Buscar permiso" autocomplete="off">
    <span class="crud-perm-count" id="conteoPermisos"></span>
    <div class="crud-select-all" id="selectAll">Seleccionar todos</div>
</div>
<p class="perm-vacio" id="permisosVacios" hidden>Ningún permiso coincide con la búsqueda.</p>
<div class="crud-perms" id="listaPermisos">
    @foreach($permission as $value)
    <div class="form-group flex items-center space-x-2 perm-item" data-permiso="{{ mb_strtolower($value->name) }}">
        <label>
            {{ Form::checkbox('permission[]', $value->id, in_array($value->id, $rolePermissions ?? []), array('class' => 'name cursor-pointer')) }}
            <span>{{ $value->name }}</span>
        </label>
    </div>
    @endforeach
</div>

@push('styles')
<style>
    .crud-perm-tools {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
        margin: 0.45rem 0 0.75rem;
    }
    .crud-perm-tools .form-control {
        flex: 1 1 220px;
        max-width: 320px;
    }
    .crud-perm-tools .crud-select-all {
        margin: 0;
    }
    .crud-perm-count {
        font-size: 0.78rem;
        color: #6b7280;
    }
    .perm-vacio {
        font-size: 0.85rem;
        color: #6b7280;
        margin: 0 0 0.75rem;
    }
</style>
@endpush

@push('third_party_scripts')
<script>
    (function () {
        var busqueda = document.getElementById('buscarPermiso');
        var lista = document.getElementById('listaPermisos');
        var conteo = document.getElementById('conteoPermisos');
        var vacio = document.getElementById('permisosVacios');
        var seleccionar = document.getElementById('selectAll');
        if (!busqueda || !lista) return;

        var items = Array.prototype.slice.call(lista.querySelectorAll('.perm-item'));

        function visibles() {
            return items.filter(function (item) {
                return item.style.display !== 'none';
            });
        }

        function actualizarBoton() {
            var cajas = visibles().map(function (item) {
                return item.querySelector('input[type="checkbox"]');
            }).filter(Boolean);
            var todos = cajas.length > 0 && cajas.every(function (caja) { return caja.checked; });
            var filtrando = busqueda.value.trim() !== '';
            seleccionar.textContent = todos
                ? (filtrando ? 'Quitar visibles' : 'Deseleccionar todos')
                : (filtrando ? 'Seleccionar visibles' : 'Seleccionar todos');
        }

        function filtrar() {
            var texto = busqueda.value.trim().toLowerCase();
            var coincidencias = 0;
            items.forEach(function (item) {
                var coincide = texto === '' || (item.getAttribute('data-permiso') || '').indexOf(texto) !== -1;
                item.style.display = coincide ? '' : 'none';
                if (coincide) coincidencias += 1;
            });
            conteo.textContent = texto === ''
                ? items.length + ' permisos'
                : coincidencias + ' de ' + items.length;
            vacio.hidden = coincidencias !== 0;
            actualizarBoton();
        }

        busqueda.addEventListener('input', filtrar);
        busqueda.addEventListener('keyup', filtrar);
        lista.addEventListener('change', actualizarBoton);
        seleccionar.addEventListener('click', function () {
            var cajas = visibles().map(function (item) {
                return item.querySelector('input[type="checkbox"]');
            }).filter(Boolean);
            var marcar = cajas.some(function (caja) { return !caja.checked; });
            cajas.forEach(function (caja) { caja.checked = marcar; });
            actualizarBoton();
        });
        filtrar();
    })();
</script>
@endpush
