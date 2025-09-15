@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#101D49] dark:text-white mb-2">Listado de Equipos Asignados</h1>
            <p class="text-gray-600 dark:text-gray-300">Inventario completo de equipos de cómputo asignados a empleados</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('reportes-especificos.export-equipos-asignados', $filtros) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <i class="fas fa-download mr-2"></i>Descargar PDF
            </a>
            <a href="{{ route('reportes-especificos.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-[#101D49] dark:text-white mb-4">
            <i class="fas fa-filter mr-2"></i>Filtros de Búsqueda
        </h3>
        
        <form method="GET" action="{{ route('reportes-especificos.equipos-asignados') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Empleado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Empleado</label>
                <select name="empleado_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los empleados</option>
                     @foreach(\App\Models\Empleados::whereNull('deleted_at')->get() as $empleado)
                         <option value="{{ $empleado->EmpleadoID }}" {{ $filtros['empleado_id'] == $empleado->EmpleadoID ? 'selected' : '' }}>
                             {{ $empleado->NombreEmpleado }}
                         </option>
                     @endforeach
                </select>
            </div>

            <!-- Equipo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Equipo</label>
                <select name="equipo_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los equipos</option>
                    @foreach(\App\Models\Equipos::whereNull('deleted_at')->get() as $equipo)
                        <option value="{{ $equipo->EquipoID }}" {{ $filtros['equipo_id'] == $equipo->EquipoID ? 'selected' : '' }}>
                            {{ $equipo->Nombre }} - {{ $equipo->Marca }} {{ $equipo->Modelo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Gerencia -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gerencia</label>
                <select name="gerencia_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todas las gerencias</option>
                    @foreach(\App\Models\Gerencia::whereNull('deleted_at')->get() as $gerencia)
                        <option value="{{ $gerencia->GerenciaID }}" {{ $filtros['gerencia_id'] == $gerencia->GerenciaID ? 'selected' : '' }}>
                            {{ $gerencia->Nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Estatus -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estatus</label>
                <select name="estatus" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los estatus</option>
                    <option value="Activo" {{ $filtros['estatus'] == 'Activo' ? 'selected' : '' }}>Activo</option>
                    <option value="Inactivo" {{ $filtros['estatus'] == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                    <option value="Mantenimiento" {{ $filtros['estatus'] == 'Mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                    <option value="Dañado" {{ $filtros['estatus'] == 'Dañado' ? 'selected' : '' }}>Dañado</option>
                </select>
            </div>

            <!-- Fecha Desde -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Botones -->
            <div class="md:col-span-2 lg:col-span-4 flex space-x-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-search mr-2"></i>Aplicar Filtros
                </button>
                <a href="{{ route('reportes-especificos.equipos-asignados') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>Limpiar Filtros
                </a>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-[#101D49] dark:text-white">
                    Resultados del Reporte
                </h3>
            </div>

            @push('third_party_stylesheets')
            <!-- DataTables CSS -->
            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
            <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
            @endpush

            <div class="table-responsive">
                {!! $dataTable->table(['width' => '100%', 'class' => 'table table-bordered table-striped']) !!}
            </div>

            @push('third_party_scripts')
            <!-- DataTables Core -->
            <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

            <!-- DataTables Buttons -->
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

            <!-- JSZIP y PDFMake para exportación -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

            <!-- DataTables Scripts -->
            {!! $dataTable->scripts() !!}
            @endpush
        </div>
    </div>
</div>
@endsection
