<!DOCTYPE html>

<!-- Documento HTML5 -->
<html lang="es">

<head>

    <!-- ========================================================= -->
    <!-- CONFIGURACIONES BÁSICAS -->
    <!-- ========================================================= -->

    <!-- Codificación UTF-8 -->
    <meta charset="UTF-8">

    <!-- Responsive Design -->
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <!-- Título del sistema -->
    <title>SISTEMA RRHH - IHCI</title>


    <!-- ========================================================= -->
    <!-- LIBRERÍAS CSS -->
    <!-- ========================================================= -->

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
          rel="stylesheet" />

    <!-- Tema Bootstrap para Select2 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Flatpickr -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- CSS personalizado -->
    <link rel="stylesheet"
          href="{{ asset('css/app.css') }}">

    <!-- Frappe Gantt -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css">

</head>


<!-- ========================================================= -->
<!-- BODY -->
<!-- ========================================================= -->

<body class="{{ Route::is('login') ? 'auth-body' : '' }}">

@auth

    @php

        // Usuario autenticado
        $user = auth()->user();

        // Verificamos si es administrador
        $isAdmin =
            ($user->role_id == 1 ||
             strtolower($user->usuario) == 'admin');


        // =====================================================
        // CONSULTA DE PERMISOS
        // =====================================================

        // Obtenemos módulos visibles según rol
        $permisosRaw = DB::table('rol_modulos')
            ->where('role_id', $user->role_id)
            ->where('visible', 1)
            ->pluck('visible', 'modulo')
            ->toArray();


        // Array limpio de permisos
        $permisosUsuario = [];

        foreach ($permisosRaw as $modulo => $visible) {

            // Eliminamos tildes para evitar errores
            $nombreLimpio =
                str_replace(
                    ['á', 'é', 'í', 'ó', 'ú'],
                    ['a', 'e', 'i', 'o', 'u'],
                    strtolower($modulo)
                );

            // Guardamos módulo limpio
            $permisosUsuario[$nombreLimpio] = $visible;
        }

    @endphp


    {{-- ========================================================= --}}
    {{-- MOSTRAR NAVBAR SOLO SI NO ES LOGIN --}}
    {{-- ========================================================= --}}

    @if(!Route::is('login') &&
        !Route::is('password.cambiar'))

    <!-- ===================================================== -->
    <!-- NAVBAR PRINCIPAL -->
    <!-- ===================================================== -->

    <header class="navbar-ihci sticky-top shadow-sm">

        <div class="nav-flex-container">

            <!-- ================================================= -->
            <!-- LOGO -->
            <!-- ================================================= -->

            <div class="logo-nav">

                <a href="{{ url('dashboard') }}">

                    <img src="{{ asset('images/IHCI.png') }}"
                         alt="IHCI">
                </a>
            </div>


            <!-- ================================================= -->
            <!-- MENÚ DE MÓDULOS -->
            <!-- ================================================= -->

            <nav class="nav-modules">

                {{-- ================================================= --}}
                {{-- SEGURIDAD --}}
                {{-- ================================================= --}}

                @if($isAdmin ||
                    ($permisosUsuario['seguridad'] ?? 0) == 1)

                <div class="menu-item">

                    <i class="fa-solid fa-shield-halved"></i>

                    Seguridad

                    <i class="fa-solid fa-chevron-down small"></i>

                    <div class="submenu">

                        <a href="{{ route('roles.index') }}"
                           class="submenu-item">

                            Roles
                        </a>

                        <a href="{{ route('departamentos.index') }}"
                           class="submenu-item">

                            Departamentos
                        </a>

                        <a href="{{ route('permisos_sistema.index') }}"
                           class="submenu-item">

                            Permisos del sistema
                        </a>

                        <a href="{{ route('usuarios.index') }}"
                           class="submenu-item">

                            Usuarios
                        </a>

                        <a href="{{ route('firmas.index') }}"
                           class="submenu-item">

                            Gestión de Firmas
                        </a>
                    </div>
                </div>

                @endif


                {{-- ================================================= --}}
                {{-- ADMINISTRACIÓN --}}
                {{-- ================================================= --}}

                @if($isAdmin ||
                    ($permisosUsuario['administracion'] ?? 0) == 1 ||
                    ($permisosUsuario['administración'] ?? 0) == 1)

                <div class="menu-item">

                    <i class="fa-solid fa-gears"></i>

                    Administración

                    <i class="fa-solid fa-chevron-down small"></i>

                    <div class="submenu">

                        <a href="{{ route('empleado.index') }}"
                           class="submenu-item">

                            Empleados
                        </a>

                        <a href="{{ route('politicas.index') }}"
                           class="submenu-item">

                            Políticas Vacaciones
                        </a>

                        <a href="{{ route('formulario.index') }}"
                           class="submenu-item">

                            Formulario Evaluación
                        </a>
                    </div>
                </div>

                @endif


                {{-- ================================================= --}}
                {{-- PERMISOS LABORALES --}}
                {{-- ================================================= --}}

                @if($isAdmin ||
                    ($permisosUsuario['permisos_laborales'] ?? 0) == 1)

                <div class="menu-item">

                    <i class="fa-solid fa-key"></i>

                    Permisos Laborales

                    <i class="fa-solid fa-chevron-down small"></i>

                    <div class="submenu">

                        <a href="{{ route('solicitudes.index') }}"
                           class="submenu-item">

                            Solicitudes
                        </a>

                        <a href="{{ route('horas_extras.gestion') }}"
                           class="submenu-item">

                            Tiempo Compensatorio
                        </a>
                    </div>
                </div>

                @endif


                {{-- ================================================= --}}
                {{-- PROYECTOS --}}
                {{-- ================================================= --}}

                @if($isAdmin ||
                    ($permisosUsuario['proyectos'] ?? 0) == 1)

                <div class="menu-item">

                    <i class="fa-solid fa-diagram-project"></i>

                    Proyectos

                    <i class="fa-solid fa-chevron-down small"></i>

                    <div class="submenu">

                        <a href="{{ route('proyectos.index') }}"
                           class="submenu-item">

                            Registrar proyectos/Metas
                        </a>

                        <a href="{{ route('evaluaciones.index') }}"
                           class="submenu-item">

                            Evaluación de proyectos/Metas
                        </a>
                    </div>
                </div>

                @endif


                {{-- ================================================= --}}
                {{-- INFORMES --}}
                {{-- ================================================= --}}

                @if($isAdmin ||
                    ($permisosUsuario['informes'] ?? 0) == 1)

                <div class="menu-item">

                    <i class="fa-solid fa-chart-line"></i>

                    Informes y Estadísticas

                    <i class="fa-solid fa-chevron-down small"></i>

                    <div class="submenu">

                        <a href="{{ route('informes.index') }}"
                           class="submenu-item">

                            Informes y Estadísticas
                        </a>

                        <a href="{{ route('informes.graficas.index') }}" class="submenu-item">
                            Gráficas Comparativas

                        </a>
                    </div>
                </div>

                @endif

            </nav>


            <!-- ================================================= -->
            <!-- NOTIFICACIONES -->
            <!-- ================================================= -->

            <div class="dropdown me-3">

                <!-- Icono campana -->
                <a class="nav-link position-relative p-0"
                   href="#"
                   id="bellIcon"
                   data-bs-toggle="dropdown"
                   aria-expanded="false">

                    <i class="fas fa-bell text-white fs-4"></i>

                    <!-- Badge contador -->
                    @if(auth()->user()->unreadNotifications->count() > 0)

                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              style="font-size: 0.6rem;
                                     padding: 0.2rem 0.4rem;">

                            {{ auth()->user()->unreadNotifications->count() }}
                        </span>

                    @endif
                </a>


                <!-- Dropdown notificaciones -->
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2"
                    style="width: 300px;
                           max-height: 400px;
                           overflow-y: auto;">

                    <!-- Encabezado -->
                    <li class="p-3 border-bottom">

                        <h6 class="fw-bold mb-0">
                            Notificaciones
                        </h6>
                    </li>


                    <!-- Recorremos notificaciones -->
                    @forelse(auth()->user()->unreadNotifications as $notificacion)

                    <li>

                        <a class="dropdown-item p-3 border-bottom text-wrap"
                           href="{{ $notificacion->data['url'] ?? route('evaluaciones.index') }}">

                            <div class="d-flex align-items-start">

                                <i class="fa-solid fa-clipboard-check text-primary me-2 mt-1"></i>

                                <div>

                                    <!-- Mensaje -->
                                    <p class="mb-0 small text-dark">

                                        {{ $notificacion->data['mensaje']
                                            ?? 'Nueva notificación' }}
                                    </p>

                                    <!-- Fecha -->
                                    <small class="text-muted">

                                        {{ $notificacion->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </a>
                    </li>

                    @empty

                    <!-- Sin notificaciones -->
                    <li class="p-4 text-center">

                        <i class="fa-solid fa-bell-slash text-muted mb-2 fs-3"></i>

                        <p class="text-muted small mb-0">
                            No tienes notificaciones pendientes
                        </p>
                    </li>

                    @endforelse


                    <!-- Botón marcar como leídas -->
                    @if(auth()->user()->unreadNotifications->count() > 0)

                    <li class="text-center p-2 border-top">

                        <a href="#"
                           class="text-primary small fw-bold text-decoration-none">

                            Marcar todas como leídas
                        </a>
                    </li>

                    @endif
                </ul>
            </div>


            <!-- ================================================= -->
            <!-- PERFIL DE USUARIO -->
            <!-- ================================================= -->

            <div class="dropdown">

                <!-- Botón perfil -->
                <button class="btn border-0 p-0 dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        style="box-shadow: none;">

                    <!-- Inicial usuario -->
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                         style="width: 40px;
                                height: 40px;
                                border: 2px solid white;">

                        {{ strtoupper(substr(auth()->user()->usuario, 0, 1)) }}
                    </div>
                </button>


                <!-- Menú usuario -->
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0 rounded-4"
                     style="width: 280px;
                            overflow: hidden;">

                    <!-- Encabezado -->
                    <div class="bg-light p-4 text-center">

                        <!-- Avatar -->
                        <div class="rounded-circle bg-info d-flex align-items-center justify-content-center text-white fw-bold mx-auto mb-3 shadow-sm"
                             style="width: 80px;
                                    height: 80px;
                                    font-size: 2.5rem;">

                            {{ strtoupper(substr(auth()->user()->usuario, 0, 1)) }}
                        </div>

                        <!-- Usuario -->
                        <h6 class="fw-bold mb-0 text-dark">

                            {{ auth()->user()->usuario }}
                        </h6>

                        <!-- Correo -->
                        <p class="text-muted small mb-3">

                            {{ auth()->user()->email }}
                        </p>

                        <!-- Botón perfil -->
                        <button type="button"
                                class="btn btn-outline-primary btn-sm rounded-pill px-4"
                                data-bs-toggle="modal"
                                data-bs-target="#modalGestionPerfil">

                            Gestionar Perfil
                        </button>
                    </div>


                    <!-- Logout -->
                    <div class="p-3">

                        <form action="{{ route('logout') }}"
                              method="POST">

                            @csrf

                            <button type="submit"
                                    class="btn btn-light btn-sm w-100 rounded-3 py-2 text-danger fw-bold">

                                <i class="fa-solid fa-right-from-bracket me-2"></i>

                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </header>

    @endif
@endauth


{{-- ========================================================= --}}
{{-- HERO PRINCIPAL --}}
{{-- ========================================================= --}}

@auth

@if(Request::is('home') || Request::is('dashboard'))

<section class="ihci-hero-container">

    <div class="hero-content">

        <h1>
            Una Exitosa Gestión Cultural
        </h1>

        <p class="fs-5">
            Impulsando la plástica contemporánea
            en Honduras desde 1963.
        </p>

        <!-- Botón abrir modal -->
        <button class="btn btn-history shadow-lg"
                data-bs-toggle="modal"
                data-bs-target="#modalHistoria">

            <i class="fa-solid fa-eye me-2"></i>

            Leer Historia
        </button>
    </div>
</section>

@endif
@endauth


<!-- ========================================================= -->
<!-- CONTENIDO PRINCIPAL -->
<!-- ========================================================= -->

<main class="container py-5">

    <!-- Sección dinámica -->
    @yield('content')
</main>


<!-- ========================================================= -->
<!-- MODAL HISTORIA -->
<!-- ========================================================= -->

<div class="modal fade"
     id="modalHistoria"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content border-0">

            <!-- Header -->
            <div class="modal-header">

                <h5 class="modal-title fw-bold">
                    Nuestra Gestión Cultural
                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                </button>
            </div>


            <!-- Body -->
            <div class="modal-body p-4">

                <p>
                    La vasta gestión cultural del IHCI inició en el año de 1963...
                </p>
            </div>


            <!-- Footer -->
            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ========================================================= -->
<!-- MODALES DE PERFIL -->
<!-- ========================================================= -->

@include('perfil.modales')


<!-- ========================================================= -->
<!-- LIBRERÍAS JAVASCRIPT -->
<!-- ========================================================= -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Idioma español Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Frappe Gantt -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js"></script>

</body>
</html>
