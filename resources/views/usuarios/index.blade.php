{{-- Extiende el layout principal de la aplicación --}}
@extends('layouts.app')

{{-- Inicio de la sección de contenido --}}
@section('content')

{{-- =====================================================
     CONTENEDOR PRINCIPAL
===================================================== --}}
<div class="container-fluid roles-section py-4">

    <div class="row justify-content-center">

        <div class="col-md-11">
            
            {{-- =====================================================
                 TARJETA PRINCIPAL
            ===================================================== --}}
            <div class="card shadow-lg border-0">
                
                {{-- =====================================================
                     ENCABEZADO DEL MÓDULO
                ===================================================== --}}
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">

                    {{-- Título principal --}}
                    <h4 class="mb-0">
                        <i class="fa-solid fa-users me-2"></i>
                        Gestión de Usuarios
                    </h4>

                    {{-- Botón nuevo usuario --}}
                    <div class="d-flex gap-2">

                        <button class="btn btn-primary btn-sm shadow-sm fw-bold" 
                                type="button" 
                                data-bs-toggle="offcanvas" 
                                data-bs-target="#offcanvasNuevoUsuario">

                            <i class="fa-solid fa-plus-circle me-1"></i>
                            Nuevo Usuario
                        </button>
                    </div>
                </div>

                {{-- =====================================================
                     CUERPO PRINCIPAL
                ===================================================== --}}
                <div class="card-body px-4">

                    {{-- =====================================================
                         BUSCADOR DE USUARIOS
                    ===================================================== --}}
                    <div class="row my-3">

                        <div class="col-md-5">

                            <form method="GET"
                                  action="{{ route('usuarios.index') }}">

                                <div class="input-group shadow-sm">

                                    {{-- Icono buscador --}}
                                    <span class="input-group-text bg-white border-end-0">

                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>

                                    {{-- Campo búsqueda --}}
                                    <input type="text"
                                           name="buscar"
                                           class="form-control border-start-0 ps-0" 
                                           placeholder="Buscar usuario..."
                                           value="{{ request('buscar') }}">

                                    {{-- Botón buscar --}}
                                    <button class="btn btn-primary"
                                            type="submit">

                                        Buscar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- =====================================================
                         TABLA DE USUARIOS
                    ===================================================== --}}
                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle shadow-sm">

                            {{-- Encabezados --}}
                            <thead>

                                <tr>

                                    <th class="text-center">USUARIO</th>

                                    <th class="text-center">EMPLEADO</th>

                                    <th class="text-center">ROL</th>

                                    <th class="text-center">ESTADO</th>

                                    <th class="text-center"
                                        style="width: 160px;">

                                        ACCIONES
                                    </th>
                                </tr>
                            </thead>

                            {{-- Cuerpo dinámico --}}
                            <tbody>

                                {{-- Recorrido de usuarios --}}
                                @foreach($usuarios as $u)

                                <tr>

                                    {{-- Nombre de usuario --}}
                                    <td class="ps-4 fw-bold text-primary">

                                        <i class="fa-solid fa-circle-user me-2"></i>

                                        {{ $u->usuario }}
                                    </td>

                                    {{-- Empleado asociado --}}
                                    <td>

                                        {{ $u->empleado ? $u->empleado->nombre . ' ' . $u->empleado->apellido : 'Sin asignar' }}
                                    </td>

                                    {{-- Rol --}}
                                    <td class="text-center">

                                        <span class="badge rounded-pill bg-info text-dark px-3">

                                            {{ $u->rol->nombre }}
                                        </span>
                                    </td>

                                    {{-- Estado --}}
                                    <td class="text-center">

                                        <span class="badge {{ $u->estado == 'activo' ? 'bg-success' : 'bg-secondary' }} shadow-sm px-3 py-2">

                                            {{ ucfirst($u->estado) }}
                                        </span>
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="text-center">

                                        <div class="btn-group shadow-sm">

                                            {{-- Botón editar --}}
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm btn-edit"

                                                onclick="abrirEditar(
                                                    '{{ $u->id }}',
                                                    '{{ $u->usuario }}',
                                                    '{{ $u->empleado?->nombre ?? 'Administrador' }} {{ $u->empleado?->apellido ?? 'Global' }}',
                                                    '{{ $u->role_id }}',
                                                    '{{ $u->estado }}'
                                                )"

                                                title="Editar Usuario">

                                                <i class="fa-solid fa-edit"></i>
                                            </button>

                                            {{-- Botón cambiar estado --}}
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm" 

                                                    onclick="confirmarEstado(
                                                        '{{ $u->id }}',
                                                        '{{ $u->usuario }}',
                                                        '{{ $u->estado }}'
                                                    )"

                                                    title="Cambiar Estado">

                                                <i class="fa-solid fa-power-off"></i>
                                            </button>

                                            {{-- Botón eliminar --}}
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm" 

                                                    onclick="confirmarEliminar(
                                                        '{{ $u->id }}',
                                                        '{{ $u->usuario }}'
                                                    )"

                                                    title="Eliminar Usuario">

                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>

                                        {{-- =====================================================
                                             FORMULARIO OCULTO CAMBIO ESTADO
                                        ===================================================== --}}
                                        <form id="estado-form-{{ $u->id }}"
                                              action="{{ route('usuarios.estado', $u->id) }}"
                                              method="POST"
                                              style="display: none;">

                                            @csrf 
                                            @method('PUT')
                                        </form>

                                        {{-- =====================================================
                                             FORMULARIO OCULTO ELIMINAR
                                        ===================================================== --}}
                                        <form id="delete-form-{{ $u->id }}"
                                              action="{{ route('usuarios.destroy', $u->id) }}"
                                              method="POST"
                                              style="display: none;">

                                            @csrf 
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>

                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div> 
            </div> 
        </div>
    </div>
</div>

{{-- =====================================================
     OFFCANVAS CREAR Y EDITAR USUARIO
===================================================== --}}
@include('usuarios.create')
@include('usuarios.edit')

{{-- Librería SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- =====================================================
     SCRIPT PRINCIPAL
===================================================== --}}
<script>

document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       ALERTA DE ÉXITO
    ===================================================== */
    @if(session('success'))

        Swal.fire({

            title: '¡Logrado!',

            text: "{{ session('success') }}",

            icon: 'success',

            iconColor: '#a5dc86',

            showConfirmButton: false,

            timer: 3000,

            customClass: {
                popup: 'rounded-4 p-5 shadow-lg',
                title: 'fw-bold text-dark fs-2 mb-3',
                htmlContainer: 'text-muted fs-5'
            }
        });

    @endif

    /* =====================================================
       ALERTA DE ERRORES VALIDACIÓN
    ===================================================== */
    @if($errors->any())

        let errorHtml = '<ul class="text-start mb-0">';

        {{-- Recorrido de errores --}}
        @foreach ($errors->all() as $error)

            errorHtml += '<li>{{ $error }}</li>';

        @endforeach

        errorHtml += '</ul>';

        Swal.fire({

            title: '¡Atención!',

            html: errorHtml,

            icon: 'error',

            confirmButtonColor: '#054084',

            confirmButtonText: 'Entendido',

            customClass: {
                popup: 'rounded-4 shadow-lg',
                title: 'fw-bold text-danger'
            }
        });

    @endif

   /* =====================================================
   REABRIR OFFCANVAS SI HUBO ERROR EN EDICIÓN
   ===================================================== */
   @if(session('abrir_edicion'))
    // Esto se ejecuta inmediatamente al cargar el script, sin esperar a DOMContentLoaded
    (function() {
        const idUsuario = "{{ session('abrir_edicion.id') }}";
        const form = document.getElementById('formEditarUsuario');
        const offcanvasElement = document.getElementById('offcanvasEditarUsuario');

        if (form && offcanvasElement) {
            // Actualizar action
            form.action = '/usuarios/' + idUsuario;

            // Abrir componente de Bootstrap
            const bsOffcanvas = new bootstrap.Offcanvas(offcanvasElement);
            bsOffcanvas.show();
        }
    })();
   @endif
});

/* =====================================================
   CONFIRMACIÓN ELIMINAR USUARIO
===================================================== */
function confirmarEliminar(id, nombre) {

    Swal.fire({

        title: '¿Eliminar usuario?',

        text: `Esta acción eliminará de forma permanente al usuario "${nombre}". No se puede deshacer.`,

        icon: 'warning',

        iconColor: '#dc3545',

        showCancelButton: true,

        confirmButtonColor: '#dc3545',

        cancelButtonColor: '#6c757d',

        confirmButtonText: 'Sí, eliminar',

        cancelButtonText: 'Cancelar',

        reverseButtons: true,

        customClass: {
            popup: 'rounded-4 p-4 shadow-sm',
            title: 'fw-bold text-secondary',
            confirmButton: 'px-4 py-2 fw-bold me-2',
            cancelButton: 'px-4 py-2 fw-bold'
        }

    }).then((result) => {

        // Si confirma eliminación
        if (result.isConfirmed) {

            document.getElementById('delete-form-' + id).submit();
        }
    });
}

/* =====================================================
   CONFIRMACIÓN CAMBIO ESTADO
===================================================== */
function confirmarEstado(id, nombre, estado) {

    // Determina acción
    const accion = estado === 'activo'
        ? 'desactivar'
        : 'activar';

    // Color dinámico
    const colorConfirm = estado === 'activo'
        ? '#6c757d'
        : '#28a745';

    Swal.fire({

        title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} usuario?`,

        text: `El acceso de "${nombre}" al sistema será modificado actualizando su estado.`,

        icon: 'info',

        iconColor: '#054084',

        showCancelButton: true,

        confirmButtonColor: '#054084',

        cancelButtonColor: '#6c757d',

        confirmButtonText: 'Sí, cambiar',

        cancelButtonText: 'Cancelar',

        reverseButtons: true,

        customClass: {
            popup: 'rounded-4 p-4 shadow-sm',
            title: 'fw-bold text-secondary',
            confirmButton: 'px-4 py-2 fw-bold me-2',
            cancelButton: 'px-4 py-2 fw-bold'
        }

    }).then((result) => {

        // Si confirma
        if (result.isConfirmed) {

            document.getElementById('estado-form-' + id).submit();
        }
    });
}

/* =====================================================
   ABRIR OFFCANVAS EDITAR
===================================================== */
function abrirEditar(id, usuario, empleado, roleId, estado) {

    // Referencia formulario editar
    const form = document.getElementById('formEditarUsuario');

    // Actualizar action dinámica
    form.action = '/usuarios/' + id;

    // Cargar valores
    document.getElementById('edit_usuario').value = usuario;
    document.getElementById('edit_empleado').value = empleado;
    document.getElementById('edit_role_id').value = roleId;
    document.getElementById('edit_estado').value = estado;
    
    // Abrir offcanvas
    var myOffcanvas = document.getElementById('offcanvasEditarUsuario');

    var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);

    bsOffcanvas.show();
}

/* =====================================================
   MOSTRAR / OCULTAR PASSWORD GENÉRICA
===================================================== */
function togglePass(inputId, iconId) {

    // Referencias
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    // Mostrar contraseña
    if (input.type === "password") {

        input.type = "text";

        icon.classList.replace('fa-eye', 'fa-eye-slash');

    } else {

        // Ocultar contraseña
        input.type = "password";

        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/* =====================================================
   MOSTRAR / OCULTAR PASSWORD PRINCIPAL
===================================================== */
function togglePassword() {

    // Referencias
    const passInput = document.getElementById('password');

    const icon = document.getElementById('password-icon');

    // Mostrar contraseña
    if (passInput.type === "password") {

        passInput.type = "text";

        icon.classList.replace('fa-eye', 'fa-eye-slash');

    } else {

        // Ocultar contraseña
        passInput.type = "password";

        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

</script>

@endsection {{-- Fin de la sección content --}}
