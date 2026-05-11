@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        {{-- Formulario de Carga --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header modal-header text-white py-2">
                    <h5 class="card-title mb-0"><i class="fas fa-pen-nib me-2"></i>Registrar Nueva Firma</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('firmas.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-bold">Empleado</label>
                            <select name="empleado_id" class="form-select form-select-lg border-2 shadow-none" required>
                                <option value="" selected disabled>Seleccione un empleado...</option>
                                @foreach($empleados as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->nombre }} {{ $emp->apellido }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Imagen de la Firma</label>
                            <input type="file" name="foto" class="form-control border-2 shadow-none" accept="image/*" required>
                            <div class="form-text mt-2 text-muted">
                                <i class="fas fa-info-circle me-1"></i> Formatos: <strong>JPG, PNG</strong>. (Máx: 2MB).
                            </div>
                        </div>

                        <button type="submit" class="btn text-white rounded-pill px-4" style="background-color: #054084;">
                            <i class="fas fa-save me-2"></i>Guardar Firma
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Listado de Firmas con Buscador --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0 text-white fw-bold">Firmas Registradas</h5>
                        </div>
                        <div class="col-md-6">
                            {{-- BUSCADOR EN TIEMPO REAL --}}
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="searchInput" class="form-control bg-light border-0 shadow-none" placeholder="Buscar empleado...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered table-hover align-middle shadow-sm" id="firmasTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-4 py-3">Empleado</th>
                                    <th class="py-3 text-center">Firma Digital</th>
                                    <th class="py-3 text-center">Estado</th>
                                    <th class="pe-4 py-3 text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($firmas as $f)
                                <tr class="firma-row">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                {{ substr($f->empleado->nombre, 0, 1) }}{{ substr($f->empleado->apellido, 0, 1) }}
                                            </div>
                                            <div class="search-target">
                                                <h6 class="mb-0 fw-bold">{{ $f->empleado->nombre }} {{ $f->empleado->apellido }}</h6>
                                                <small class="text-muted small">ID: #{{ $f->empleado_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="p-2 border rounded bg-white d-inline-block shadow-sm">
                                            @php
                                                $datos = is_resource($f->imagen_path) ? stream_get_contents($f->imagen_path) : $f->imagen_path;
                                            @endphp
                                            <img src="data:image/png;base64,{{ base64_encode($datos) }}" 
                                                 style="height: 40px; width: auto; mix-blend-mode: multiply;" alt="Firma">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-success px-3">Activa</span>
                                    </td>

                                    {{-- 2. Celda de Acciones --}}
                                    <td class="pe-4 text-end">
                                      <div class="d-inline-flex align-items-center gap-2">
                                         {{-- Botón para Modificar --}}
                                          <button type="button" 
                                             class="btn btn-sm rounded-circle" 
                                              style="color: #003366; border-color: #003366;"
                                              onclick="prepararModificacion('{{ $f->empleado_id }}')" 
                                              title="Cambiar Firma">
                                               <i class="fas fa-sync-alt"></i>
                                            </button>

                                           {{-- Botón para Eliminar (Formulario Inline) --}}
                                           <form action="{{ route('firmas.destroy', $f->id) }}" method="POST" class="d-inline">
                                             @csrf
                                             @method('DELETE')
                                                  <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" 
                                                      onclick="confirmarEliminacion(this.form)">
                                                     <i class="fas fa-trash-alt"></i>
                                                   </button>
                                           </form>
                                      </div>
                                  </td>
                                </tr>
                                @empty
                                <tr id="noResults">
                                    <td colspan="4" class="text-center py-5 text-muted small">No hay firmas registradas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT PARA EL BUSCADOR --}}
<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.firma-row');
        
        rows.forEach(row => {
            let text = row.querySelector('.search-target').innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

{{-- SCRIPT PARA ELIMINAR--}}
<script>
    function confirmarEliminacion(formulario) {
        Swal.fire({
            title: '¿Eliminar firma?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                formulario.submit(); // Envía el formulario si acepta
            }
        });
    }
</script>

{{-- SCRIPT PARA EL MENSAJE DE ERROR --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Mensaje de ÉXITO (Se quita en 2 segundos)
        @if(session('success'))
            Swal.fire({
                title: '¡Operación Exitosa!',
                text: "{{ session('success') }}",
                icon: 'success',
                timer: 2000, // <--- 2000 milisegundos = 2 segundos
                timerProgressBar: true,
                showConfirmButton: false, // Escondemos el botón para que sea automático
                willClose: () => {
                    console.log('Alerta cerrada automáticamente');
                }
            });
        @endif

        // 2. Mensaje de ERROR DE INTEGRIDAD (Se quita en 4 segundos)
        @if(session('error_integridad'))
            Swal.fire({
                title: 'No se puede eliminar',
                text: "{{ session('error_integridad') }}",
                icon: 'error',
                timer: 4000, // <--- 4 segundos
                timerProgressBar: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Entendido'
            });
        @endif

        // 3. Mensaje de ERROR GENERAL
        @if(session('error'))
            Swal.fire({
                title: 'Error',
                text: "{{ session('error') }}",
                icon: 'error',
                timer: 3000,
                timerProgressBar: true
            });
        @endif
    });
</script>

<script>
    function prepararModificacion(empleadoId) {
        // Selecciona el empleado en el dropdown automáticamente
        const select = document.querySelector('select[name="empleado_id"]');
        select.value = empleadoId;
        
        // Enfoca el input de archivo para que el usuario sepa que debe subir la nueva
        document.querySelector('input[name="foto"]').focus();
        
        // Cambiamos el texto del botón temporalmente
        const btn = document.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-edit me-2"></i>Actualizar Firma Existente';
        btn.classList.replace('btn-success', 'btn-warning');
    }

    // Buscador (el que ya tenías)
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.firma-row');
        rows.forEach(row => {
            let text = row.querySelector('.search-target').innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<style>
    .avatar-sm { font-size: 12px; font-weight: bold; letter-spacing: 1px; }
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
    .sticky-top { top: -1px; z-index: 10; }
</style>
@endsection