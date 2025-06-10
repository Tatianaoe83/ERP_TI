<li class="side-menus {{ Request::is('*') ? 'active' : '' }}">
    <a class="nav-link" href="/home">
        <i class="fas fa-home"></i><span>Dashboard</span>
    </a>
    <ul class="sidebar-menu">
        <li class="menu-header"></li>
        <li class="dropdown">
            <a href="#" class="nav-link has-dropdown"><i class="fas fa-city"></i><span>Empresa</span></a>
            <ul class="dropdown-menu">

                @if(auth()->user()->can('ver-unidadesdenegocio') or auth()->user()->can('crear-unidadesdenegocio') or auth()->user()->can('editar-unidadesdenegocio') or auth()->user()->can('borrar-unidadesdenegocio'))
                <li>
                    <a class="nav-link" href="/unidadesDeNegocios">
                        <i class="fas fa-circle-notch"></i></i><span>Unidad de negocio</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-gerencias') or auth()->user()->can('crear-gerencias') or auth()->user()->can('editar-gerencias') or auth()->user()->can('borrar-gerencias'))
                <li>
                    <a class="nav-link" href="/gerencias">
                        <i class="fas fa-circle-notch"></i><span>Gerencias</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-obras') or auth()->user()->can('crear-obras') or auth()->user()->can('editar-obras') or auth()->user()->can('borrar-obras'))
                <li>
                    <a class="nav-link" href="/obras">
                        <i class="fas fa-circle-notch"></i><span>Obras</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-departamentos') or auth()->user()->can('crear-departamentos') or auth()->user()->can('editar-departamentos') or auth()->user()->can('borrar-departamentos'))
                <li>
                    <a class="nav-link" href="/departamentos">
                        <i class="fas fa-circle-notch"></i></i><span>Departamentos</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-puestos') or auth()->user()->can('crear-puestos') or auth()->user()->can('editar-puestos') or auth()->user()->can('borrar-puestos'))
                <li>
                    <a class="nav-link" href="/puestos">
                        <i class="fas fa-circle-notch"></i><span>Puestos</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-empleados') or auth()->user()->can('crear-empleados') or auth()->user()->can('editar-empleados') or auth()->user()->can('borrar-empleados'))
                <li>
                    <a class="nav-link" href="/empleados">
                        <i class="fas fa-circle-notch"></i></i><span>Empleados</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>
    </ul>

    <ul class="sidebar-menu">
        <li class="menu-header"></li>
        <li class="dropdown">
            <a href="#" class="nav-link has-dropdown"><i class="fas fa-align-justify"></i><span>Activos</span></a>
            <ul class="dropdown-menu">

                @if(auth()->user()->can('ver-Lineastelefonicas') or auth()->user()->can('crear-Lineastelefonicas') or auth()->user()->can('editar-Lineastelefonicas') or auth()->user()->can('borrar-Lineastelefonicas'))
                <li>
                    <a class="nav-link" href="/lineasTelefonicas">
                        <i class="fas fa-circle-notch"></i></i><span>Líneas</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-equipos') or auth()->user()->can('crear-equipos') or auth()->user()->can('editar-equipos') or auth()->user()->can('borrar-equipos'))
                <li>
                    <a class="nav-link" href="/equipos">
                        <i class="fas fa-circle-notch"></i><span>Equipos</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-insumos') or auth()->user()->can('crear-insumos') or auth()->user()->can('editar-insumos') or auth()->user()->can('borrar-insumos'))
                <li>
                    <a class="nav-link" href="/insumos">
                        <i class="fas fa-circle-notch"></i><span>Insumos</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-categorias') or auth()->user()->can('crear-categorias') or auth()->user()->can('editar-categorias') or auth()->user()->can('borrar-categorias'))
                <li>
                    <a class="nav-link" href="/categorias">
                        <i class="fas fa-circle-notch"></i><span>Categorías</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-planes') or auth()->user()->can('crear-planes') or auth()->user()->can('editar-planes') or auth()->user()->can('borrar-planes'))
                <li>
                    <a class="nav-link" href="/planes">
                        <i class="fas fa-circle-notch"></i><span>Planes</span>
                    </a>
                </li>
                @endif

            </ul>
        </li>
    </ul>
    <ul class="sidebar-menu">
        <li class="menu-header"></li>
        <li class="dropdown">
            <a href="#" class="nav-link has-dropdown"><i class="fas fa-exchange-alt"></i><span>Movimientos</span></a>
            <ul class="dropdown-menu">

                @if(auth()->user()->can('transferir-inventario') or auth()->user()->can('cartas-inventario') or auth()->user()->can('asignar-inventario'))
                <li>
                    <a class="nav-link" href="/inventarios">
                        <i class="fas fa-circle-notch"></i><span>Inventario</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>
    </ul>
    <ul class="sidebar-menu">
        <li class="menu-header"></li>
        <li class="dropdown">
            <a href="#" class="nav-link has-dropdown"><i class="fas fa-file-alt"></i><span>Reportes</span></a>
            <ul class="dropdown-menu">

                @if(auth()->user()->can('ver-presupuesto'))
                <li>
                    <a class="nav-link" href="/presupuesto">
                        <i class="fas fa-circle-notch"></i><span>Presupuesto</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->can('ver-reporte') or auth()->user()->can('crear-reporte') or auth()->user()->can('editar-reporte') or auth()->user()->can('borrar-reporte') or auth()->user()->can('exportar-reporte'))
                <li>
                    <a class="nav-link" href="/lista">
                        <i class="fas fa-circle-notch"></i><span>Reporteador</span>
                    </a>
                </li>
                @endif


                @if(auth()->user()->can('ver-informe') or auth()->user()->can('buscar-informe') )
                <li>
                    <a class="nav-link" href="/informe">
                        <i class="fas fa-circle-notch"></i><span>Informes</span>
                    </a>
                </li>
                @endif

            </ul>
        </li>
    </ul>

    @if(auth()->user()->can('ver-usuarios') or auth()->user()->can('crear-usuarios') or auth()->user()->can('editar-usuarios') or auth()->user()->can('borrar-usuarios'))
    <a class="nav-link" href="/usuarios">
        <i class=" fas fa-users"></i><span>Usuarios</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-rol') or auth()->user()->can('crear-rol') or auth()->user()->can('editar-rol') or auth()->user()->can('borrar-rol'))
    <a class="nav-link" href="/roles">
        <i class=" fas fa-user-lock"></i><span>Roles</span>
    </a>
    @endif
</li>