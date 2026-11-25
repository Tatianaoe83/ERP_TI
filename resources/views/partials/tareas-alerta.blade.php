{{--
    Alertas del módulo de Tareas con SweetAlert2 (cargado en layouts/app).
    Compartida por livewire/tabla-tareas y livewire/productividad-tareas.

    Desde el componente:  $this->dispatchBrowserEvent('tareas-alerta', ['icono' => 'success', 'mensaje' => '...']);
    icono: success | error.

    Va inline con guard, igual que tareas-modal-js: dentro de un componente Livewire
    los stacks no se re-resuelven y el listener no debe registrarse dos veces.
--}}
<script>
    if (!window.tareasAlertaLista) {
        window.tareasAlertaLista = true;
        window.addEventListener('tareas-alerta', function (e) {
            var esError = e.detail.icono === 'error';
            Swal.fire({
                icon: e.detail.icono,
                title: esError ? 'Atención' : 'Listo',
                text: e.detail.mensaje,
                // El éxito se cierra solo; el error espera a que lo lean.
                timer: esError ? undefined : 2000,
                showConfirmButton: esError,
                confirmButtonColor: '#101D49',
            });
        });
    }
</script>
