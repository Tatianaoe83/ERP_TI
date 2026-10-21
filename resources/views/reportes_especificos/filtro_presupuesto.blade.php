<div class="form-group">
    <label for="presupuesto_id">Presupuesto interno</label>
    <select id="presupuesto_id" name="presupuesto_id" class="jz form-control">
        <option value="">Todos</option>
        @foreach($presupuestosReporte as $presupuesto)
            <option value="{{ $presupuesto->PresupuestoInternoID }}" {{ (string) ($filtros['presupuesto_id'] ?? '') === (string) $presupuesto->PresupuestoInternoID ? 'selected' : '' }}>
                {{ $presupuesto->Nombre }}
            </option>
        @endforeach
    </select>
</div>
