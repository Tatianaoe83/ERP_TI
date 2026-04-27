<div x-data="{ open: @entangle('isOpen') }">

    <!-- FONDO -->
    <div 
        x-show="open"
        class="fixed inset-0 bg-black bg-opacity-50 z-40"
        x-transition
    ></div>

    <!-- MODAL -->
    <div 
        x-show="open"
        class="fixed inset-0 flex items-center justify-center z-50"
        x-transition
    >
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">

            <!-- TÍTULO -->
            <h2 class="text-lg font-bold mb-4">
                Confirmación
            </h2>

            <!-- CONTENIDO -->
            <p class="text-gray-600 mb-6">
                ¿Estás seguro de realizar esta acción?
            </p>

            <!-- BOTONES -->
            <div class="flex justify-end gap-3">
                
                <button 
                    @click="open = false"
                    class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                >
                    Cancelar
                </button>

                <button 
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                    Aceptar
                </button>

            </div>

        </div>
    </div>

</div>