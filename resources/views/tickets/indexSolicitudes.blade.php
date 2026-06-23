<div class="overflow-x-auto">
    <livewire:tabla-solicitudes />
</div>

<!-- Scripts para manejar aprobaciones -->
<script>
    function aprobarSolicitud(solicitudId, nivel) {
        Swal.fire({
            title: '¿Aprobar solicitud?',
            text: 'Ingrese un comentario (opcional)',
            input: 'textarea',
            inputPlaceholder: 'Comentario...',
            showCancelButton: true,
            confirmButtonText: 'Aprobar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981',
            preConfirm: (comentario) => {
                return fetch(`/solicitudes/${solicitudId}/aprobar-${nivel}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            comentario: comentario || ''
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Error al aprobar');
                        }
                        return data;
                    });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('¡Aprobado!', result.value.message, 'success').then(() => {
                    location.reload();
                });
            }
        }).catch(error => {
            Swal.fire('Error', error.message, 'error');
        });
    }

    function rechazarSolicitud(solicitudId, nivel) {
        Swal.fire({
            title: '¿Rechazar solicitud?',
            text: 'Ingrese el motivo del rechazo',
            input: 'textarea',
            inputPlaceholder: 'Motivo del rechazo...',
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe ingresar un motivo';
                }
            },
            showCancelButton: true,
            confirmButtonText: 'Rechazar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            preConfirm: (comentario) => {
                return fetch(`/solicitudes/${solicitudId}/rechazar-${nivel}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            comentario: comentario
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Error al rechazar');
                        }
                        return data;
                    });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Rechazada', result.value.message, 'info').then(() => {
                    location.reload();
                });
            }
        }).catch(error => {
            Swal.fire('Error', error.message, 'error');
        });
    }
</script>
