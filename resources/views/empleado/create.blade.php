<!-- Offcanvas lateral para registrar un nuevo empleado -->
<div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="offcanvasNuevoEmpleado" style="width: 500px;">
    
    {{-- Encabezado del offcanvas --}}
    <div class="offcanvas-header bg-primary text-white py-4">
        <h5 class="offcanvas-title fw-bold">
            <i class="fa-solid fa-user-plus me-2"></i>Registrar Empleado
        </h5>

        {{-- Botón para cerrar el panel lateral --}}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body p-4">

        {{-- Formulario para registrar empleado --}}
        <form action="{{ route('empleado.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">

                {{-- ========================================================= --}}
                {{-- 1. IDENTIFICACIÓN Y DATOS PERSONALES --}}
                {{-- ========================================================= --}}

                <div class="col-md-12">
                    <label class="form-label fw-bold small text-primary">
                        CÓDIGO DE EMPLEADO:
                    </label>

                    <div class="input-group">

                        {{-- Icono decorativo --}}
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-id-badge text-muted"></i>
                        </span>

                        {{-- Campo código de empleado --}}
                        {{-- old() mantiene el valor si hay error de validación --}}
                        <input type="text" 
                            name="codigo_empleado"
                            class="form-control shadow-sm border-primary-subtle @error('codigo_empleado') is-invalid @enderror"
                            value="{{ old('codigo_empleado') }}"
                            placeholder="Ej. EMP-2026-001"
                            required>
                    </div>
                </div>

                {{-- Campo DNI / identidad --}}
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

                {{-- Campo correo institucional --}}
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

                    {{-- Mensaje de error si falla validación --}}
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Campo nombres --}}
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

                {{-- Campo apellidos --}}
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

                {{-- Campo contacto --}}
                <div class="col-md-6">
                    <label class="form-label fw-bold small">
                        CONTACTO:
                    </label>

                    <div class="input-group">

                        {{-- Icono teléfono --}}
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-phone text-muted small"></i>
                        </span>

                        <input type="text"
                            name="contacto"
                            class="form-control shadow-sm"
                            value="{{ old('contacto') }}"
                            placeholder="Ej. 9988-7766">
                    </div>
                </div>

                {{-- Fecha de nacimiento --}}
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
                {{-- 2. DATOS LABORALES --}}
                {{-- ========================================================= --}}

                <div class="col-12 mt-4">

                    {{-- Separador visual --}}
                    <hr class="text-muted">

                    <h6 class="fw-bold text-secondary mb-3" style="font-size: 0.85rem;">
                        INFORMACIÓN LABORAL
                    </h6>
                </div>

                {{-- Campo cargo --}}
                <div class="col-md-12">
                    <label class="form-label fw-bold small text-nowrap">
                        CARGO / PUESTO DE TRABAJO:
                    </label>

                    <div class="input-group">

                        {{-- Icono cargo --}}
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-briefcase text-muted"></i>
                        </span>

                        <input type="text"
                            name="cargo"
                            class="form-control shadow-sm @error('cargo') is-invalid @enderror"
                            value="{{ old('cargo') }}"
                            placeholder="Ej. Analista de Recursos Humanos"
                            required>
                    </div>
                </div>

                {{-- Selector de departamento --}}
                <div class="col-12">

                    <label class="form-label fw-bold small text-uppercase">
                        DEPARTAMENTO:
                    </label>

                    <select name="departamento" id="departamento" class="form-select shadow-sm" required>

                        <option value="" selected disabled>
                            -- Seleccione Departamento --
                        </option>

                        {{-- Recorremos todos los departamentos --}}
                        @foreach($departamentos as $dep)

                            <option value="{{ $dep->id }}"
                                {{ old('departamento') == $dep->id ? 'selected' : '' }}

                                {{-- Guardamos el jefe en data-jefe para JS --}}
                                data-jefe="{{ $dep->jefeEmpleado ? $dep->jefeEmpleado->nombre . ' ' . $dep->jefeEmpleado->apellido : 'Sin jefe asignado' }}">

                                {{ $dep->nombre }}
                            </option>

                        @endforeach
                    </select>
                </div>

                {{-- Campo jefe inmediato --}}
                <div class="col-12">

                    <label class="form-label fw-bold small text-uppercase">
                        JEFE INMEDIATO:
                    </label>

                    <div class="input-group">

                        {{-- Icono jefe --}}
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-user-tie text-muted"></i>
                        </span>

                        {{-- Este campo se llena automáticamente mediante JS --}}
                        <input type="text"
                            name="jefe_inmediato"
                            id="jefe_inmediato"
                            class="form-control shadow-sm"
                            value="{{ old('jefe_inmediato') }}"
                            placeholder="Nombre del supervisor directo"
                            readonly>
                    </div>
                </div>

                {{-- Selector de política / tipo de contrato --}}
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

                        {{-- Solo mostramos políticas del primer año --}}
                        @foreach($politicas as $politica)

                            @if($politica->anio_antiguedad == 1)

                                <option value="{{ $politica->id }}"
                                    {{ old('politica_id') == $politica->id ? 'selected' : '' }}

                                    {{-- Guardamos días de vacaciones en atributo data --}}
                                    data-dias="{{ $politica->dias_anuales }}">

                                    {{ strtoupper($politica->tipo_contrato) }}
                                </option>

                            @endif

                        @endforeach
                    </select>

                    {{-- Aquí se mostrará información dinámica vía JS --}}
                    <small class="text-muted" id="infoDiasVacaciones"></small>
                </div>

                {{-- Fecha de ingreso --}}
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

                {{-- Adjuntar documentos --}}
                <div class="col-12">

                    <label class="form-label fw-bold small text-uppercase">
                        ADJUNTAR CONTRATO:
                    </label>

                    {{-- Se permite subir múltiples archivos --}}
                    <input type="file"
                        name="documentos[]"
                        class="form-control shadow-sm"
                        multiple>

                    {{-- Tipo oculto del documento --}}
                    <input type="hidden"
                        name="tipos_documento[]"
                        value="Contrato Inicial">
                </div>

                {{-- ========================================================= --}}
                {{-- BOTONES DE ACCIÓN --}}
                {{-- ========================================================= --}}

                <div class="col-12 d-flex justify-content-end gap-2 mt-4">

                    {{-- Botón cancelar --}}
                    <button type="button"
                        class="btn btn-secondary px-4"
                        data-bs-dismiss="offcanvas">

                        Cancelar
                    </button>

                    {{-- Botón guardar --}}
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

{{-- ========================================================= --}}
{{-- SCRIPT: Mostrar jefe automático según departamento --}}
{{-- ========================================================= --}}
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Selector de departamento
    const selectDepto = document.getElementById('departamento');

    // Input donde se mostrará el jefe
    const inputJefe = document.getElementById('jefe_inmediato');

    // Evento al cambiar departamento
    selectDepto.addEventListener('change', function() {

        // Opción seleccionada
        const selectedOption = this.options[this.selectedIndex];

        // Obtener jefe desde atributo data-jefe
        const jefe = selectedOption.getAttribute('data-jefe');

        // Mostrar nombre del jefe
        inputJefe.value = jefe;
    });
});
</script>

{{-- ========================================================= --}}
{{-- SCRIPT: Mostrar días de vacaciones según contrato --}}
{{-- ========================================================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Selector de política
    const selectPolitica = document.querySelector('select[name="politica_id"]');

    // Contenedor del mensaje informativo
    const info = document.getElementById('infoDiasVacaciones');

    // Validamos existencia
    if (!selectPolitica) return;

    // Evento cambio de política
    selectPolitica.addEventListener('change', function () {

        // Obtener días desde atributo data-dias
        const dias = this.options[this.selectedIndex].getAttribute('data-dias');

        // Mostrar mensaje dinámico
        if (dias) {
            info.textContent = `Este contrato asigna ${dias} días de vacaciones anuales.`;
        }
    });
});
</script>

{{-- ========================================================= --}}
{{-- SI EXISTEN ERRORES DE VALIDACIÓN --}}
{{-- Se vuelve a abrir automáticamente el offcanvas --}}
{{-- ========================================================= --}}
@if($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Obtener el offcanvas
    var myOffcanvas = document.getElementById('offcanvasNuevoEmpleado');

    // Crear instancia Bootstrap
    var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);

    // Mostrar automáticamente
    bsOffcanvas.show();
});
</script>
@endif

