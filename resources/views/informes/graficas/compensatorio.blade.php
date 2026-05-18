@extends('layouts.app')

@section('content')

{{-- =========================================================
     ESTILOS PERSONALIZADOS DE LA VISTA
========================================================== --}}
<style>
    /*
    |--------------------------------------------------------------------------
    | Contenedor principal de la gráfica
    |--------------------------------------------------------------------------
    | Define el tamaño, fondo oscuro, bordes redondeados y espaciado
    | interno para que haga juego con el diseño institucional.
    |--------------------------------------------------------------------------
    */
    .grafico-container {
        position: relative;
        height: 500px;
        width: 100%;
        background-color: #141820 !important;
        border-radius: 15px;
        padding: 30px;
        border: 1px solid #d1d3e2;
    }
</style>

{{-- =========================================================
     CONTENEDOR GENERAL
========================================================== --}}
<div class="container-fluid">

    {{-- Tarjeta principal --}}
    <div class="card shadow mb-4">

        {{-- ENCABEZADO DE LA TARJETA --}}
        <div class="card-header py-3 bg-white text-center">
            <h2 class="m-0 font-weight-bold text-primary">
                Análisis Estadístico de Tiempo Compensatorio
            </h2>
        </div>

        {{-- CUERPO PRINCIPAL --}}
        <div class="card-body" style="background-color: #f8f9fc;">

            {{-- FILTROS DE BÚSQUEDA --}}
            <div class="row align-items-end mb-4">

                {{-- 1. FILTRO DE DEPARTAMENTO --}}
                <div class="col-md-3">
                    <label class="font-weight-bold text-dark">1. Departamento:</label>
                    <select id="depto_id" class="form-control" onchange="cargarEmpleados()">
                        <option value="">Seleccione Depto...</option>
                        @foreach($departamentos as $d)
                            <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. FILTRO DE EMPLEADO --}}
                <div class="col-md-3">
                    <label class="font-weight-bold text-dark">2. Empleado:</label>
                    <select id="empleado_id" class="form-control">
                        <option value="">Seleccione primero un depto...</option>
                    </select>
                </div>

                {{-- 3. FILTRO DE PERÍODO --}}
                <div class="col-md-2">
                    <label class="font-weight-bold text-dark">3. Período:</label>
                    <select id="periodo" class="form-control" onchange="actualizarInterfaz()">
                        <option value="" selected disabled>Elija...</option>
                        <option value="anual">Anual</option>
                        <option value="mensual">Mensual</option>
                    </select>
                </div>

                {{-- 4. FILTRO DE AÑO --}}
                <div class="col-md-2 d-none" id="div_anio">
                    <label class="font-weight-bold text-dark">4. Año:</label>
                    <select id="anio_v" class="form-control">
                        <option value="">Elija...</option>
                        @foreach($anios as $a)
                            <option value="{{ $a }}">{{ $a }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 5. FILTRO DE MES --}}
                <div class="col-md-1 d-none" id="div_mes">
                    <label class="font-weight-bold text-dark">5. Mes:</label>
                    <select id="mes_v" class="form-control">
                        <option value="">Elija...</option>
                        @foreach([
                            '01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr',
                            '05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago',
                            '09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'
                        ] as $val => $nombre)
                            <option value="{{ $val }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- BOTÓN DE ACCIÓN --}}
                <div class="col-md-1">
                    <button class="btn btn-primary btn-block shadow" onclick="generarGraficaCompensatorio()">
                        <i class="fas fa-chart-bar mr-1"></i> Gráfica
                    </button>
                </div>
            </div>

            {{-- CONTENEDOR DE LA GRÁFICA --}}
            <div class="row">
                <div class="col-12">
                    <div class="grafico-container">
                        <canvas id="canvasCompensatorio"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- =========================================================
     SCRIPT DE FUNCIONALIDAD JAVASCRIPT
========================================================== --}}
<script>
    let miGraficaCompensatorio = null;
    
    // Registrar el plugin de las etiquetas sobre las barras
    Chart.register(ChartDataLabels);

    // FUNCIÓN: actualizarInterfaz()
    function actualizarInterfaz() {
        const periodo = document.getElementById('periodo').value;
        const divAnio = document.getElementById('div_anio');
        const divMes = document.getElementById('div_mes');
        
        if (periodo === 'anual' || periodo === 'mensual') {
            divAnio.classList.remove('d-none');
        } else {
            divAnio.classList.add('d-none');
            document.getElementById('anio_v').value = "";
        }

        if (periodo === 'mensual') {
            divMes.classList.remove('d-none');
        } else {
            divMes.classList.add('d-none');
            document.getElementById('mes_v').value = "";
        }
    }

    // FUNCIÓN: cargarEmpleados()
    function cargarEmpleados() {
        const deptoId = document.getElementById('depto_id').value;
        const selectEmp = document.getElementById('empleado_id');
        
        if (!deptoId) {
            selectEmp.innerHTML = `<option value="">Seleccione un departamento...</option>`;
            return;
        }

        selectEmp.innerHTML = `<option value="">Cargando empleados...</option>`;

        // Reutiliza tu ruta existente de empleados
        const urlBase = "{{ route('get.empleados', ':id') }}".replace(':id', deptoId);

        fetch(urlBase)
            .then(response => {
                if (!response.ok) throw new Error('Error en el servidor');
                return response.json();
            })
            .then(data => {
                let html = `<option value="">Seleccione un empleado...</option>`;
                
                if (data.length === 0) {
                    html = `<option value="">No hay empleados registrados aquí</option>`;
                } else {
                    data.forEach(emp => {
                        const nombre = emp.nombre || '';
                        const apellido = emp.apellido || '';
                        html += `<option value="${emp.id}">${nombre} ${apellido}</option>`;
                    });
                }
                selectEmp.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                selectEmp.innerHTML = `<option value="">Error al cargar</option>`;
            });
    }

    
    // FUNCIÓN: generarGraficaCompensatorio()
    function generarGraficaCompensatorio() {
        const depto = document.getElementById('depto_id').value;
        const emp = document.getElementById('empleado_id').value;
        const periodo = document.getElementById('periodo').value;
        const anio = document.getElementById('anio_v').value;
        const mes = document.getElementById('mes_v').value;

        // Función interna auxiliar para limpiar la gráfica si existe
        function limpiarGrafica() {
            if (miGraficaCompensatorio) {
                miGraficaCompensatorio.destroy();
                miGraficaCompensatorio = null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN ESTRICTA DE TODOS LOS CAMPOS (UNO POR UNO)
        |--------------------------------------------------------------------------
        */
        
        // 1. Validar Departamento
        if (!depto) {
            limpiarGrafica();
            Swal.fire('Falta el Departamento', 'Por favor, seleccione un departamento de la lista.', 'warning');
            return;
        }

        // 2. Validar Empleado / Colaborador
        if (!emp) {
            limpiarGrafica();
            Swal.fire('Falta el Colaborador', 'Por favor, seleccione un empleado para analizar.', 'warning');
            return;
        }

        // 3. Validar Período
        if (!periodo) {
            limpiarGrafica();
            Swal.fire('Falta el Período', 'Por favor, elija si el análisis será Anual o Mensual.', 'warning');
            return;
        }

        // 4. Validar Año
        if (!anio) {
            limpiarGrafica();
            Swal.fire('Falta el Año', 'Por favor, seleccione el año correspondiente para la búsqueda.', 'warning');
            return;
        }

        // 5. Validar Mes (Únicamente si el período seleccionado es "Mensual")
        if (periodo === 'mensual' && (!mes || mes === '' || mes.includes('Elija'))) {
            limpiarGrafica();
            Swal.fire('Falta el Mes', 'Ha seleccionado el período Mensual. Por favor, elija un mes específico para generar la gráfica.', 'warning');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PROCESO DE CARGA (SI TODOS LOS CAMPOS ESTÁN DEBIDAMENTE LLENOS)
        |--------------------------------------------------------------------------
        */
        Swal.fire({
            title: 'Calculando balances...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const url = `{{ route('graficas.data.compensatorio') }}?empleado_id=${emp}&anio=${anio}&periodo=${periodo}&mes=${mes}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                Swal.close();
                limpiarGrafica();

                if (data.ganadas === 0 && data.usadas === 0) {
                    Swal.fire('Sin registros', 'No se encontraron movimientos de tiempo compensatorio para los filtros seleccionados.', 'info');
                    return;
                }

                const ctx = document.getElementById('canvasCompensatorio').getContext('2d');

                // Renderizado de barras dobles verticales comparativas
                miGraficaCompensatorio = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Balance de Horas'],
                        datasets: [
                            {
                                label: 'Horas Extras Ganadas',
                                data: [data.ganadas],
                                backgroundColor: '#4e73df', // Azul institucional
                                borderRadius: 5
                            },
                            {
                                label: 'Horas Compensadas (Usadas)',
                                data: [data.usadas],
                                backgroundColor: '#e74a3b', // Rojo alerta
                                borderRadius: 5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(255,255,255,0.1)' },
                                ticks: { color: '#ffffff', font: { weight: 'bold' } }
                            },
                            x: {
                                ticks: { color: '#ffffff', font: { size: 14, weight: 'bold' } }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: { color: '#ffffff', font: { weight: 'bold' } }
                            },
                            datalabels: {
                                color: '#ffffff',
                                anchor: 'end',
                                align: 'top',
                                offset: 5,
                                font: { weight: 'bold', size: 13 },
                                formatter: (value) => value + ' hrs'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                Swal.close();
                console.error(error);
                Swal.fire('Error', 'No se pudieron recuperar las estadísticas contables.', 'error');
            });
    }
</script>
@endsection