{{--
    Cierre animado de los modales del módulo de Tareas.
    Lo usan livewire/tabla-tareas y livewire/productividad-tareas.

    Uso en el backdrop:
        <div class="tareas-modal-backdrop"
             x-data="tareasModalCerrable('nombrePropiedadLivewire')"
             :class="{ 'is-closing': !abierto }"
             @click.self="cerrar()"
             @keydown.escape.window="cerrar()">

    Y en los botones de cerrar/cancelar:  @click="cerrar()"
--}}
<script>
    /**
     * Livewire quita el nodo del modal en cuanto la propiedad pasa a false, así que
     * no daría tiempo de animar la salida. Aquí Alpine baja primero una bandera
     * local (que activa la clase .is-closing) y hasta que termina la animación le
     * avisa a Livewire.
     *
     * Va inline y no en un stack push: dentro de un componente Livewire los stacks
     * no se re-resuelven en cada render. El guard evita redefinirla si ambas vistas
     * cargan. (Sin la arroba a proposito: Blade la tomaria como directiva.)
     */
    if (!window.tareasModalCerrable) {
        window.tareasModalCerrable = function (propiedad) {
            return {
                abierto: true,
                cerrar() {
                    if (!this.abierto) return;   // evita doble disparo
                    this.abierto = false;

                    const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    setTimeout(() => this.$wire.set(propiedad, false), reducido ? 0 : 150);
                },
            };
        };
    }
</script>
