@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid">
    {{-- Manejo de Alertas de Éxito --}}
    @if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Logrado!',
            text: "{{ session('success') }}",
            showConfirmButton: false,
            timer: 1500
        });
    </script>
    @endif

       <div class="card shadow mb-4">
    {{-- ENCABEZADO FORMAL CON COLORES IHCI (Azul, Blanco, Rojo) --}}
<div class="card border-0 shadow-sm mb-4" style="background-color: #007bff; border-radius: 5px;">
    <div class="card-body py-3 px-4">
        <div class="row align-items-center">
            
            {{-- Lado Izquierdo: Icono y Títulos --}}
            <div class="col-md-8 d-flex align-items-center">
                <div class="mr-3 text-white">
                    <i class="fas fa-file-signature" style="font-size: 1.8rem;"></i>
                </div>
                <div>
                    {{-- Título principal más pequeño para dar jerarquía --}}
                    <p class="mb-0 text-white-50 text-uppercase font-weight-bold" style="font-size: 0.7rem; letter-spacing: 1px;">
                        Configuración de Formulario
                    </p>
                    {{-- Nombre del Formulario: Ahora es el protagonista --}}
                    <div style="cursor: pointer;" 
                         data-bs-toggle="modal" 
                         data-bs-target="#modalConfiguracion"
                         class="d-flex align-items-center">
                        <h4 class="text-white font-weight-bold mb-0 mr-2" style="letter-spacing: 0.5px;">
                            {{ $formulario->nombre }}
                        </h4>
                        <i class="fas fa-edit text-white-50 fa-xs"></i>
                    </div>
                </div>
            </div>

            {{-- Lado Derecho: Badge y Botón --}}
            <div class="col-md-4 text-md-right text-center mt-3 mt-md-0">
                <span class="badge badge-light text-primary px-3 py-2 mr-2 shadow-sm" style="font-size: 0.75rem; border-radius: 4px;">
                    <i class="fas fa-shield-alt mr-1"></i> GTH-2026
                </span>
                <a href="{{ route('formulario.index') }}" 
                   class="btn btn-primary btn-sm border-white shadow-sm font-weight-bold" 
                   style="border: 1.5px solid white; border-radius: 4px; padding: 5px 15px;">
                    <i class="fas fa-arrow-left mr-1"></i> Regresar
                </a>
            </div>
            
        </div>
    </div>
</div>

       {{-- BOTÓN PARA AGREGAR CRITERIOS --}}
<div class="mb-4">
    @if($esNuevo)
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPregunta">
            <i class="fas fa-plus"></i> Agregar Pregunta / Criterio
        </button>
    @else
        {{-- Aquí no ponemos nada, o un mensaje simple si prefieres --}}
        <span class="badge badge-secondary p-2">
            <i class="fas fa-info-circle"></i> Modo Lectura: No se pueden agregar preguntas al editar.
        </span>
    @endif
</div>

            {{-- TABLA DE CRITERIOS (TU CÓDIGO ORIGINAL) --}}
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle">
                    <thead>
                        <tr style="background-color: #f1b3cf; color: #630d31; font-size: 0.85rem;">
                            <th class="text-left" style="width: 35%;">Criterios de Evaluación</th>
                            <th>{{ $formulario->label_5 ?? 'Superior (5)' }}</th>
                            <th>{{ $formulario->label_4 ?? 'Expectativas (4)' }}</th>
                            <th>{{ $formulario->label_3 ?? 'Cumple (3)' }}</th>
                            <th>{{ $formulario->label_2 ?? 'Mejorar (2)' }}</th>
                            <th>{{ $formulario->label_1 ?? 'Insatisfactorio (1)' }}</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($formulario->preguntas as $p)
                        <tr>
                            <td class="text-left" style="background-color: #fce4ec;">
                                <strong>{{ $p->pregunta }}</strong> <br>
                                <small class="text-muted">{{ $p->categoria }}</small>
                            </td>
                            <td><input type="radio" disabled></td>
                            <td><input type="radio" disabled></td>
                            <td><input type="radio" disabled></td>
                            <td><input type="radio" disabled></td>
                            <td><input type="radio" disabled></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-outline-primary btn-sm btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditarPregunta{{ $p->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $p->id }}" action="{{ route('formulario.eliminarPregunta', $p->id) }}" method="POST" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmarEliminacion({{ $p->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- MODAL PARA EDITAR CRITERIO --}}
                       <div class="modal fade" id="modalEditarPregunta{{ $p->id }}" tabindex="-1" aria-labelledby="editLabel{{ $p->id }}" aria-hidden="true">
                          <div class="modal-dialog">
                              <div class="modal-content">
                                 
                                  <form action="{{ route('formulario.actualizarPregunta', $p->id) }}" method="POST">
                                     @csrf
                                     @method('PUT')
                                       <div class="modal-header text-white">
                                            <h5 class="modal-title" id="editLabel{{ $p->id }}">Editar Criterio</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                      </div>
                                      <div class="modal-body text-left">
                                           <div class="mb-3">
                                              <label class="form-label">Pregunta:</label>
                                              <textarea name="pregunta" class="form-control" required rows="3">{{ $p->pregunta }}</textarea>
                                          </div>
                                          <div class="mb-3">
                                              <label class="form-label">Categoría:</label>
                                               <input type="text" name="categoria" class="form-control" value="{{ $p->categoria }}">
                                           </div>
                                      </div>
                                     <div class="modal-footer">
                                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                         <button type="submit" class="btn text-white rounded-pill px-4" style="background-color: #054084;">Actualizar</button>
                                      </div>
                                   </form>
                              </div>
                            </div>
                       </div>
                        @empty
                        <tr>
                            <td colspan="7" class="py-4 text-muted">No hay preguntas agregadas aún.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
      
    </div>
</div>

{{-- MODAL AGREGAR PREGUNTA --}}
<div class="modal fade" id="modalPregunta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('formulario.agregarPregunta', $formulario->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Nueva Pregunta / Criterio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-left">
                    <div class="mb-3">
                        <label>Descripción del Criterio</label>
                        <textarea name="pregunta" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Categoría</label>
                        <input type="text" name="categoria" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar Pregunta</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL CONFIGURAR TÍTULOS --}}
<div class="modal fade" id="modalConfiguracion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('formulario.update', $formulario->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header text-white">
                    <h5 class="modal-title">Configurar Formulario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-left">
                    <div class="mb-3">
                        <label>Nombre del Formulario</label>
                        <input type="text" name="nombre" class="form-control" value="{{ $formulario->nombre }}" required>
                    </div>
                    <hr>
                    <label><b>Títulos de las Columnas:</b></label>
                    <input type="text" name="label_5" class="form-control mb-2" value="{{ $formulario->label_5 }}" placeholder="Superior (5)">
                    <input type="text" name="label_4" class="form-control mb-2" value="{{ $formulario->label_4 }}" placeholder="Expectativas (4)">
                    <input type="text" name="label_3" class="form-control mb-2" value="{{ $formulario->label_3 }}" placeholder="Cumple (3)">
                    <input type="text" name="label_2" class="form-control mb-2" value="{{ $formulario->label_2 }}" placeholder="Mejorar (2)">
                    <input type="text" name="label_1" class="form-control mb-2" value="{{ $formulario->label_1 }}" placeholder="No Sat (1)">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn text-white rounded-pill px-4" style="background-color: #054084;">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar criterio?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        })
    }
</script>
@endsection