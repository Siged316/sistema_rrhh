@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Tabla de Formularios --}}
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h4 class="mb-0">Gestión de Formularios de Evaluación</h6>
            <button type="button"  class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoFormulario">
                <i class="fas fa-plus"></i> Nuevo Formulario
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Formulario</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($formularios as $f)
                        <tr>
                            <td>{{ $f->id }}</td>
                            <td>{{ $f->nombre }}</td>
                            <td><span class="badge bg-success">Activo</span></td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('formulario.show', $f->id) }}"  class="btn btn-outline-primary btn-sm btn-edit">
                                         <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAsignar{{ $f->id }}">
                                        <i class="fas fa-user-tag"></i> Asignar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODALES DE ASIGNACIÓN (Generados dinámicamente) --}}
@foreach($formularios as $f)
<div class="modal fade" id="modalAsignar{{ $f->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
          
            <form action="{{ route('asignaciones.store') }}" method="POST">
                @csrf
                <input type="hidden" name="formulario_id" value="{{ $f->id }}">
                <input type="hidden" name="proyecto_id" value="{{ $f->proyecto_id }}">

                <div class="modal-header text-white" style="background-color: #003366; border-bottom: 4px solid #d9534f;">
                    <h5 class="modal-title">Configurar Evaluación: {{ $f->nombre }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body bg-light"> 
                    <div class="row">
                        {{-- 1. TIPO DE EVALUACIÓN --}}
                        <div class="col-md-3 border-end">
                            <label class="form-label fw-bold text-dark p-2">1. Tipo de Evaluación</label>
                            <select name="tipo" class="form-select selector-tipo-eval" required>
                                <option value="" selected disabled>-- Seleccione --</option>
                                <option value="Autoevaluacion">Autoevaluación</option>
                                <option value="Evaluacion Jefe">Evaluación de Jefe (Proyecto)</option>
                            </select>
                            
                            <div class="mt-4 p-2 bg-white rounded border">
                                <small class="text-muted d-block mb-1">Instrucciones GTH:</small>
                                <small class="d-block text-secondary">• Seleccione tipo de evaluación para desbloquear secciones.</small>
                            </div>
                        </div>

                        {{-- 2. DEPARTAMENTOS (Oculto inicialmente) --}}
                        <div class="col-md-3 border-end bg-white seccion-paso-2 contenedor-dinamico" style="display: none;">
                            <label class="form-label fw-bold text-dark p-2">2. Departamentos</label>
                            <div class="list-group list-group-flush border-top">
                                <button type="button" class="list-group-item list-group-item-action active btn-filtro-general" data-dept="todos">
                                    <i class="fas fa-layer-group me-2"></i>Todos
                                </button>
                                @foreach($departamentos as $dept)
                                    <button type="button" class="list-group-item list-group-item-action btn-filtro-general" data-dept="{{ $dept->id }}">
                                        {{ $dept->nombre }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- 3. COLABORADORES (Oculto inicialmente) --}}
                        <div class="col-md-6 bg-white seccion-paso-3 contenedor-dinamico" style="display: none;">
                            <div class="row h-100">
                                {{-- COLUMNA EVALUADOS --}}
                                <div class="col-6 border-end">
                                    <label class="form-label fw-bold text-danger p-2">
                                        <input type="checkbox" id="seleccionarTodosEmpleados" class="form-check-input me-1">
                                        <i class="fas fa-users me-2"></i>Evaluados (Todos)
                                   </label>
                                    <div class="p-2 overflow-auto" style="max-height: 400px;">
                                        @foreach($soloEmpleados as $emp)
                                        <div class="form-check mb-2 item-colaborador" data-dept="{{ $emp->departamento_id }}">
                                            <input class="form-check-input check-empleado" type="checkbox" name="empleado_id[]" value="{{ $emp->id }}" id="emp_{{ $f->id }}_{{ $emp->id }}">
                                            <label class="form-check-label small" for="emp_{{ $f->id }}_{{ $emp->id }}">
                                                {{ $emp->nombre }} {{ $emp->apellido }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- COLUMNA JEFES --}}
                                <div class="col-6 seccion-jefes-col">
                                    <label class="form-label fw-bold text-primary p-2">
                                      <input type="checkbox" id="seleccionarTodosJefes" class="form-check-input me-1">
                                      <i class="fas fa-user-tie me-2"></i>Jefe Evaluador (Todos)
                                   </label>
                                    <div class="p-2 overflow-auto" style="max-height: 400px;">
                                        @foreach($todosLosJefes as $j)
                                        <div class="border rounded p-2 mb-2 item-jefe" data-dept="{{ $j->departamento_id }}">
    
                                          <div class="form-check">
                                                <input class="form-check-input jefe-check"
                                                 type="checkbox"
                                                name="evaluador_id[]"
                                                value="{{ $j->id }}"
                                                id="jefe_{{ $f->id }}_{{ $j->id }}">

                                               <label class="form-check-label small fw-bold"
                                                  for="jefe_{{ $f->id }}_{{ $j->id }}">
                                                  {{ $j->nombre }} {{ $j->apellido }}
                                               </label>
                                          </div>

                                          {{-- INPUT DE PESO --}}
                                           <div class="mt-2">
                                              <label class="small text-muted">
                                                 Peso (%)
                                              </label>

                                              <input type="number"
                                                 name="peso_jefe[{{ $j->id }}]"
                                               class="form-control form-control-sm"
                                               min="0"
                                               max="100"
                                               placeholder="Ej: 50">
                                          </div>

                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Habilitar Evaluación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach 


{{-- MODAL NUEVO FORMULARIO --}}
<div class="modal fade" id="modalNuevoFormulario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formNuevoFormulario">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Nuevo Formulario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Formulario</label>
                        <input type="text" name="nombre" id="inputNombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Proyecto o Meta Finalizado</label>
                        <select name="proyecto_id" id="selectProyecto" class="form-select" required>
                            <option value="">Seleccione un proyecto</option>
                            @foreach($proyectos as $proyecto)
                                <option value="{{ $proyecto->id }}">{{ $proyecto->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnGuardarFormulario" class="btn btn-primary">Guardar Formulario</button>
                </div>
            </form>
        </div>
    </div>
</div>



{{-- SCRIPTS --}}
<script>
document.addEventListener("DOMContentLoaded", function() {

 /*
    |--------------------------------------------------------------------------
    | REABRIR MODAL AIGNACIÓN
    |--------------------------------------------------------------------------
    */
    @if (
        old('formulario_id') &&
        (
            $errors->any() ||
            session('success') ||
            session('warning') ||
            session('error')
        )
    )

        let modalEl = document.getElementById(
            'modalAsignar{{ old("formulario_id") }}'
        );

        if (modalEl) {

            let modal = new bootstrap.Modal(modalEl);

            modal.show();
        }

    @endif


    /*
    |--------------------------------------------------------------------------
    | SWEET ALERTS
    |--------------------------------------------------------------------------
    */

    @if(session('success'))

        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '{{ session("success") }}',
            confirmButtonColor: '#3085d6',
            timer: 3000,
            timerProgressBar: true
        });

    @endif


    @if(session('warning'))

        Swal.fire({
            icon: 'warning',
            title: 'Advertencia',
            text: '{{ session("warning") }}',
            confirmButtonColor: '#f0ad4e',
            timer: 3000,
            timerProgressBar: true
        });

    @endif


    @if(session('error'))

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session("error") }}',
            confirmButtonColor: '#d33'
        });

    @endif


    @if($errors->any())

        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `
                <ul style="text-align:left;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            `,
            confirmButtonColor: '#d33'
        });

    @endif


    /*
    |--------------------------------------------------------------------------
    | SELECT TIPO EVALUACIÓN
    |--------------------------------------------------------------------------
    */
    document.body.addEventListener('change', function(e) {

        if (e.target.classList.contains('selector-tipo-eval')) {

            let valor = e.target.value;

            let modalContent = e.target.closest('.modal-content');

            modalContent
                .querySelectorAll('.contenedor-dinamico')
                .forEach(el => {
                    el.style.display = 'block';
                });

            let seccionJefes = modalContent.querySelector('.seccion-jefes-col');

            if (seccionJefes) {

                seccionJefes.style.display =
                    (valor === 'Evaluacion Jefe')
                        ? 'block'
                        : 'none';
            }
        }
    });


    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */
    document.body.addEventListener('click', function(e) {

        let btn = e.target.closest('.btn-filtro-general');

        if (btn) {

            e.preventDefault();

            let modalContent = btn.closest('.modal-content');

            let deptId = btn.getAttribute('data-dept');

            modalContent
                .querySelectorAll('.btn-filtro-general')
                .forEach(b => b.classList.remove('active'));

            btn.classList.add('active');

            modalContent
                .querySelectorAll('.item-colaborador, .item-jefe')
                .forEach(item => {

                    let itemDept = item.getAttribute('data-dept');

                    item.style.display =
                        (deptId === 'todos' || itemDept === deptId)
                            ? 'block'
                            : 'none';
                });
        }
    });


    /*
    |--------------------------------------------------------------------------
    | REABRIR MODAL NUEVO FORMULARIO
    |--------------------------------------------------------------------------
    */
    const btn = document.getElementById('btnGuardarFormulario');
    
    btn.addEventListener('click', function() {
        console.log("Botón presionado (JS Puro)");

        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        formData.append('nombre', document.getElementById('inputNombre').value);
        formData.append('proyecto_id', document.getElementById('selectProyecto').value);

        fetch("{{ route('formulario.store') }}", {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.errors.nombre[0] });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });

     /*
    |--------------------------------------------------------------------------
    | CHECK DE TODOS LOS EVALUADORES
    |--------------------------------------------------------------------------
    */
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function configurarSeleccionMasiva(checkboxMaestroId, claseCheckItem) {
        const maestro = document.getElementById(checkboxMaestroId);
        
        if (!maestro) return;

        maestro.addEventListener('change', function () {
            const esChecked = this.checked;
            // Obtenemos todos los checkboxes de la categoría
            const items = document.querySelectorAll('.' + claseCheckItem);
            
            items.forEach(item => {
                // Buscamos el contenedor padre (item-colaborador o item-jefe)
                const contenedor = item.closest('.item-colaborador') || item.closest('.item-jefe');
                
                // Solo marcamos si el contenedor NO está oculto (display: none)
                if (contenedor && contenedor.style.display !== 'none') {
                    item.checked = esChecked;
                }
            });
        });
    }

    // Inicializar
    configurarSeleccionMasiva('seleccionarTodosEmpleados', 'check-empleado');
    configurarSeleccionMasiva('seleccionarTodosJefes', 'jefe-check');
});
</script>
@endsection
