<!-- Offcanvas lateral para registrar un nuevo empleado -->
<div class="offcanvas offcanvas-end shadow-lg"
     tabindex="-1"
     id="offcanvasNuevoEmpleado"
     style="width: 500px;">

    <!-- Encabezado del offcanvas -->
    <div class="offcanvas-header bg-primary text-white py-4">

        <!-- Título principal -->
        <h5 class="offcanvas-title fw-bold">
            <i class="fa-solid fa-user-plus me-2"></i>
            Registrar Empleado
        </h5>

        <!-- Botón para cerrar el panel -->
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas">
        </button>
    </div>

    <!-- Contenido del offcanvas -->
    <div class="offcanvas-body p-4">

        <!-- Formulario principal -->
        <form action="{{ route('empleado.store') }}"
              method="POST"
              enctype="multipart/form-data">

            <!-- Token CSRF de seguridad -->
            @csrf

            <div class="row g-3">

                {{-- ========================================================= --}}
                {{-- 1. IDENTIFICACIÓN Y DATOS PERSONALES --}}
                {{-- ========================================================= --}}

                <!-- Campo código de empleado -->
                <div class="col-md-12">

                    <label class="form-label fw-bold small text-primary">
                        CÓDIGO DE EMPLEADO:
                    </label>

                    <div class="input-group">

                        <!-- Icono -->
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-id-badge text-muted"></i>
                        </span>

                        <!-- Input código -->
                        <input type="text"
                               name="codigo_empleado"
                               class="form-control shadow-sm border-primary-subtle @error('codigo_empleado') is-invalid @enderror"
                               value="{{ old('codigo_empleado') }}"
                               placeholder="Ej. EMP-2026-001"
                               required>
                    </div>
                </div>


                <!-- Campo DNI -->
                <div class="col-md-6">

                    <label class="form-label fw-bold small">
                        DNI / IDENTIDAD:
                    </label>

                    <input type="text"
                           name="dni"
                           class="form-control shadow-sm @error('dni') is-invalid @enderror"
                           value="{{ old('dni') }}"
                           placeholder="0000-0000-00000"
                           required>
                </div>


                <!-- Campo correo institucional -->
                <div class="col-md-6">

                    <label class="form-label fw-bold small text-nowrap">
                        CORREO INSTITUCIONAL:
                    </label>

                    <input type="email"
                           name="email"
                           class="form-control shadow-sm @error('email') is-invalid @enderror"
                           placeholder="usuario@dominio.com"
                           value="{{ old('email') }}"
                           required>

                    <!-- Mensaje de error de validación -->
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                <!-- Campo nombres -->
                <div class="col-md-6">

                    <label class="form-label fw-bold small">
                        NOMBRES:
                    </label>

                    <input type="text"
                           name="nombre"
                           class="form-control shadow-sm @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre') }}"
                           required>
                </div>


                <!-- Campo apellidos -->
                <div class="col-md-6">

                    <label class="form-label fw-bold small">
                        APELLIDOS:
                    </label>

                    <input type="text"
                           name="apellido"
                           class="form-control shadow-sm @error('apellido') is-invalid @enderror"
                           value="{{ old('apellido') }}"
                           required>
                </div>


                <!-- Campo contacto -->
                <div class="col-md-6">

                    <label class="form-label fw-bold small">
                        CONTACTO:
                    </label>

                    <div class="input-group">

                        <!-- Icono teléfono -->
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-phone text-muted small"></i>
                        </span>

                        <!-- Input contacto -->
                        <input type="text"
                               name="contacto"
                               class="form-control shadow-sm"
                               value="{{ old('contacto') }}"
                               placeholder="Ej. 9988-7766">
                    </div>
                </div>


                <!-- Fecha de nacimiento -->
                <div class="col-md-6">

                    <label class="form-label fw-bold small">
                        FECHA NACIMIENTO:
                    </label>

                    <input type="date"
                           name="fecha_nacimiento"
                           class="form-control shadow-sm"
                           value="{{ old('fecha_nacimiento') }}">
                </div>


                {{-- ========================================================= --}}
                {{-- 2. INFORMACIÓN LABORAL --}}
                {{-- ========================================================= --}}

                <!-- Separador visual -->
                <div class="col-12 mt-4">

                    <hr class="text-muted">

                    <h6 class="fw-bold text-secondary mb-3"
                        style="font-size: 0.85rem;">

                        INFORMACIÓN LABORAL
                    </h6>
                </div>


                <!-- Campo cargo -->
                <div class="col-md-12">

                    <label class="form-label fw-bold small text-nowrap">
                        CARGO / PUESTO DE TRABAJO:
                    </label>

                    <div class="input-group">

                        <!-- Icono -->
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-briefcase text-muted"></i>
                        </span>

                        <!-- Input cargo -->
                        <input type="text"
                               name="cargo"
                               class="form-control shadow-sm @error('cargo') is-invalid @enderror"
                               value="{{ old('cargo') }}"
                               placeholder="Ej. Analista de Recursos Humanos"
                               required>
                    </div>
                </div>


                <!-- Selector de departamento -->
                <div class="col-12">

                    <label class="form-label fw-bold small text-uppercase">
                        DEPARTAMENTO:
                    </label>

                    <select name="departamento"
                            id="departamento"
                            class="form-select shadow-sm"
                            required>

                        <!-- Opción por defecto -->
                        <option value="" selected disabled>
                            -- Seleccione Departamento --
                        </option>

                        <!-- Listado dinámico de departamentos -->
                        @foreach($departamentos as $dep)

                            <option value="{{ $dep->id }}"
                                {{ old('departamento') == $dep->id ? 'selected' : '' }}

                                <!-- Guardamos el nombre del jefe en data-jefe -->
                                data-jefe="{{ $dep->jefeEmpleado ? $dep->jefeEmpleado->nombre . ' ' . $dep->jefeEmpleado->apellido : 'Sin jefe asignado' }}">

                                {{ $dep->nombre }}
                            </option>

                        @endforeach
                    </select>
                </div>


                <!-- Campo jefe inmediato -->
                <div class="col-12">

                    <label class="form-label fw-bold small text-uppercase">
                        JEFE INMEDIATO:
                    </label>

                    <div class="input-group">

                        <!-- Icono -->
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-user-tie text-muted"></i>
                        </span>

                        <!-- Input solo lectura -->
                        <input type="text"
                               name="jefe_inmediato"
                               id="jefe_inmediato"
                               class="form-control shadow-sm"
                               value="{{ old('jefe_inmediato') }}"
                               placeholder="Nombre del supervisor directo"
                               readonly>
                    </div>
                </div>


                <!-- Selector tipo de contrato -->
                <div class="col-12">

                    <label class="form-label fw-bold small text-primary">
                        TIPO DE CONTRATO (POLÍTICAS):
                    </label>

                    <select name="politica_id"
                            class="form-control shadow-sm @error('politica_id') is-invalid @enderror"
                            required>

                        <option value="" selected disabled>
                            -- Seleccione Contrato --
                        </option>

                        <!-- Recorremos políticas -->
                        @foreach($politicas as $politica)

                            <!-- Solo contratos del primer año -->
                            @if($politica->anio_antiguedad == 1)

                                <option value="{{ $politica->id }}"
                                    {{ old('politica_id') == $politica->id ? 'selected' : '' }}

                                    <!-- Guardamos días en atributo personalizado -->
                                    data-dias="{{ $politica->dias_anuales }}">

                                    {{ strtoupper($politica->tipo_contrato) }}
                                </option>

                            @endif

                        @endforeach
                    </select>

                    <!-- Texto dinámico -->
                    <small class="text-muted" id="infoDiasVacaciones"></small>
                </div>


                <!-- Fecha de ingreso -->
                <div class="col-md-12">

                    <label class="form-label fw-bold small text-success">
                        FECHA INGRESO:
                    </label>

                    <input type="date"
                           name="fecha_ingreso"
                           class="form-control border-success shadow-sm"
                           value="{{ old('fecha_ingreso') }}"
                           required>
                </div>


                <!-- Subida de documentos -->
                <div class="col-12">

                    <label class="form-label fw-bold small text-uppercase">
                        ADJUNTAR CONTRATO:
                    </label>

                    <!-- Input múltiple de archivos -->
                    <input type="file"
                           name="documentos[]"
                           class="form-control shadow-sm"
                           multiple>

                    <!-- Tipo de documento oculto -->
                    <input type="hidden"
                           name="tipos_documento[]"
                           value="Contrato Inicial">
                </div>


                {{-- ========================================================= --}}
                {{-- BOTONES DE ACCIÓN --}}
                {{-- ========================================================= --}}

                <div class="col-12 d-flex justify-content-end gap-2 mt-4">

                    <!-- Botón cancelar -->
                    <button type="button"
                            class="btn btn-secondary px-4"
                            data-bs-dismiss="offcanvas">

                        Cancelar
                    </button>

                    <!-- Botón guardar -->
                    <button type="submit"
                            class="btn btn-primary px-4 fw-bold shadow">

                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Guardar Empleado
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>


<!-- ========================================================= -->
<!-- SCRIPT PARA ACTUALIZAR EL JEFE INMEDIATO -->
<!-- ========================================================= -->

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Selector departamento
    const selectDepto = document.getElementById('departamento');

    // Input jefe inmediato
    const inputJefe = document.getElementById('jefe_inmediato');

    // Evento al cambiar departamento
    selectDepto.addEventListener('change', function() {

        // Opción seleccionada
        const selectedOption = this.options[this.selectedIndex];

        // Obtenemos atributo data-jefe
        const jefe = selectedOption.getAttribute('data-jefe');

        // Colocamos nombre del jefe en el input
        inputJefe.value = jefe;
    });
});
</script>


<!-- ========================================================= -->
<!-- SCRIPT PARA MOSTRAR DÍAS DE VACACIONES -->
<!-- ========================================================= -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Selector de política
    const selectPolitica = document.querySelector('select[name="politica_id"]');

    // Contenedor informativo
    const info = document.getElementById('infoDiasVacaciones');

    // Validación de seguridad
    if (!selectPolitica) return;

    // Evento cambio de política
    selectPolitica.addEventListener('change', function () {

        // Obtiene días desde atributo data-dias
        const dias = this.options[this.selectedIndex]
                          .getAttribute('data-dias');

        // Si existen días
        if (dias) {

            // Muestra mensaje dinámico
            info.textContent =
                `Este contrato asigna ${dias} días de vacaciones anuales.`;
        }
    });
});
</script>


<!-- ========================================================= -->
<!-- REABRIR OFFCANVAS SI HAY ERRORES -->
<!-- ========================================================= -->

@if($errors->any())

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Obtiene el offcanvas
    var myOffcanvas = document.getElementById('offcanvasNuevoEmpleado');

    // Inicializa componente Bootstrap
    var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);

    // Muestra nuevamente el formulario
    bsOffcanvas.show();
});
</script>

@endif

