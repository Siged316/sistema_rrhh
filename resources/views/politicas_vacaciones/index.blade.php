{{-- Extiende el layout principal de la aplicación --}}
@extends('layouts.app')

{{-- Inicio de la sección content --}}
@section('content')

{{-- Contenedor principal de la vista --}}
<div class="container-fluid roles-section py-4">
    <div class="row justify-content-center">
        <div class="col-md-11">

            {{-- Tarjeta principal --}}
            <div class="card shadow-lg border-0">

                {{-- Encabezado de la tarjeta --}}
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <h4 class="mb-0">
                        {{-- Icono y título --}}
                        <i class="fa-solid fa-calendar-days me-2"></i> Políticas de Vacaciones
                    </h4>

                    {{-- Botones de acciones --}}
                    <div class="d-flex gap-2">
                        
                        {{-- Botón para abrir offcanvas de nueva política --}}
                        <button class="btn btn-dark btn-sm shadow-sm fw-bold"
                                type="button"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#offcanvasNuevaPolitica">
                            <i class="fa-solid fa-plus-circle me-1"></i> Nueva Política
                        </button>
                    </div>
                </div>

                {{-- Cuerpo de la tarjeta --}}
                <div class="card-body px-4">

                    {{-- Mensaje de éxito --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mt-3" 
                             role="alert" id="success-alert">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Mensaje de ERROR (Este es el que te falta) --}}
                  @if(session('error'))
                     <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mt-3" role="alert">
                         <i class="fa-solid fa-triangle-exclamation me-2"></i>
                         {{ session('error') }}
                           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                       </div>
                    @endif

                    {{-- Tabla de políticas --}}
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered table-hover align-middle shadow-sm">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">TIPO DE CONTRATO</th>
                                    <th class="text-center">AÑO ANTIGÜEDAD</th>
                                    <th class="text-center">DÍAS ANUALES</th>
                                    <th class="text-center" style="width:150px;">ACCIONES</th>
                                </tr>
                            </thead>

                            <tbody>
                                {{-- Recorrido de políticas --}}
                                @foreach($politicas as $politica)
                                <tr>
                                    {{-- Tipo de contrato --}}
                                    <td class="ps-4 fw-bold text-secondary">
                                      <div class="d-flex align-items-center">
                                         <i class="fa-solid fa-file-contract me-2 text-primary"></i>
                                          {{-- Input conectado al formulario de actualización --}}
                                          <input type="text" 
                                             name="tipo_contrato" 
                                             form="form-update-{{ $politica->id }}"
                                             value="{{ $politica->tipo_contrato }}" 
                                             class="form-control form-control-sm border-0 bg-light fw-bold"
                                             style="max-width: 200px;"
                                             placeholder="Nombre de la política..."
                                            required>
                                       </div>
                                    </td>

                                    {{-- Columna del Año --}}
                                    <td class="text-center fw-bold text-primary">
                                       Año {{ $politica->anio_antiguedad }}
                                   </td>

                                    {{-- Formulario para actualizar días --}}
                                    <td class="text-center">
                                        <form method="POST" 
                                              action="{{ route('politicas.update', $politica->id) }}" 
                                              id="form-update-{{ $politica->id }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="input-group input-group-sm mx-auto" style="max-width: 100px;">
                                                <input type="number" 
                                                       name="dias_anuales" 
                                                       value="{{ $politica->dias_anuales }}" 
                                                       class="form-control text-center border-2 border-primary-subtle fw-bold">
                                            </div>
                                        </form>
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="text-center">
                                        <div class="btn-group shadow-sm">

                                            {{-- Botón guardar cambios --}}
                                            <button type="submit" 
                                                    form="form-update-{{ $politica->id }}"
                                                    class="btn btn-outline-warning btn-sm" 
                                                    title="Guardar Cambios">
                                                <i class="fa-solid fa-floppy-disk"></i>
                                            </button>

                                            {{-- Botón eliminar con SweetAlert --}}
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm" 
                                                    onclick="confirmarEliminacion('{{ $politica->id }}', '{{ $politica->tipo_contrato }}')"
                                                    title="Eliminar Política">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>

                                            {{-- Formulario oculto para eliminación --}}
                                            <form id="delete-form-{{ $politica->id }}" 
                                                  action="{{ route('politicas.destroy', $politica->id) }}" 
                                                  method="POST" 
                                                  style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
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
    </div>
</div>

{{-- Offcanvas para registrar nueva política --}}
<div class="offcanvas offcanvas-end border-0 shadow" tabindex="-1" id="offcanvasNuevaPolitica">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title fw-bold">
            <i class="fa-solid fa-plus-circle me-2"></i> Registrar Nueva Política
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

   <div class="offcanvas-body">
      <form method="POST" action="{{ route('politicas.store') }}" id="formPolitica">
         @csrf

         <div class="mb-4">
             <label class="form-label fw-bold text-secondary">Categoría de Contrato</label>
            <select id="tipo_contrato_select" class="form-select border-2" required>
                <option value="" selected disabled>Seleccione...</option>
                <option value="permanente">Permanente (Escala de Ley)</option>
                <option value="otros">Otros (Nombre Personalizado)</option>
             </select>
          </div>
  
          <div id="contenedor_nombre" class="mb-4" style="display: none;">
             <label class="form-label fw-bold text-secondary">Nombre del Contrato</label>
             <input type="text" name="tipo_contrato" id="input_nombre_real" class="form-control border-2" placeholder="Ej: Temporal, Por Hora...">
         </div>

          <div id="seccion_permanente" style="display: none;" class="bg-light p-3 rounded border">
             <h6 class="fw-bold text-primary mb-3"><i class="fas fa-balance-scale me-1"></i> Escala de Días por Año</h6>
            
              @foreach([1 => '1er Año', 2 => '2do Año', 3 => '3er Año', 4 => '4to Año o más'] as $num => $label)
                 <div class="row g-2 align-items-center mb-2">
                     <div class="col-7"><small>Al cumplir {{ $label }}:</small></div>
                        <div class="col-5">
                         <input type="number" name="dias_permanente[{{ $num }}]" class="form-control form-control-sm input-escala" min="1" max="100">
                       </div>
                 </div>
               @endforeach
          </div>

          <div id="seccion_otros" style="display: none;">
              <label class="form-label fw-bold text-secondary">Días Anuales</label>
              <input type="number" name="dias_fijos" id="input_dias_fijos" class="form-control border-2" placeholder="Ej: 15" min="1" max="100">
          </div>

          <div class="d-grid gap-2 mt-4">
             <button type="submit" class="btn btn-primary btn-lg shadow fw-bold">
                  <i class="fa-solid fa-save me-2"></i> Guardar Política
              </button>
          </div>
       </form>
   </div>
</div>

{{-- SweetAlert --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Offcanvas para registrar nueva política
    document.getElementById('tipo_contrato_select').addEventListener('change', function() {
     const seccionPerm = document.getElementById('seccion_permanente');
     const seccionOtros = document.getElementById('seccion_otros');
     const contenedorNombre = document.getElementById('contenedor_nombre');
     const inputNombreReal = document.getElementById('input_nombre_real');
     const inputDiasFijos = document.getElementById('input_dias_fijos');
     const inputsEscala = document.querySelectorAll('.input-escala');

     if (this.value === 'permanente') {
         // Mostrar/Ocultar
         seccionPerm.style.display = 'block';
         seccionOtros.style.display = 'none';
         contenedorNombre.style.display = 'none';

         // Lógica de valores
         inputNombreReal.value = 'permanente';

         // Validaciones: Activar escala, desactivar otros
         inputNombreReal.required = false;
         inputDiasFijos.required = false;
         inputsEscala.forEach(input => {
             input.required = true;
                if(!input.value) input.value = input.name.includes('[1]') ? 10 : (input.name.includes('[2]') ? 12 : (input.name.includes('[3]') ? 15 : 20));
           });

        } else if (this.value === 'otros') {
         // Mostrar/Ocultar
         seccionPerm.style.display = 'none';
         seccionOtros.style.display = 'block';
         contenedorNombre.style.display = 'block';

         // Lógica de valores
         inputNombreReal.value = '';

         // Validaciones: Activar otros, desactivar escala
         inputNombreReal.required = true;
         inputDiasFijos.required = true;
         inputsEscala.forEach(input => input.required = false);
        
         inputNombreReal.focus();
       }
   });
</script>

<script>
 document.addEventListener('DOMContentLoaded', function() {
    //EDITAR
    // Seleccionamos todos los formularios que empiezan con 'form-update-'
    const forms = document.querySelectorAll('form[id^="form-update-"]');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Buscamos el input de texto asociado a este formulario específico
            // Nota: Como el input está fuera del form, usamos el atributo 'form' para encontrarlo
            const id = this.id.replace('form-update-', '');
            const inputNombre = document.querySelector(`input[name="tipo_contrato"][form="form-update-${id}"]`);

            if (!inputNombre.value.trim()) {
                e.preventDefault(); // Detiene el envío
                
                // Efecto visual de error
                inputNombre.classList.add('is-invalid');
                inputNombre.focus();
                
                // Opcional: Una alerta rápida
                alert('El nombre de la política no puede estar vacío.');
            } else {
                inputNombre.classList.remove('is-invalid');
            }
        });
    });
});
    
</script>

<script>
    // Confirmación de eliminación con SweetAlert
    function confirmarEliminacion(id, nombreContrato) {
        Swal.fire({
            title: '¿Eliminar política?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 shadow',
                title: 'fw-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }

    // Ocultar alerta de éxito automáticamente
    document.addEventListener('DOMContentLoaded', function () {
      // Seleccionamos todas las alertas (éxito y error)
     const alerts = document.querySelectorAll('.alert');
    
      alerts.forEach(function(alert) {
          setTimeout(() => {
              // Efecto de desvanecimiento suave
              alert.style.transition = "opacity 0.5s ease";
              alert.style.opacity = "0";
            
              // Eliminar del DOM después del desvanecimiento
               setTimeout(() => alert.remove(), 500);
           }, 4000); // 4 segundos de visibilidad
       });
   });
</script>

{{-- Fin de la sección content --}}
@endsection



