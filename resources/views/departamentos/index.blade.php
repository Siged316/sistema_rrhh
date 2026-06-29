@extends('layouts.app') {{-- Indica que esta vista hereda la plantilla principal layouts.app --}}

@section('content') {{-- Inicio de la sección content que se mostrará en el layout --}}

<div class="container-fluid py-4"> {{-- Contenedor principal con espaciado vertical --}}
    
    <div class="card shadow border-0"> {{-- Tarjeta principal del módulo --}}

        {{-- ================================
             ENCABEZADO DEL MÓDULO
        ================================= --}}
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            
            {{-- Título principal --}}
            <h4 class="mb-0">
                <i class="fa-solid fa-building me-2"></i> Gestión de Departamentos
            </h4>

            {{-- Botón para abrir el formulario lateral de nuevo departamento --}}
            <button class="btn btn-primary btn-sm"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasNuevoDepartamento">

                <i class="fa-solid fa-plus-circle"></i> Nuevo
            </button>
        </div>

        {{-- ================================
             CUERPO PRINCIPAL
        ================================= --}}
        <div class="card-body">

            {{-- Tabla responsive --}}
            <div class="table-responsive">

                {{-- Tabla de departamentos --}}
                <table class="table table-bordered table-hover align-middle shadow-sm">

                    {{-- Encabezados de tabla --}}
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">Departamento</th>
                            <th class="text-center">Descripción</th>
                            <th class="text-center">Jefe</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    {{-- Cuerpo dinámico de la tabla --}}
                    <tbody>

                        {{-- Recorrido de departamentos --}}
                        @foreach($departamentos as $dep)

                        <tr>

                            {{-- ID del departamento --}}
                            <td class="text-center">
                                #{{ $dep->id }}
                            </td>

                            {{-- Nombre del departamento --}}
                            <td>
                                {{ $dep->nombre }}
                            </td>

                            {{-- Descripción --}}
                            <td>
                                {{ $dep->descripcion }}
                            </td>

                            {{-- Nombre del jefe asignado --}}
                            <td>
                                {{ $dep->jefeEmpleado?->nombre ?? '' }}
                                {{ $dep->jefeEmpleado?->apellido ?? '' }}
                            </td>

                            {{-- Botones de acciones --}}
                            <td class="text-center">

                                {{-- Botón editar --}}
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-edit-departamento"

                                        {{-- Datos enviados al modal --}}
                                        data-id="{{ $dep->id }}"
                                        data-nombre="{{ $dep->nombre }}"
                                        data-descripcion="{{ $dep->descripcion }}"
                                        data-jefe-id="{{ $dep->jefeEmpleado?->id ?? '' }}">

                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                {{-- Formulario eliminar --}}
                                <form action="{{ route('departamentos.destroy', $dep->id) }}"
                                      method="POST"
                                      class="d-inline delete-form">

                                    @csrf {{-- Token CSRF --}}
                                    @method('DELETE') {{-- Método DELETE --}}

                                    {{-- Botón eliminar --}}
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-departamento"
                                            data-nombre="{{ $dep->nombre }}">

                                        <i class="fa-solid fa-trash"></i>
                                    </button>
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

{{-- =====================================================
     OFFCANVAS NUEVO DEPARTAMENTO
===================================================== --}}
<div class="offcanvas offcanvas-end" id="offcanvasNuevoDepartamento">

    {{-- Encabezado --}}
    <div class="offcanvas-header bg-primary text-white">

        <h5>Nuevo Departamento</h5>

        {{-- Botón cerrar --}}
        <button class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"></button>
    </div>

    {{-- Cuerpo del formulario --}}
    <div class="offcanvas-body">

        {{-- Formulario guardar --}}
        <form action="{{ route('departamentos.store') }}"
              method="POST">

            @csrf {{-- Seguridad CSRF --}}

            {{-- Campo nombre --}}
            <div class="mb-3">

                <label class="form-label fw-bold">
                    Nombre
                </label>

                <input 
                    id="edit_nombre_departamento"
                    name="nombre"
                    class="form-control"
                    required>
            </div>

            {{-- Campo descripción --}}
            <div class="mb-3">

                <label class="form-label fw-bold">
                    Descripción
                </label>

                <textarea
                    id="edit_descripcion_departamento"
                    name="descripcion"
                    class="form-control"
                    required></textarea>
            </div>

            {{-- Selector jefe --}}
            <div class="mb-3">

                <label class="form-label fw-bold">
                    Jefe del Departamento
                </label>

                <select name="jefe_empleado_id"
                        class="form-select select2">

                    <option value="">
                        -- Sin asignar --
                    </option>

                    {{-- Listado de empleados --}}
                    @foreach($empleados as $emp)

                        <option value="{{ $emp->id }}">
                            {{ $emp->nombre }} {{ $emp->apellido }}
                        </option>

                    @endforeach
                </select>
            </div>

            {{-- Botón guardar --}}
            <button class="btn btn-primary w-100">
                Guardar
            </button>

        </form>
    </div>
</div>

{{-- =====================================================
     MODAL EDITAR DEPARTAMENTO
===================================================== --}}
<div class="modal fade"
     id="modalEditarDepartamento"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg">

            {{-- Encabezado modal --}}
            <div class="modal-header text-white">

                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-pen-to-square me-2"></i>
                    Editar Departamento
                </h5>

                {{-- Botón cerrar --}}
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            {{-- Cuerpo modal --}}
            <div class="modal-body">

               {{-- Formulario editar --}}
               <form id="formEditarDepartamento"
                     method="POST"
                     action="{{ session('edit_url') ?? '' }}">

                    @csrf
                    @method('PUT')

                    {{-- Campo nombre --}}
                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Nombre del Departamento
                        </label>

                       <input type="text"
                              name="nombre"
                              id="modal_edit_nombre_departamento" 
                              class="form-control border-2" 
                              value="{{ old('nombre') }}"
                              required>
                    </div>

                    {{-- Campo descripción --}}
                    <div class="mb-3">

                     <label class="form-label fw-bold">
                        Descripción
                     </label>

                      <textarea name="descripcion"
                                id="modal_edit_descripcion_departamento" 
                                class="form-control border-2"
                                rows="4"
                                required>{{ old('descripcion') }}</textarea>
                   </div>

                    {{-- Selector jefe --}}
                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Jefe del Departamento
                        </label>

                        <select name="jefe_empleado_id"
                                id="modal_edit_jefe_departamento"
                                class="form-select select2">

                           <option value="">
                               -- Sin asignar --
                           </option>

                           {{-- Recorrido de empleados --}}
                           @foreach($empleados as $emp)

                               <option value="{{ $emp->id }}" 

                                   {{-- Mantener seleccionado --}}
                                   {{ (old('jefe_empleado_id') == $emp->id || session('id_jefe_original') == $emp->id) ? 'selected' : '' }}>

                                   {{ $emp->nombre }} {{ $emp->apellido }} 

                                   {{-- Validación si ya es jefe --}}
                                   {{ $emp->departamentoAsignado ? '(Ya es jefe)' : '' }}

                                </option>

                            @endforeach
                       </select>
                     </div>

                    {{-- Botones acciones --}}
                    <div class="d-grid gap-2 mt-4">

                      {{-- Actualizar --}}
                      <button type="submit"
                              class="btn text-white rounded-pill px-4"
                              style="background-color: #054084;">

                         <i class="fa-solid fa-rotate me-2"></i>
                         Actualizar
                      </button>

                      {{-- Cancelar --}}
                     <button type="button"
                             class="btn btn-secondary btn-lg fw-bold"
                             data-bs-dismiss="modal">

                         Cancelar
                     </button>
                  </div>
              </form>
            </div>

        </div>
    </div>
</div>

{{-- =====================================================
     SCRIPT PRINCIPAL
===================================================== --}}
<script>

document.addEventListener('DOMContentLoaded', function () {

   /* =====================================================
   ALERTA ERROR (Integridad referencial)
   ===================================================== */
   @if(session('error'))
     Swal.fire({
         title: '¡Acción no permitida!',
         text: "{{ session('error') }}",
         icon: 'error',
         confirmButtonColor: '#dc3545',
         customClass: {
             popup: 'rounded-4 shadow-lg'
            }
        });
   @endif

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
            timerProgressBar: false,

            customClass: {
                popup: 'rounded-4 p-5 shadow-lg',
                title: 'fw-bold text-dark fs-2 mb-3',
                htmlContainer: 'text-muted fs-5'
            }
        });

    @endif

    
    /* =====================================================
       ALERTA WARNING Y REABRIR MODAL
    ===================================================== */
    @if(session('warning'))

        // Mostrar alerta warning
        Swal.fire({
            title: '¡Advertencia!',
            text: "{{ session('warning') }}",
            icon: 'warning',
            confirmButtonColor: '#054084',

            customClass: {
                popup: 'rounded-4 shadow-lg'
            }
        });

        // Reabrir modal automáticamente
        const modalElement = document.getElementById('modalEditarDepartamento');

        if (modalElement) {

            const editModal = new bootstrap.Modal(modalElement);

            // Refrescar Select2
            $('#modal_edit_jefe_departamento').trigger('change');

            editModal.show();
        }

    @endif

    /* =====================================================
       CONFIRMACIÓN ELIMINAR
    ===================================================== */
    document.querySelectorAll('.btn-delete-departamento').forEach(btn => {

        btn.addEventListener('click', function () {

            const form = this.closest('.delete-form');
            const nombreDep = this.dataset.nombre;

            Swal.fire({

                title: '¿Eliminar departamento?',

                text: `Esta acción eliminará de forma permanente el departamento "${nombreDep}". No se puede deshacer.`,

                icon: 'warning',
                iconColor: '#f8bb86',

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

            }).then(result => {

                // Si confirma eliminación
                if (result.isConfirmed) {

                    form.submit();
                }
            });
        });
    });

    /* =====================================================
       MODAL EDITAR DINÁMICO
    ===================================================== */
    document.querySelectorAll('.btn-edit-departamento').forEach(btn => {

        btn.addEventListener('click', function () {

            // Cargar nombre
            document.getElementById('modal_edit_nombre_departamento').value =
                this.dataset.nombre;

            // Cargar descripción
            document.getElementById('modal_edit_descripcion_departamento').value =
                this.dataset.descripcion;

            // Obtener jefe actual
            const jefeId = this.dataset.jefeId;

            // Referencia Select2
            const selectJefe = $('#modal_edit_jefe_departamento');

            // Seleccionar jefe
            selectJefe.val(jefeId ? jefeId : '').trigger('change');

            // Asignar ruta dinámica
            document.getElementById('formEditarDepartamento').action =
                `/departamentos/${this.dataset.id}`;

            // Mostrar modal
            const editModal = new bootstrap.Modal(
                document.getElementById('modalEditarDepartamento')
            );

            editModal.show();
        });
    });

});

</script>

@endsection {{-- Fin de la sección content --}}