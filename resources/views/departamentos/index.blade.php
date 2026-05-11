@extends('layouts.app') {{-- Indica que esta vista hereda la estructura base definida en layouts.app --}}

@section('content') {{-- Inicia la sección "content" que será inyectada en el layout principal --}}
<div class="container-fluid py-4">
    <div class="card shadow border-0">

        <!-- HEADER DEL MÓDULO -->
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h4 class="mb-0">
                <i class="fa-solid fa-building me-2"></i> Gestión de Departamentos
            </h4>

            <button class="btn btn-dark btn-sm" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNuevoDepartamento">
                <i class="fa-solid fa-plus-circle"></i> Nuevo Departamento
            </button>
        </div>

        <!-- CUERPO -->
        <div class="card-body">

         <!-- Mensaje de exito -->
            @if(session('success'))
             <div id="success-alert" class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                 <i class="fa-solid fa-circle-check me-2"></i>
                 {{ session('success') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
           @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle shadow-sm">
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">Departamento</th>
                            <th class="text-center">Descripción</th>
                            <th class="text-center">Jefe</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($departamentos as $dep)
                        <tr>
                            <td>#{{ $dep->id }}</td>
                            <td>{{ $dep->nombre }}</td>
                            <td>{{ $dep->descripcion }}</td>
                            <td>{{ $dep->jefeEmpleado?->nombre ?? '' }} {{ $dep->jefeEmpleado?->apellido ?? '' }}</td>
                            <td class="text-center">
                                <!-- Botón Editar -->
 <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-edit-departamento"
                                        data-id="{{ $dep->id }}"
                                        data-nombre="{{ $dep->nombre }}"
                                        data-descripcion="{{ $dep->descripcion }}"
                                        data-jefe-id="{{ $dep->jefeEmpleado?->id ?? '' }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>


                                <!-- Botón Eliminar -->
                                <form action="{{ route('departamentos.destroy', $dep->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
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

<!-- OFFCANVAS NUEVO DEPARTAMENTO -->
<div class="offcanvas offcanvas-end" id="offcanvasNuevoDepartamento">
    <div class="offcanvas-header bg-primary text-white">
        <h5>Nuevo Departamento</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form action="{{ route('departamentos.store') }}" method="POST">
            @csrf

           <div class="mb-3">
             <label class="form-label fw-bold">Nombre</label>
             <input 
              id="edit_nombre_departamento"
              name="nombre"
              class="form-control"
              required>
          </div>

          <div class="mb-3">
             <label class="form-label fw-bold">Descripción</label>
              <textarea
                  id="edit_descripcion_departamento"
                  name="descripcion"
                  class="form-control"
                required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Jefe del Departamento</label>
                <select name="jefe_empleado_id" class="form-select select2">
                    <option value="">-- Sin asignar --</option>
                    @foreach($empleados as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->nombre }} {{ $emp->apellido }}</option>
                    @endforeach
                </select>
            </div>

            <button class="btn btn-primary w-100">Guardar</button>
        </form>
    </div>
</div>

<!-- MODAL EDITAR DEPARTAMENTO -->
<div class="modal fade" id="modalEditarDepartamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-pen-to-square me-2"></i> Editar Departamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
               <form id="formEditarDepartamento" method="POST" action="{{ session('edit_url') ?? '' }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Departamento</label>
                       <input type="text" name="nombre" id="modal_edit_nombre_departamento" 
                       class="form-control border-2" 
                       value="{{ old('nombre') }}" required>
                   </div>

                  <div class="mb-3">
                     <label class="form-label fw-bold">Descripción</label>
                      <textarea name="descripcion" id="modal_edit_descripcion_departamento" 
                      class="form-control border-2" rows="4" required>{{ old('descripcion') }}</textarea>
                   </div>

                   <div class="mb-3">
                       <label class="form-label fw-bold">Jefe del Departamento</label>
                       <select name="jefe_empleado_id" id="modal_edit_jefe_departamento" class="form-select select2">
                          <option value="">-- Sin asignar --</option>
                          @foreach($empleados as $emp)
                              <option value="{{ $emp->id }}" 
                                  {{ (old('jefe_empleado_id') == $emp->id || session('id_jefe_original') == $emp->id) ? 'selected' : '' }}>
                                  {{ $emp->nombre }} {{ $emp->apellido }} 
                                  {{ $emp->departamentoAsignado ? '(Ya es jefe)' : '' }}
                               </option>
                           @endforeach
                      </select>
                    </div>

                    @if(session('warning'))
                     <div id="alert-disposable" class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                          <i class="fa-solid fa-triangle-exclamation me-2"></i>
                          {{ session('warning') }}
                         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                       </div>
                   @endif

                  <div class="d-grid gap-2 mt-4">
                      <button type="submit" class="btn btn-warning fw-bold text-dark">
                         <i class="fa-solid fa-rotate me-2"></i> Actualizar Cambios
                      </button>
                     <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                         Cancelar
                     </button>
                  </div>
              </form>
            </div>

        </div>
    </div>
</div>

<!-- SCRIPTS -->
 <script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Desaparecer alerta
    const alertElement = document.getElementById('alert-disposable');
    if (alertElement) {
        setTimeout(() => {
            let bsAlert = new bootstrap.Alert(alertElement);
            bsAlert.close();
        }, 4000);
    }

    // 2. REABRIR MODAL SI HAY ERROR (Mantiene datos gracias a old())
   @if(session('warning'))
        const modalElement = document.getElementById('modalEditarDepartamento');
        if (modalElement) {
            const editModal = new bootstrap.Modal(modalElement);
            
            // Forzamos a Select2 a reconocer el cambio de valor (el jefe original)
            $('#modal_edit_jefe_departamento').trigger('change');
            
            editModal.show();
        }
    @endif

    // 3. Lógica normal de botones editar
    document.querySelectorAll('.btn-edit-departamento').forEach(btn => {
        btn.addEventListener('click', function () {
            // Limpiar inputs antes de cargar nuevos (evita mezclar datos viejos)
            document.getElementById('modal_edit_nombre_departamento').value = this.dataset.nombre;
            document.getElementById('modal_edit_descripcion_departamento').value = this.dataset.descripcion;

            const jefeId = this.dataset.jefeId; 
            const selectJefe = $('#modal_edit_jefe_departamento');
            selectJefe.val(jefeId ? jefeId : '').trigger('change');

            document.getElementById('formEditarDepartamento').action = `/departamentos/${this.dataset.id}`;
            
            const editModal = new bootstrap.Modal(document.getElementById('modalEditarDepartamento'));
            editModal.show();
        });
    });
});

 // Mensaje de éxito
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('success-alert');
        if(alert){
            setTimeout(() => {
                // Aplicamos fade out usando clases de Bootstrap
                alert.classList.remove('show');
                alert.classList.add('hide');
            }, 3000); // Desaparece después de 3 segundos
        }
    });
</script>

<script>
  // Mensaje de éxito
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('success-alert');
        if(alert){
            setTimeout(() => {
                // Aplicamos fade out usando clases de Bootstrap
                alert.classList.remove('show');
                alert.classList.add('hide');
            }, 3000); // Desaparece después de 3 segundos
        }
    });
</script>
@endsection