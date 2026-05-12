@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Botón para regresar al centro de informes del IHCI --}}
    <div class="mb-3">
        <a href="{{ route('informes.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Centro de Informes
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0">
                {{-- Encabezado con color institucional --}}
                <div class="card-header py-3" style="background-color: #003366;">
                    <h5 class="mb-0 fw-bold text-white"><i class="fas fa-user me-2"></i> Informe Individual de Evaluación del Desempeño</h5>
                </div>
                <div class="card-body p-5">
                    <form id="formReporteIndividual">
                        @csrf
                        <div class="row g-4">
                            {{-- 1. SELECCIONAR EMPLEADO (Paso principal) --}}
  <div class="col-md-5">
    <label class="form-label fw-bold text-dark">1. Seleccione el Colaborador</label>
    <select class="form-select form-select-lg select2-busqueda" name="empleado_id" id="empleado_id" required>
    <option value="">Seleccione un colaborador...</option> 
    @foreach($empleados as $e)
        <option value="{{ $e->id }}">{{ $e->nombre }} {{ $e->apellido }}</option>
    @endforeach
</select>
</div>


                            {{-- 2. TIPO DE PERÍODO --}}
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark">2. Período</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="periodo" id="periodo" onchange="actualizarInterfaz()" required>
                                    <option value="" selected disabled>Elija período...</option>
                                    <option value="mensual">Mensual</option>
                                    <option value="anual">Anual Acumulado</option>
                                </select>
                            </div>

                            {{-- 3. SELECCIONAR AÑO (Oculto al inicio, dinámico) --}}
                            <div class="col-md-2 d-none" id="div_anio">
                                <label class="form-label fw-bold text-dark">3. Año</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="anio" id="anio_valor">
                                    <option value="" selected disabled>Elija...</option>
                                    @foreach($anios as $a)
                                        <option value="{{ $a }}">{{ $a }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 4. SELECCIONAR MES (Solo visible si elige 'mensual') --}}
                            <div class="col-md-2 d-none" id="div_mes">
                                <label class="form-label fw-bold text-dark">4. Mes</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="mes" id="mes_valor">
                                    <option value="" selected disabled>Elija...</option>
                                    @php
                                        $meses = [
                                            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', 
                                            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto', 
                                            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                                        ];
                                    @endphp
                                    @foreach($meses as $num => $nombre)
                                        <option value="{{ $num }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Línea divisoria decorativa --}}
                        <div class="text-center my-5">
                            <span class="px-3 bg-white text-muted small fw-bold text-uppercase" style="position: relative; z-index: 1;">Elija el Formato de Descarga</span>
                            <hr style="margin-top: -10px;">
                        </div>

                        {{-- BOTONES DE DESCARGA (Mismo estilo dashed que te gusta) --}}
                        <div class="row text-center justify-content-center">
                            {{-- Descarga PDF --}}
                            <div class="col-md-5 mb-3">
                                <div class="card btn-outline-danger border-2 p-4 btn-reporte" onclick="descargarIndividual('pdf')" style="cursor: pointer;">
                                    <div class="card-body">
                                        <i class="fas fa-file-pdf fa-4x mb-3"></i>
                                        <h4 class="fw-bold mb-1">Descargar PDF</h4>
                                        <p class="small mb-0 text-muted">Informe formal con Auto-evaluación vs Jefe, promedio y firmas.</p>
                                    </div>
                                </div>
                            </div>
                            {{-- Descarga Excel --}}
                            <div class="col-md-5 mb-3">
                                <div class="card btn-outline-success border-2 p-4 btn-reporte" onclick="descargarIndividual('excel')" style="cursor: pointer;">
                                    <div class="card-body">
                                        <i class="fas fa-file-excel fa-4x mb-3 text-success"></i>
                                        <h4 class="fw-bold mb-1 text-dark">Descargar Excel</h4>
                                        <p class="small mb-0 text-muted">Consolidado de notas detalladas por competencia para análisis.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Estilos personalizados heredados de tu diseño --}}
<style>
    .btn-reporte {
        transition: all 0.3s ease;
        border-style: dashed !important; /* Estilo dashed heredado */
        border-color: #6c757d;
    }
    .btn-reporte:hover {
        transform: translateY(-5px);
        border-style: solid !important;
    }
    /* Efecto hover rojo para PDF */
    .btn-outline-danger:hover {
        background-color: #f8d7da;
        border-color: #dc3545 !important;
        color: #721c24 !important;
    }
    /* Efecto hover verde para Excel */
    .btn-outline-success:hover {
        background-color: #d1e7dd;
        border-color: #198754 !important;
        color: #0f5132 !important;
    }
</style>

<!-- buscador-->
<script>
    window.onload = function() {
        if (window.jQuery) {
            $('.select2-busqueda').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    };
</script>
{{-- Scripts de lógica heredados y mejorados --}}
@push('scripts') {{-- O @section('scripts') según use tu layout --}}

<script>
    // 1. Definimos la función de inicialización segura
    function iniciarSelect2() {
        if (typeof jQuery !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('.select2-busqueda').select2({
                theme: 'bootstrap-5',
                placeholder: 'Escriba el nombre del colaborador...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() { return "No se encontró al colaborador"; },
                    searching: function() { return "Buscando..."; }
                }
            });
            console.log("Select2 inicializado correctamente");
        } else {
            // Si no detecta la librería, reintenta cada 100ms
            setTimeout(iniciarSelect2, 100);
        }
    }

    // 2. Ejecutar cuando el DOM esté listo
   alert("¿El script carga?"); // <--- AGREGA ESTO TEMPORALMENTE
   $(document).ready(function() {
        // Inicialización estándar
        $('.select2-busqueda').select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione un colaborador...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() { return "No se encontró al colaborador"; }
            }
        });

        // SOLUCIÓN DEFINITIVA PARA EL CUADRO VACÍO
        $('.select2-busqueda').on('select2:open', function() {
            // Esperamos un milisegundo para que el cuadro exista en el DOM
            setTimeout(function() {
                const searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) {
                    searchField.setAttribute('placeholder', 'Escriba el nombre del empleado...');
                    // Forzamos el foco por si se pierde
                    searchField.focus();
                }
            }, 1); 
        });
    });

    // 3. Función para mostrar/ocultar campos (Año/Mes)
    function actualizarInterfaz() {
        const periodo = document.getElementById('periodo').value;
        const divAnio = document.getElementById('div_anio');
        const divMes = document.getElementById('div_mes');
        const mesInput = document.getElementById('mes_valor');

        if (!divAnio || !divMes) return;

        mesInput.required = false;

        if (periodo === 'mensual') {
            divAnio.classList.remove('d-none');
            divMes.classList.remove('d-none');
            mesInput.required = true;
        } else if (periodo === 'anual') {
            divAnio.classList.remove('d-none');
            divMes.classList.add('d-none');
            mesInput.value = '';
        } else {
            divAnio.classList.add('d-none');
            divMes.classList.add('d-none');
        }
    }

    // 4. Función de descarga
    function descargarIndividual(tipo) {
        // Usamos jQuery para obtener el valor del Select2 correctamente
        const empleadoId = $('#empleado_id').val();
        const periodo = document.getElementById('periodo').value;
        const anio = document.getElementById('anio_valor').value;
        const mes = document.getElementById('mes_valor').value;

        if (!empleadoId || !periodo || !anio) {
            Swal.fire('Atención', 'Debe seleccionar al colaborador, el período y el año.', 'warning');
            return;
        }

        let rutaBase = (tipo === 'pdf') 
            ? "{{ route('informes.individual.pdf') }}" 
            : "{{ route('informes.individual.excel') }}";

        const url = `${rutaBase}?empleado_id=${empleadoId}&periodo=${periodo}&anio=${anio}&mes=${mes}`;
        window.location.href = url;
    }
</script>
@endpush
@endsection