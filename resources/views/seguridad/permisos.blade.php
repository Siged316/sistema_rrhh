{{-- Extiende el layout principal de la aplicación --}}
@extends('layouts.app')

{{-- Inicio de la sección de contenido --}}
@section('content')

{{-- Contenedor principal de la vista --}}
<div class="container-fluid roles-section py-4 bg-light" style="min-height: 90vh;">
    <div class="row justify-content-center">
        <div class="col-md-10">

            {{-- Tarjeta principal que contiene toda la gestión de permisos --}}
            <div class="card shadow-lg border-0">
                
                {{-- Encabezado de la tarjeta --}}
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <h4 class="mb-0">
                        {{-- Icono y título del módulo --}}
                        <i class="fa-solid fa-shield-halved me-2"></i> Permisos del Sistema
                    </h4>
                </div>

                {{-- Cuerpo de la tarjeta --}}
                <div class="card-body px-4">

                    {{-- ================= ALERTAS NATIVAS COMENTADAS PARA EVITAR DUPLICIDAD ================= --}}
                    {{-- Sección de selección de rol --}}
                    <div class="row my-4">
                       <div class="col-md-6 offset-md-3">
                          {{-- Formulario para cambiar el rol activo --}}
                          <form method="GET" action="{{ route('permisos_sistema.index') }}">
   
                               <div class="input-group shadow-sm">
                                 <a href="{{ route('permisos_sistema.index') }}" class="btn btn-secondary">
                                     <i class="fa-solid fa-rotate-left"></i> Limpiar Selección
                                 </a>
                                  {{-- Etiqueta del selector --}}
                                 <span class="input-group-text bg-primary text-white border-dark">
                                     <i class="fa-solid fa-user-gear me-2"></i> Rol Actual:
                                 </span>

                                  {{-- Selector de roles --}}
                                  <select name="role_id" class="form-select border-2 fw-bold" onchange="this.form.submit()" autocomplete="off">
                                      <option value="" {{ !$roleId ? 'selected' : '' }} disabled>Seleccione un rol</option>
                                       @foreach($roles as $rol)
                                          <option value="{{ $rol->id }}" {{ $rol->id == $roleId ? 'selected' : '' }}>
                                              {{ $rol->nombre }}
                                          </option>
                                       @endforeach
                                  </select>
                               </div>

                               {{-- Texto de ayuda --}}
                               <small class="text-muted d-block mt-2 text-center">
                                  Cambia el rol para visualizar y editar sus accesos específicos.
                               </small>
                          </form>
                       </div>
                    </div>

                    {{-- Separador visual --}}
                    <hr class="text-muted opacity-25">

                    {{-- Formulario para guardar los permisos --}}
                    <form method="POST" action="{{ route('permisos_sistema.update') }}" id="form-modulo-permisos">
                        @csrf

                        {{-- Campo oculto con el ID del rol seleccionado --}}
                        <input type="hidden" name="role_id" value="{{ $roleId }}">

                        {{-- Tabla de módulos del sistema --}}
                        <div class="table-responsive mt-2">
                            <table class="table table-hover align-middle border">

                                {{-- Encabezado de la tabla --}}
                                <thead class="table-secondary">
                                    <tr>
                                        <th class="ps-4 py-3">Módulo del Sistema</th>
                                        <th class="text-center" style="width: 180px;">Estado de Visibilidad</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {{-- Definición de los módulos --}}
                                    @foreach([
                                      'seguridad' => [
                                          'label' => 'Módulo de Seguridad', 
                                          'icon' => 'fa-lock', 
                                          'color' => 'text-danger'
                                        ],
                                     'administración' => [
                                         'label' => 'Módulo de Administración', 
                                         'icon' => 'fa-gears', 
                                         'color' => 'text-primary'
                                        ],
                                        'permisos_laborales' => [
                                          'label' => 'Módulo de Permisos Laborales', 
                                          'icon' => 'fa-file-signature', 
                                          'color' => 'text-success'
                                        ],
                                        'informes' => [
                                          'label' => 'Módulo de Informes y Estadísticas', 
                                          'icon' => 'fa-chart-line', 
                                          'color' => 'text-info'
                                        ],
                                        'proyectos' => [
                                         'label' => 'Módulo de Proyectos', 
                                         'icon' => 'fa-diagram-project', 
                                         'color' => 'text-warning'
                                        ]
                                    ] as $key => $data)

                                    {{-- Fila del módulo --}}
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center">
                                                {{-- Icono del módulo --}}
                                                <div class="bg-light p-2 rounded-circle me-3 border">
                                                    <i class="fa-solid {{ $data['icon'] }} {{ $data['color'] }} fs-5" style="width: 25px; text-align: center;"></i>
                                                </div>
                                                {{-- Descripción del módulo --}}
                                                <div>
                                                    <span class="fw-bold d-block">{{ $data['label'] }}</span>
                                                    <small class="text-muted">Permite al usuario ver esta sección en el menú.</small>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Switch de activación --}}
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" 
                                                       role="switch"
                                                       style="width: 3em; height: 1.5em; cursor: pointer;"
                                                       name="modulos[{{ $key }}]"
                                                       {{ !$roleId ? 'disabled' : '' }}
                                                       {{ ($permisos[$key] ?? 0) == 1 ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Botón para guardar los cambios --}}
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4 mb-3">
                          <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm" {{ !$roleId ? 'disabled' : '' }}>
                              <i class="fa-solid fa-rotate me-2"></i> Actualizar
                          </button>
                        </div>
                    </form>

                </div> 
            </div> 

            {{-- Texto de pie de página --}}
            <p class="text-center text-muted mt-4 small">
                © {{ date('Y') }} Sistema de Gestión IHCI - Control de Acceso
            </p>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ===== ALERTA MODAL DE ÉXITO ===== */
    @if(session('success_permisos'))
        Swal.fire({
            title: '¡Permisos Actualizados!',
            text: "{{ session('success_permisos') }}",
            icon: 'success',
            iconColor: '#a5dc86',
            showConfirmButton: false,
            timer: 3000,
            customClass: {
                popup: 'rounded-4 p-5 shadow-lg',
                title: 'fw-bold text-dark fs-3 mb-3',
                htmlContainer: 'text-muted fs-5'
            }
        });
    @endif

    /* ===== ALERTA MODAL DE INFORMACIÓN ===== */
    @if(session('info'))
        Swal.fire({
            title: 'Información',
            text: "{{ session('info') }}",
            icon: 'info',
            iconColor: '#054084',
            confirmButtonColor: '#054084',
            confirmButtonText: 'Entendido',
            customClass: {
                popup: 'rounded-4 shadow-lg',
                title: 'fw-bold text-primary'
            }
        });
    @endif

    /* ===== AISLAMIENTO DE ACCIONES EN EL FORMULARIO ===== */
    const formPermisos = document.getElementById('form-modulo-permisos');
    if (formPermisos) {
        formPermisos.addEventListener('submit', function(e) {
            e.stopPropagation(); 
        });
    }
});
</script>

{{-- Fin de la sección de contenido --}}
@endsection
