<div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="offcanvasNuevoEmpleado" style="width: 500px;">
    
    <div class="offcanvas-header bg-primary text-white py-4">
        <h5 class="offcanvas-title fw-bold">
            <i class="fa-solid fa-user-plus me-2"></i>Registrar Empleado
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body p-4">
        <form action="{{ route('empleado.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">

                {{-- ========================================================= --}}
                {{-- 1. IDENTIFICACIÓN Y DATOS PERSONALES --}}
                {{-- ========================================================= --}}

                <div class="col-md-12">
                    <label class="form-label fw-bold small text-primary">CÓDIGO DE EMPLEADO:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-id-badge text-muted"></i>
                        </span>
                        <input type="text" 
                            name="codigo_empleado"
                            class="form-control shadow-sm border-primary-subtle @error('codigo_empleado') is-invalid @enderror"
                            value="{{ old('codigo_empleado') }}"
                            placeholder="Ej. EMP-2026-001"
                            required>
                        @error('codigo_empleado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">DNI / IDENTIDAD:</label>
                    <input type="text"
                        name="dni"
                        class="form-control shadow-sm @error('dni') is-invalid @enderror"
                        value="{{ old('dni') }}"
                        placeholder="0000-0000-00000"
                        required>
                    @error('dni')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-nowrap">CORREO INSTITUCIONAL:</label>
                    <input type="email"
                        name="email"
                        class="form-control shadow-sm @error('email') is-invalid @enderror"
                        placeholder="usuario@dominio.com"
                        value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">NOMBRES:</label>
                    <input type="text"
                        name="nombre"
                        class="form-control shadow-sm @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}"
                        required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">APELLIDOS:</label>
                    <input type="text"
                        name="apellido"
                        class="form-control shadow-sm @error('apellido') is-invalid @enderror"
                        value="{{ old('apellido') }}"
                        required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small">CONTACTO:</label>
                    <div class="input-group">
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

                <div class="col-md-6">
                    <label class="form-label fw-bold small">FECHA NACIMIENTO:</label>
                    <input type="date"
                        name="fecha_nacimiento"
                        class="form-control shadow-sm"
                        value="{{ old('fecha_nacimiento') }}">
                </div>

                {{-- ========================================================= --}}
                {{-- 2. DATOS LABORALES --}}
                {{-- ========================================================= --}}

                <div class="col-12 mt-4">
                    <hr class="text-muted">
                    <h6 class="fw-bold text-secondary mb-3" style="font-size: 0.85rem;">INFORMACIÓN LABORAL</h6>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold small text-nowrap">CARGO / PUESTO DE TRABAJO:</label>
                    <div class="input-group">
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

                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase">DEPARTAMENTO:</label>
                    <select name="departamento" id="departamento" class="form-select shadow-sm" required>
                        <option value="" selected disabled>-- Seleccione Departamento --</option>
                        @foreach($departamentos as $dep)
                            <option value="{{ $dep->id }}"
                                {{ old('departamento') == $dep->id ? 'selected' : '' }}
                                data-jefe="{{ $dep->jefeEmpleado ? $dep->jefeEmpleado->nombre . ' ' . $dep->jefeEmpleado->apellido : 'Sin jefe asignado' }}">
                                {{ $dep->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase">JEFE INMEDIATO:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-user-tie text-muted"></i>
                        </span>
                        <input type="text"
                            name="jefe_inmediato"
                            id="jefe_inmediato"
                            class="form-control shadow-sm"
                            value="{{ old('jefe_inmediato') }}"
                            placeholder="Nombre del supervisor directo"
                            readonly>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold small text-primary">TIPO DE CONTRATO (POLÍTICAS):</label>
                    <select name="politica_id" class="form-control shadow-sm @error('politica_id') is-invalid @enderror" required>
                        <option value="" selected disabled>-- Seleccione Contrato --</option>
                        @foreach($politicas as $politica)
                            @if($politica->anio_antiguedad == 1)
                                <option value="{{ $politica->id }}"
                                    {{ old('politica_id') == $politica->id ? 'selected' : '' }}
                                    data-dias="{{ $politica->dias_anuales }}">
                                    {{ strtoupper($politica->tipo_contrato) }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-1" id="infoDiasVacaciones"></small>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold small text-success">FECHA INGRESO:</label>
                    <input type="date"
                        name="fecha_ingreso"
                        class="form-control border-success shadow-sm"
                        value="{{ old('fecha_ingreso') }}"
                        required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase">ADJUNTAR CONTRATO:</label>
                    <input type="file"
                        name="documento[]"
                        class="form-control shadow-sm @error('documento.*') is-invalid @enderror"
                        accept=".pdf, .doc, .docx, .xls, .xlsx, .jpg, .png"
                        multiple>
                    @error('documento.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    <input type="hidden" name="tipos_documento[]" value="Contrato Inicial">
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="offcanvas">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Empleado
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SCRIPTS DINÁMICOS (LÍNEAS DE EVENTO ÚNICAS) --}}
{{-- ========================================================= --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Manejo dinámico de Jefe Inmediato
    const selectDepto = document.getElementById('departamento');
    const inputJefe = document.getElementById('jefe_inmediato');

    if(selectDepto) {
        selectDepto.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            inputJefe.value = selectedOption.getAttribute('data-jefe') || 'Sin jefe asignado';
        });
    }

    // 2. Manejo dinámico de Vacaciones por Contrato
    const selectPolitica = document.querySelector('select[name="politica_id"]');
    const info = document.getElementById('infoDiasVacaciones');

    if (selectPolitica) {
        selectPolitica.addEventListener('change', function () {
            const dias = this.options[this.selectedIndex].getAttribute('data-dias');
            info.textContent = dias ? `Este contrato asigna ${dias} días de vacaciones anuales.` : '';
        });
    }
});
</script>

{{-- Reapertura del Offcanvas en caso de errores de validación --}}
@if($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    var myOffcanvas = document.getElementById('offcanvasNuevoEmpleado');
    if (myOffcanvas) {
        var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);
        bsOffcanvas.show();
    }
});
</script>
@endif

