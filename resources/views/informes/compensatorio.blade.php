@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('informes.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Centro de Informes
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card shadow-lg border-0">
                {{-- Encabezado con el color institucional IHCI --}}
                <div class="card-header py-3" style="background-color: #003366 !important; color: white;">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-clock me-2"></i> Reporte de Tiempo Compensatorio</h5>
                </div>
                <div class="card-body p-5">
                    <form id="formCompensatorio">
                        @csrf
                        <div class="row g-4">
                            {{-- 1. FILTRAR POR DEPARTAMENTO --}}
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark">1. Filtrar por Depto.</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" id="depto_filtro" onchange="filtrarEmpleados()">
                                    <option value="todos">Todos los Departamentos</option>
                                    @foreach($departamentos as $d)
                                        <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 2. SELECCIONAR COLABORADOR --}}
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark">2. Colaborador</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="empleado_id" id="empleado_id" required>
                                    <option value="" selected disabled>Seleccione...</option>
                                    @foreach($empleados as $e)
                                        <option value="{{ $e->id }}" data-depto="{{ $e->departamento_id }}">
                                            {{ $e->nombre }} {{ $e->apellido }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 3. TIPO DE PERÍODO --}}
                            <div class="col-md-2">
                                <label class="form-label fw-bold text-dark">3. Período</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="periodo" id="periodo" onchange="actualizarInterfaz()">
                                    <option value="anual" selected>Anual</option>
                                    <option value="mensual">Mensual</option>
                                </select>
                            </div>

                            {{-- 4. AÑO FISCAL --}}
                            <div class="col-md-2" id="div_anio">
                                <label class="form-label fw-bold text-dark">4. Año</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="anio" id="anio_valor">
                                    @foreach($anios as $a)
                                        <option value="{{ $a }}" {{ $a == date('Y') ? 'selected' : '' }}>{{ $a }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 5. MES (Oculto por defecto) --}}
                            <div class="col-md-2 d-none" id="div_mes">
                                <label class="form-label fw-bold text-dark">5. Mes</label>
                                <select class="form-select form-select-lg border-2 shadow-sm" name="mes" id="mes_valor">
                                    <option value="" selected disabled>Elija...</option>
                                    @foreach(['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'] as $val => $nombre)
                                        <option value="{{ $val }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="text-center my-5">
                            <span class="px-3 bg-white text-muted small fw-bold text-uppercase" style="position: relative; z-index: 1;">Seleccione Formato de Balance</span>
                            <hr style="margin-top: -10px;">
                        </div>

                        <div class="row text-center mt-5">
                            <div class="col-md-6 mb-3">
                                <button type="button" onclick="generarReporte('pdf')" class="btn btn-outline-primary w-100 p-4 border-2 btn-reporte-comp">
                                    <i class="fas fa-file-pdf fa-4x mb-3"></i><br>
                                    <b>Descargar Balance PDF</b>
                                    <p class="small mb-0">Estado de horas ganadas vs. usadas.</p>
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button type="button" onclick="generarReporte('excel')" class="btn btn-outline-success w-100 p-4 border-2 btn-reporte-excel">
                                    <i class="fas fa-file-excel fa-4x mb-3"></i><br>
                                    <b>Descargar Detalle Excel</b>
                                    <p class="small mb-0">Listado completo de movimientos.</p>
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
    .btn-reporte-comp, .btn-reporte-excel {
        cursor: pointer;
        transition: all 0.3s ease;
        border-style: dashed !important;
    }
    
    .btn-reporte-comp { border-color: #0b3d68 !important; color: #0b3d68; }
    .btn-reporte-excel { border-color: #198754 !important; color: #198754; }

    .btn-reporte-comp:hover { transform: translateY(-5px); border-style: solid !important; background-color: #0b3d68 !important; color: white !important; }
    .btn-reporte-excel:hover { transform: translateY(-5px); border-style: solid !important; background-color: #198754 !important; color: white !important; }
</style>

<script>
    function filtrarEmpleados() {
        const deptoId = document.getElementById('depto_filtro').value;
        const selectEmp = document.getElementById('empleado_id');
        const opciones = selectEmp.querySelectorAll('option');

        selectEmp.value = "";
        opciones.forEach(opcion => {
            if (opcion.value === "") return;
            const deptoOpcion = opcion.getAttribute('data-depto');
            const coincide = (deptoId === "todos" || deptoOpcion === deptoId);
            opcion.style.display = coincide ? 'block' : 'none';
        });
    }

    function actualizarInterfaz() {
        const periodo = document.getElementById('periodo').value;
        const divMes = document.getElementById('div_mes');
        
        if (periodo === 'mensual') {
            divMes.classList.remove('d-none');
        } else {
            divMes.classList.add('d-none');
            document.getElementById('mes_valor').value = "";
        }
    }

   function generarReporte(tipo) {
    const empleado = document.getElementById('empleado_id').value;
    const periodo = document.getElementById('periodo').value;
    const anio = document.getElementById('anio_valor').value;
    const mes = document.getElementById('mes_valor').value;

    // 1. Validaciones iniciales en el cliente
    if (!empleado) {
        Swal.fire('Atención', 'Debe seleccionar un colaborador.', 'warning');
        return;
    }
    if (periodo === 'mensual' && !mes) {
        Swal.fire('Atención', 'Debe seleccionar el mes para el reporte mensual.', 'warning');
        return;
    }

    // 2. Mostrar indicador de carga
    Swal.fire({
        title: 'Verificando datos...',
        text: 'Espere un momento por favor.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    /**
     * 3. Llamada AJAX para validar datos
     * IMPORTANTE: El nombre de la ruta debe ser 'informes.validar.compensatorio'
     * para que coincida con tu archivo web.php
     */
    fetch(`{{ route('validar.compensatorio') }}?empleado_id=${empleado}&anio=${anio}&periodo=${periodo}&mes=${mes}`)
        .then(response => {
            if (!response.ok) throw new Error('Error en la respuesta del servidor');
            return response.json();
        })
        .then(data => {
            Swal.close(); // Cerrar el loading

            if (data.count > 0) {
                // 4. Si hay datos, construir la URL de descarga (PDF o Excel)
                let rutaBase = (tipo === 'pdf') 
                    ? "{{ route('informes.compensatorio.pdf') }}" 
                    : "{{ route('informes.compensatorio.excel') }}";
                
                const params = `empleado_id=${empleado}&anio=${anio}&periodo=${periodo}&mes=${mes}`;
                
                // Abrir en pestaña nueva
                window.open(`${rutaBase}?${params}`, '_blank');
            } else {
                // 5. Mensaje si la consulta viene vacía
                Swal.fire({
                    title: 'Sin registros',
                    text: 'No se encontraron horas extras aprobadas para los criterios seleccionados.',
                    icon: 'info',
                    confirmButtonColor: '#003366'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.close();
            Swal.fire('Error', 'Hubo un problema al conectar con el servidor. Intente de nuevo.', 'error');
        });
}
</script>
@endsection