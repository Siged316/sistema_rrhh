@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('informes.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Centro de Informes
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-11"> {{-- Ampliado un poco para acomodar el nuevo campo --}}
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3" style="background-color: #003366 !important;">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user me-2"></i> Informe de Desempeño Individual</h5>
                </div>
                <div class="card-body p-5">
                    <form id="formReporte">
                        @csrf
                        <div class="row g-4">
                            {{-- NUEVO: FILTRAR POR DEPARTAMENTO --}}
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark">1. Filtrar por Depto.</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" id="depto_filtro" onchange="filtrarEmpleados()">
                                    <option value="todos">Todos los Departamentos</option>
                                    @foreach($departamentos as $d)
                                        <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 2. SELECCIONAR EMPLEADO --}}
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark">2. Colaborador</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="empleado_id" id="empleado_id" required>
                                    <option value="" selected disabled>Seleccione...</option>
                                    @foreach($empleados as $e)
                                        {{-- Agregamos data-depto para poder filtrar con JS --}}
                                        <option value="{{ $e->id }}" data-depto="{{ $e->departamento_id }}">
                                            {{ $e->nombre }} {{ $e->apellido }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 3. PERÍODO --}}
                            <div class="col-md-2">
                                <label class="form-label fw-bold text-dark">3. Período</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="periodo" id="periodo" onchange="actualizarInterfaz()">
                                    <option value="" selected disabled>Elija...</option>
                                    <option value="mensual">Mensual</option>
                                    <option value="anual">Anual</option>
                                </select>
                            </div>

                            {{-- 4. AÑO --}}
                            <div class="col-md-2 d-none" id="div_anio">
                                <label class="form-label fw-bold text-dark">4. Año</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="anio" id="anio_valor">
                                   <option value="" selected disabled>Elija...</option>
                                    @foreach($anios as $a)
                                        <option value="{{ $a }}">{{ $a }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 5. MES --}}
                            <div class="col-md-2 d-none" id="div_mes">
                                <label class="form-label fw-bold text-dark">5. Mes</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="mes" id="mes_valor">
                                    <option value="" selected disabled>Elija...</option>
                                    <option value="01">Enero</option><option value="02">Febrero</option>
                                    <option value="03">Marzo</option><option value="04">Abril</option>
                                    <option value="05">Mayo</option><option value="06">Junio</option>
                                    <option value="07">Julio</option><option value="08">Agosto</option>
                                    <option value="09">Septiembre</option><option value="10">Octubre</option>
                                    <option value="11">Noviembre</option><option value="12">Diciembre</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-center my-5">
                            <span class="px-3 bg-white text-muted small fw-bold text-uppercase" style="position: relative; z-index: 1;">Elija el Formato de Salida</span>
                            <hr style="margin-top: -10px;">
                        </div>

                         {{-- BOTONES DE DESCARGA --}}
                        <div class="row text-center mt-5">
                            <div class="col-md-6 mb-3">
                                <button type="button" onclick="descargar('pdf')" class="btn btn-outline-primary w-100 p-4 border-2  btn-reporte">
                                    <i class="fas fa-file-pdf fa-4x mb-3"></i><br>
                                    <b>Descargar PDF</b>
                                    <p class="small mb-0">Informe formal con promedios.</p>
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button type="button" onclick="descargar('excel')" class="btn btn-outline-success w-100 p-4 border-2 btn-reportes">
                                    <i class="fas fa-file-excel fa-4x mb-3"></i><br>
                                    <b>Descargar Excel</b>
                                    <p class="small mb-0">Consolidado de promedios para análisis de datos.</p>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-reporte {
        cursor: pointer;
        transition: all 0.3s ease;
        border-style: dashed !important;
        /* AGREGAMOS EL COLOR DEL BORDE AQUÍ */
        border-color: #0b3d68 !important; 
        color: #0b3d68;
    }
    
    .btn-reportes {
        cursor: pointer;
        transition: all 0.3s ease;
        border-style: dashed !important;
        /* AGREGAMOS EL COLOR DEL BORDE AQUÍ */
        border-color: #075537 !important;
        color: #075537;
    }

    .btn-reporte:hover {
        transform: translateY(-5px);
        border-style: solid !important;
        background-color: #0b3d68 !important;
        color: white !important;
    }

    .btn-reportes:hover {
        transform: translateY(-5px);
        border-style: solid !important;
        background-color: #075537 !important;
        color: white !important;
    }

    .card-header { background-color: #003366 !important; }
</style>

<script>
    // FUNCIÓN PARA FILTRAR COLABORADORES POR DEPTO
    function filtrarEmpleados() {
        const deptoId = document.getElementById('depto_filtro').value;
        const selectEmp = document.getElementById('empleado_id');
        const opciones = selectEmp.querySelectorAll('option');

        // Resetear selección
        selectEmp.value = "";

        opciones.forEach(opcion => {
            if (opcion.value === "") return; // Ignorar el "Seleccione..."

            const deptoOpcion = opcion.getAttribute('data-depto');

            if (deptoId === "todos" || deptoOpcion === deptoId) {
                opcion.style.display = 'block';
            } else {
                opcion.style.display = 'none';
            }
        });
    }

    function actualizarInterfaz() {
        const periodo = document.getElementById('periodo').value;
        document.getElementById('div_anio').classList.remove('d-none');
        if (periodo === 'mensual') {
            document.getElementById('div_mes').classList.remove('d-none');
        } else {
            document.getElementById('div_mes').classList.add('d-none');
        }
    }

    function descargar(tipo) {
        const empleado = document.getElementById('empleado_id').value;
        const periodo = document.getElementById('periodo').value;
        const anio = document.getElementById('anio_valor').value;
        const mes = document.getElementById('mes_valor').value;

        if (!empleado || !periodo || !anio) {
            Swal.fire('Atención', 'Seleccione un colaborador, período y año.', 'warning');
            return;
        }

        Swal.fire({ title: 'Verificando...', didOpen: () => { Swal.showLoading(); } });

        fetch(`{{ route('informes.validar') }}?empleado_id=${empleado}&anio=${anio}&periodo=${periodo}&mes=${mes}`)
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.count > 0) {
                    let ruta = (tipo === 'pdf') 
                        ? "{{ route('informes.individual.pdf') }}" 
                        : "{{ route('informes.individual.excel') }}";
                    
                    window.location.href = `${ruta}?empleado_id=${empleado}&anio=${anio}&periodo=${periodo}&mes=${mes}`;
                } else {
                    Swal.fire('Sin registros', 'No se encontraron evaluaciones para este empleado.', 'info');
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire('Error', 'No se pudo validar la información.', 'error');
            });
    }
</script>
@endsection