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
                            <label class="form-label fw-bold text-dark">1. Tipo de Evaluación</label>
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
                        <div class="col-md-3 border-end bg-white seccion-paso-2" style="display: none;">
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
                        <div class="col-md-6 bg-white seccion-paso-3" style="display: none;">
                            <div class="row h-100">
                                {{-- COLUMNA EVALUADOS --}}
                                <div class="col-6 border-end">
                                    <label class="form-label fw-bold text-danger p-2"><i class="fas fa-users me-2"></i>Evaluados</label>
                                    <div class="p-2 overflow-auto" style="max-height: 400px;">
                                        @foreach($soloEmpleados as $emp)
                                        <div class="form-check mb-2 item-colaborador" data-dept="{{ $emp->departamento_id }}">
                                            <input class="form-check-input" type="checkbox" name="empleado_id[]" value="{{ $emp->id }}" id="emp_{{ $f->id }}_{{ $emp->id }}">
                                            <label class="form-check-label small" for="emp_{{ $f->id }}_{{ $emp->id }}">
                                                {{ $emp->nombre }} {{ $emp->apellido }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- COLUMNA JEFES --}}
                                <div class="col-6 seccion-jefes-col">
                                    <label class="form-label fw-bold text-primary p-2"><i class="fas fa-user-tie me-2"></i>Jefe Evaluador</label>
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

            <form action="{{ route('formulario.store') }}" method="POST">
                @csrf

                <div class="offcanvas-header bg-primary text-white py-4 px-4">
                   <h5 class="offcanvas-title fw-bold">
                        <i class="fas fa-file-alt"></i>
                        Nuevo Formulario
                    </h5>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">

                    {{-- NOMBRE --}}
              <div class="modal-body">
                    {{-- Alerta de error específica dentro del modal --}}
                    @if($errors->has('nombre'))
                        <div class="alert alert-danger py-2 shadow-sm">
                            <i class="fas fa-times-circle mr-2"></i> {{ $errors->first('nombre') }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Formulario</label>
                        <input type="text" name="nombre" 
                               class="form-control @error('nombre') is-invalid @enderror" 
                               value="{{ old('nombre') }}" required>
                    </div>

                    @if($errors->has('nombre'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Error:</strong> {{ $errors->first('nombre') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

                    {{-- PROYECTO --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Proyecto o Meta Finalizado
                        </label>

                        <select name="proyecto_id"
                                class="form-select"
                                required>

                            <option value="">
                                Seleccione un proyecto o meta
                            </option>

                            @foreach($proyectos as $proyecto)

                                <option value="{{ $proyecto->id }}">
                                    {{ $proyecto->nombre }}
                                </option>

                            @endforeach

                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="button" id="btnGuardarFormulario" class="btn btn-primary">
                      Guardar Formulario
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>



{{-- SCRIPTS --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    console.log("Sistema de Asignación IHCI Iniciado");

    // 1. Mostrar/Ocultar secciones al elegir el tipo
    $(document).on('change', '.selector-tipo-eval', function() {
        let modal = $(this).closest('.modal');
        let tipo = $(this).val();
        
      if (tipo) {
        // Mostramos las secciones de Departamentos y Listados
        modal.find('.seccion-paso-2').attr('style', 'display: block !important');
        modal.find('.seccion-paso-3').attr('style', 'display: block !important');

        if (tipo === 'Autoevaluacion') {
            // Ocultamos la columna de jefes
            modal.find('.seccion-jefes-col').hide();
            
            // LIMPIEZA: Desmarcamos cualquier jefe que haya quedado seleccionado por error
            modal.find('.item-jefe input[type="checkbox"]').prop('checked', false);
            // Limpiar también los inputs de peso
            modal.find('input[name^="peso_jefe"]').val('');
            
            console.log("Modo Autoevaluación: Jefes desmarcados y ocultos.");
        } else {
            // Si es Evaluación de Jefe, mostramos la columna
            modal.find('.seccion-jefes-col').attr('style', 'display: block !important');
        }
    } else {
        // Si no hay nada seleccionado, ocultamos todo el flujo
        modal.find('.seccion-paso-2, .seccion-paso-3').attr('style', 'display: none !important');
    }
    });

    // 2. Filtro jerárquico por departamento
    $(document).on('click', '.btn-filtro-general', function() {
        let modal = $(this).closest('.modal');
        let deptId = $(this).data('dept');

        // Estilo de botones de departamento
        modal.find('.btn-filtro-general').removeClass('active bg-primary text-white');
        $(this).addClass('active bg-primary text-white');

        if (deptId === 'todos') {
            modal.find('.item-colaborador, .item-jefe').fadeIn(200);
        } else {
            modal.find('.item-colaborador, .item-jefe').hide();
            modal.find('.item-colaborador[data-dept="' + deptId + '"]').fadeIn(200);
            modal.find('.item-jefe[data-dept="' + deptId + '"]').fadeIn(200);
        }
    });
});

//ALERTAS 
$(document).ready(function() {
    // Escuchamos el clic en el botón de guardar del modal
    $('#btnGuardarFormulario').on('click', function(e) {
        e.preventDefault();

        // Obtenemos los datos del modal
        let form = $(this).closest('form');
        let formData = {
            nombre: form.find('input[name="nombre"]').val(),
            proyecto_id: form.find('select[name="proyecto_id"]').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: form.attr('action'),
            type: "POST",
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Si es exitoso, redirigimos a la edición de preguntas
                Swal.fire({
                    icon: 'success',
                    title: '¡Logrado!',
                    text: 'Formulario creado, redirigiendo...',
                    showConfirmButton: false,
                    timer: 1000
                }).then(() => {
                    window.location.href = response.redirect;
                });
            },
            error: function(xhr) {
                // Si el error es 422 (Validación de Laravel)
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    Swal.fire({
                        icon: 'error',
                        title: 'Nombre Duplicado',
                        text: errors.nombre ? errors.nombre[0] : 'Este formulario ya existe.',
                        confirmButtonColor: '#d33'
                    });
                } else {
                    // Cualquier otro error técnico
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo procesar la solicitud.',
                        confirmButtonColor: '#d33'
                    });
                }
            }
        });
    });
});
</script>

@endsection
