@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Botón para regresar --}}
    <div class="mb-3">
        <a href="{{ route('informes.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Centro de Informes
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-building me-2"></i> Informe de Desempeño por Departamento</h5>
                </div>
                <div class="card-body p-5">
                    <form id="formReporte">
                        @csrf
                        <div class="row g-4">
                         {{-- 1. SELECCIONAR DEPARTAMENTO --}}
                         <div class="col-md-4">
                             <label class="form-label fw-bold text-dark">1. Departamento</label>
                              <select class="form-select form-select-lg border-2 shadow-sm" name="departamento_id" id="departamento_id" required>
                                 <option value="" selected disabled>Elija un área...</option>
                                    @foreach($departamentos as $d)
                                     <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                                    @endforeach
                             </select>
                          </div>

                           {{-- 2. TIPO DE PERÍODO (Ahora es el paso 2) --}}
                          <div class="col-md-3">
                             <label class="form-label fw-bold text-dark">2. Período</label>
                              <select class="form-select form-select-lg border-2 shadow-sm" name="periodo" id="periodo" onchange="actualizarInterfaz()">
                                  <option value="" selected disabled>Elija período...</option>
                                  <option value="mensual">Mensual</option>
                                  <option value="anual">Anual</option>
                              </select>
                            </div>

                           {{-- 3. SELECCIONAR AÑO (Oculto al inicio) --}}
                           <div class="col-md-2 d-none" id="div_anio">
                              <label class="form-label fw-bold text-dark">3. Año</label>
                               <select class="form-select form-select-lg border-2 shadow-sm" name="anio" id="anio_valor">
                                   <!-- Este se mostrará primero por defecto -->
                                   <option value="" selected disabled>Elija...</option>
    
                                    @foreach($anios as $a)
                                     <option value="{{ $a }}">{{ $a }}</option>
                                    @endforeach
                               </select>
                            </div>

                            {{-- 4. SELECCIONAR MES (Oculto al inicio) --}}
                            <div class="col-md-3 d-none" id="div_mes">
                              <label class="form-label fw-bold text-dark">4. Mes</label>
                            <select class="form-select form-select-lg border-2 shadow-sm" name="mes" id="mes_valor">
                              <!-- Este aparecerá siempre primero porque es el único con 'selected' -->
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

                        <div class="text-center my-5">
                            <span class="px-3 bg-white text-muted small fw-bold text-uppercase" style="position: relative; z-index: 1;">Elija el Formato de Salida</span>
                            <hr style="margin-top: -10px;">
                        </div>

                        {{-- BOTONES DE DESCARGA --}}
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                               <div class="card btn-outline-danger border-2 p-4 btn-reporte" onclick="descargar('pdf')">
                                  <div class="card-body">
                                     <i class="fas fa-file-pdf fa-4x mb-3"></i>
                                     <h4 class="fw-bold mb-1">Descargar PDF</h4>
                                     <p class="small mb-0">Informe formal con promedios y firmas.</p>
                                   </div>
                              </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card btn-outline-success border-2 p-4 btn-reporte" onclick="descargar('excel')" style="cursor: pointer;">
                                   <div class="card-body">
                                       <i class="fas fa-file-excel fa-4x mb-3 text-success"></i>
                                       <h4 class="fw-bold mb-1">Descargar Excel</h4>
                                       <p class="small mb-0">Consolidado de promedios para análisis de datos.</p>
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

<style>
    .btn-reporte {
        cursor: pointer;
        transition: all 0.3s ease;
        border-style: dashed !important;
        color: #6c757d;
    }
    .btn-reporte:hover {
        transform: translateY(-5px);
        border-style: solid !important;
    }
    .btn-outline-danger:hover {
        background-color: #1665c0;
        color: white !important;
    }
    .btn-outline-success:hover {
        background-color: #198754;
        color: white !important;
    }
    .card-header { background-color: #003366 !important; }
</style>

<script>
    function actualizarInterfaz() {
    const periodo = document.getElementById('periodo').value;
    const divAnio = document.getElementById('div_anio');
    const divMes = document.getElementById('div_mes');

    if (periodo === 'mensual') {
        // Muestra Año y Mes
        divAnio.classList.remove('d-none');
        divMes.classList.remove('d-none');
    } else if (periodo === 'anual') {
        // Muestra Año, oculta Mes
        divAnio.classList.remove('d-none');
        divMes.classList.add('d-none');
    } else {
        // Oculta ambos si no hay selección
        divAnio.classList.add('d-none');
        divMes.classList.add('d-none');
    }
}

    // Función para ocultar el mes si elige "Anual"
    function toggleMes() {
        const periodo = document.getElementById('periodo').value;
        const divMes = document.getElementById('div_mes');
        divMes.style.visibility = (periodo === 'anual') ? 'hidden' : 'visible';
    }

    function actualizarInterfaz() {
        const periodo = document.getElementById('periodo').value;
        const divAnio = document.getElementById('div_anio');
        const divMes = document.getElementById('div_mes');

        if (periodo === 'mensual') {
            divAnio.classList.remove('d-none');
            divMes.classList.remove('d-none');
        } else if (periodo === 'anual') {
            divAnio.classList.remove('d-none');
            divMes.classList.add('d-none');
        } else {
            divAnio.classList.add('d-none');
            divMes.classList.add('d-none');
        }
    }

    // Función que se activará cuando hagamos la lógica
    async function descargar(tipo) {
        // 1. Captura de valores
        const depto = document.getElementById('departamento_id').value;
        const periodo = document.getElementById('periodo').value;
        const anio = document.getElementById('anio_valor').value;
        const mes = document.getElementById('mes_valor').value;

        // 2. Validación de campos vacíos en el cliente
        if (!depto || !periodo || !anio) {
            Swal.fire('Atención', 'Debe seleccionar departamento, período y año.', 'warning');
            return;
        }
        if (periodo === 'mensual' && !mes) {
            Swal.fire('Atención', 'Debe seleccionar un mes para el reporte mensual.', 'warning');
            return;
        }

        // 3. Alerta de carga
        Swal.fire({
            title: 'Verificando datos...',
            text: 'Espere un momento mientras consultamos los registros.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            // 4. Petición AJAX al método validarDatos de tu ReporteController
            const response = await fetch(`{{ route('informes.validar') }}?departamento_id=${depto}&periodo=${periodo}&anio=${anio}&mes=${mes}`);
            const resultado = await response.json();

            // 5. Lógica de decisión según el conteo
            if (resultado.count > 0) {
                Swal.close(); // Cerramos el cargando

                let rutaBase = (tipo === 'pdf') 
                    ? "{{ route('informes.pdf') }}" 
                    : "{{ route('informes.excel') }}";

                // Construimos la URL final
                const url = `${rutaBase}?departamento_id=${depto}&periodo=${periodo}&anio=${anio}&mes=${mes}`;
                
                // Iniciamos la descarga real
                window.location.href = url;
            } else {
                // Si count es 0, mostramos alerta y NO descargamos
                Swal.fire({
                    icon: 'info',
                    title: 'Sin registros',
                    text: 'No existen evaluaciones para el departamento y período seleccionado.',
                    confirmButtonColor: '#003366'
                });
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Hubo un problema al conectar con el servidor.', 'error');
        }
    }
    
   
</script>
@endsection