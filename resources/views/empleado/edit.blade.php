<!-- ========================================================= -->
<!-- MODAL PARA EDITAR INFORMACIÓN DEL EMPLEADO -->
<!-- ========================================================= -->

<div class="modal fade" id="modalEditarEmpleado{{ $empleado->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            
            {{-- Encabezado del modal --}}
            <div class="modal-header  text-white py-3">
                <h5 class="modal-title fw-bold">
                    {{-- Icono + nombre del empleado --}}
                    <i class="fa-solid fa-user-pen me-2"></i>Editar Registro: {{ strtoupper($empleado->nombre) }}
                </h5>

                {{-- Botón para cerrar el modal --}}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            {{-- Formulario para actualizar empleado --}}
            <form action="{{ route('empleado.update', $empleado->id) }}" method="POST" enctype="multipart/form-data">
                @csrf 
                @method('PUT')
                
                <div class="modal-body p-4">
                    <div class="row g-3">

                        {{-- ========================================================= --}}
                        {{-- 0. IDENTIFICACIÓN --}}
                        {{-- ========================================================= --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-black">CÓDIGO DE EMPLEADO</label>

                            {{-- Grupo visual con icono --}}
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fa-solid fa-id-badge"></i>
                                </span>

                                {{-- Campo solo lectura --}}
                                <input type="text"
                                       name="codigo_empleado"
                                       value="{{ old('codigo_empleado', $empleado->codigo_empleado) }}" 
                                       class="form-control bg-light "
                                       readonly
                                       title="El código no se puede editar">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-black">DNI / IDENTIDAD</label>

                            {{-- Grupo visual con icono --}}
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fa-solid fa-fingerprint"></i>
                                </span>

                                {{-- Campo identidad --}}
                                <input type="text"
                                       name="dni"
                                       value="{{ old('dni', $empleado->dni) }}" 
                                       class="form-control"
                                       placeholder="0000-0000-00000"
                                       required>
                            </div>
                        </div>

                        {{-- ========================================================= --}}
                        {{-- 1. DATOS PERSONALES --}}
                        {{-- ========================================================= --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombres</label>

                            {{-- Nombre del empleado --}}
                            <input type="text"
                                   name="nombre"
                                   value="{{ old('nombre', $empleado->nombre) }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Apellidos</label>

                            {{-- Apellidos del empleado --}}
                            <input type="text"
                                   name="apellido"
                                   value="{{ old('apellido', $empleado->apellido) }}"
                                   class="form-control"
                                   required>
                        </div>

                        {{-- ========================================================= --}}
                        {{-- 2. INFORMACIÓN DE CONTACTO --}}
                        {{-- ========================================================= --}}
                        <div class="col-md-6">
                          <label class="form-label fw-bold small text-uppercase">Correo Electrónico</label>

                          {{-- Grupo visual correo --}}
                          <div class="input-group">
                              <span class="input-group-text bg-light">
                                  <i class="fa-solid fa-envelope"></i>
                              </span>

                               {{-- Campo de correo institucional --}}
                               <input type="email"
                                      name="email"
                                      value="{{ old('email', $empleado->email) }}" 
                                      class="form-control"
                                      placeholder="Pendiente de asignar por TI">
                         </div>

                          {{-- Mensaje cuando el empleado no tiene correo --}}
                          @if(!$empleado->email)
                             <small class="text-danger fw-bold" style="font-size: 0.7rem;">
                                 <i class="fa-solid fa-circle-info me-1"></i>
                                 SIN CORREO INSTITUCIONAL
                              </small>
                           @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">CONTACTO</label>

                            {{-- Número de contacto --}}
                            <input type="text"
                                   name="contacto"
                                   value="{{ old('contacto', $empleado->contacto) }}"
                                   class="form-control">
                        </div>

                        {{-- ========================================================= --}}
                        {{-- 3. DATOS LABORALES --}}
                        {{-- ========================================================= --}}
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">CARGO</label>

                            {{-- Cargo del empleado --}}
                            <input type="text"
                                   name="cargo"
                                   value="{{ old('cargo', $empleado->cargo) }}"
                                   class="form-control">
                        </div>

                        <div class="col-md-4">
                          <label class="form-label fw-bold small text-uppercase">Departamento</label>

                          {{-- Select de departamentos --}}
                          <select name="departamento" class="form-select select-departamento-edit" required>

                             <option value="" disabled>-- Seleccione --</option>

                                {{-- Listado de departamentos --}}
                                @foreach($departamentos as $dep)

                                  <option value="{{ $dep->id }}" 
                                      data-jefe="{{ $dep->jefeEmpleado ? $dep->jefeEmpleado->nombre . ' ' . $dep->jefeEmpleado->apellido : 'Sin jefe asignado' }}"
                                      {{ $empleado->departamento_id == $dep->id ? 'selected' : '' }}>

                                      {{ $dep->nombre }}

                                 </option>
                               @endforeach
                          </select>
                       </div>

                       <div class="col-md-4">
                         <label class="form-label fw-bold small text-uppercase">Jefe Inmediato</label>

                          {{-- Campo automático del jefe inmediato --}}
                          <input type="text"
                                 name="jefe_inmediato" 
                                 value="{{ $empleado->departamento?->jefeEmpleado ? $empleado->departamento->jefeEmpleado->nombre . ' ' . $empleado->departamento->jefeEmpleado->apellido : 'Sin jefe asignado' }}" 
                                 class="form-control input-jefe-edit"
                                 readonly>
                       </div>

                       {{-- ========================================================= --}}
                       {{-- 4. GESTIÓN DE CONTRATO --}}
                       {{-- ========================================================= --}}
                       <div class="col-12 mt-2">
                          
                          {{-- Título pequeño --}}
                          <label class="text-muted fw-bold"
                                 style="font-size: 0.75rem; display: block; margin-bottom: 4px;">

                             CAMBIAR TIPO DE CONTRATO
                          </label>

                          {{-- Alerta dinámica al cambiar contrato --}}
                          <div class="alert alert-warning d-none" id="alertaCambioContrato{{ $empleado->id }}">
                             <i class="fa-solid fa-triangle-exclamation me-2"></i>

                               <strong>Atención:</strong>
                               Cambiar el tipo de contrato actualizará los días de vacaciones del empleado.
                          </div>

                          {{-- Select de políticas --}}
                         <select name="politica_id"
                                 class="form-select select-politica-edit"
                                 data-contrato-actual="{{ $empleado->tipo_contrato }}"
                                 data-empleado="{{ $empleado->id }}"
                                 required>

                              {{-- Listado de políticas --}}
                              @foreach($politicas as $politica)

                                 <option value="{{ $politica->id }}"
                                     data-contrato="{{ $politica->tipo_contrato }}"
                                     data-dias="{{ $politica->dias_anuales }}"
                                     {{ $empleado->tipo_contrato == $politica->tipo_contrato ? 'selected' : '' }}>

                                      {{ strtoupper($politica->tipo_contrato) }}

                                 </option>

                               @endforeach
                          </select>

                          {{-- Información actual de vacaciones --}}
                          <div class="mt-2">
                             <small class="text-muted">
                                  Días de vacaciones actuales:
                                   <strong>{{ $empleado->dias_vacaciones_anuales }} días</strong>
                              </small>
                          </div>

                      </div>

                        {{-- ========================================================= --}}
                        {{-- 5. FECHAS Y ESTADO --}}
                        {{-- ========================================================= --}}
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success small">FECHA INGRESO</label>

                            {{-- Fecha de ingreso --}}
                            <input type="date"
                                   name="fecha_ingreso"
                                   value="{{ old('fecha_ingreso', $empleado->fecha_ingreso) }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger small">FECHA BAJA</label>

                            {{-- Fecha de baja --}}
                            <input type="date"
                                   name="fecha_baja"
                                   value="{{ old('fecha_baja', $empleado->fecha_baja) }}"
                                   class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small">ESTADO</label>

                            {{-- Estado laboral --}}
                            <select name="estado" class="form-select" required>
                                <option value="activo" {{ $empleado->estado == 'activo' ? 'selected' : '' }}>
                                    🟢 ACTIVO
                                </option>

                                <option value="inactivo" {{ $empleado->estado == 'inactivo' ? 'selected' : '' }}>
                                    🔴 INACTIVO
                                </option>
                            </select>
                        </div>

                        {{-- ========================================================= --}}
                        {{-- 6. DOCUMENTOS --}}
                        {{-- ========================================================= --}}
                        <div class="col-12 mt-3">

                            {{-- Línea divisora --}}
                            <hr class="text-muted">

                            <label class="form-label fw-bold small mb-2 text-uppercase">
                                Expediente Digital
                            </label>
                            
                            {{-- Verifica si existen documentos --}}
                            @if($empleado->documentos && $empleado->documentos->count() > 0)

                                @php 
                                    // Obtiene el primer documento
                                    $doc = $empleado->documentos->first();

                                    // Limpia la ruta del archivo
                                    $rutaLimpia = str_replace(['public/', 'storage/'], '', $doc->ruta_archivo);
                                @endphp

                                <div class="mb-2">

                                    {{-- Enlace para visualizar el documento --}}
                                    <a href="{{ asset('storage/' . $rutaLimpia) }}"
                                       target="_blank"
                                       class="text-danger small fw-bold text-decoration-none">

                                        <i class="fa-solid fa-file-pdf me-1"></i>
                                        VER ARCHIVO CARGADO

                                    </a>
                                </div>
                            @endif

                            {{-- Campo para subir nuevo archivo --}}
                            <div class="input-group">
                                <label class="input-group-text bg-dark text-white">
                                    <i class="fa-solid fa-upload"></i>
                                </label>

                                <input type="file"
                                       name="documento[]"
                                       class="form-control"
                                       accept=".pdf, .doc, .docx, .xls, .xlsx, .jpg, .png" multiple>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Footer del modal --}}
                <div class="modal-footer bg-light border-top">

                    {{-- Botón cancelar --}}
                    <button type="button"
                            class="btn btn-secondary btn-lg fw-bold"
                            data-bs-dismiss="modal">

                        Cancelar
                    </button>

                    {{-- Botón actualizar --}}
                    <button type="submit"
                            class="btn text-white rounded-pill px-4"
                            style="background-color: #054084;">

                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SCRIPT: ACTUALIZAR JEFE INMEDIATO AUTOMÁTICAMENTE --}}
{{-- ========================================================= --}}
<script>
    document.addEventListener('change', function(event) {

        // Detecta cambio en el select de departamento
        if (event.target && event.target.classList.contains('select-departamento-edit')) {

            const select = event.target;
            
            // Obtiene el jefe desde el atributo data-jefe
            const selectedOption = select.options[select.selectedIndex];
            const jefe = selectedOption.getAttribute('data-jefe') || 'Sin jefe asignado';
            
            // Busca el input del jefe dentro del modal
            const modalBody = select.closest('.row');
            const inputJefe = modalBody.querySelector('.input-jefe-edit');
            
            // Actualiza el valor del input
            if (inputJefe) {
                inputJefe.value = jefe;
            }
        }
    });
</script>

{{-- ========================================================= --}}
{{-- SCRIPT: ALERTA AL CAMBIAR TIPO DE CONTRATO --}}
{{-- ========================================================= --}}
<script>
document.addEventListener('change', function (event) {

    // Solo se ejecuta para selects de política
    if (!event.target.classList.contains('select-politica-edit')) return;

    const select = event.target;

    // Contrato actual del empleado
    const contratoActual = select.dataset.contratoActual;

    // ID del empleado
    const empleadoId = select.dataset.empleado;

    // Nueva opción seleccionada
    const selectedOption = select.options[select.selectedIndex];

    // Nuevo tipo de contrato
    const contratoNuevo = selectedOption.dataset.contrato;

    // Alerta visual
    const alerta = document.getElementById('alertaCambioContrato' + empleadoId);

    // Si cambia el contrato, muestra alerta
    if (contratoNuevo !== contratoActual) {
        alerta.classList.remove('d-none');

    } else {

        // Si vuelve al contrato original, oculta alerta
        alerta.classList.add('d-none');
    }
});
</script>