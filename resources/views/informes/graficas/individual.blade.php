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
    | Define:
    | - Tamaño
    | - Fondo oscuro
    | - Bordes redondeados
    | - Espaciado interno
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

        {{-- =========================================================
             ENCABEZADO DE LA TARJETA
        ========================================================== --}}
        <div class="card-header py-3 bg-white text-center">

            {{-- Título principal --}}
            <h2 class="m-0 font-weight-bold text-primary">
                Análisis de Rendimiento Individual
            </h2>
        </div>

        {{-- =========================================================
             CUERPO PRINCIPAL
        ========================================================== --}}
        <div class="card-body" style="background-color: #f8f9fc;">

            {{-- =========================================================
                 FILTROS
            ========================================================== --}}
            <div class="row align-items-end mb-4">

                {{-- =========================================================
                     1. FILTRO DE DEPARTAMENTO
                ========================================================== --}}
                <div class="col-md-3">

                    <label class="font-weight-bold text-dark">
                        1. Departamento:
                    </label>

                    <select id="depto_id" class="form-control" onchange="cargarEmpleados()">

                        <option value="">
                            Seleccione Depto...
                        </option>

                        {{-- Recorrido de departamentos --}}
                        @foreach($departamentos as $d)

                            <option value="{{ $d->id }}">
                                {{ $d->nombre }}
                            </option>

                        @endforeach
                    </select>
                </div>

                {{-- =========================================================
                     2. FILTRO DE EMPLEADO
                ========================================================== --}}
                <div class="col-md-3">

                    <label class="font-weight-bold text-dark">
                        2. Empleado:
                    </label>

                    {{-- Select cargado dinámicamente --}}
                    <select id="empleado_id" class="form-control">

                        <option value="">
                            Seleccione primero un depto...
                        </option>

                    </select>
                </div>

                {{-- =========================================================
                     3. FILTRO DE AÑO
                ========================================================== --}}
                <div class="col-md-2">

                    <label class="font-weight-bold text-dark">
                        Año:
                    </label>

                    <select id="anio_v" class="form-control">
 
                    <option value="">Elija...</option>
                        {{-- Lista de años --}}
                        @foreach($anios as $a)

                            <option value="{{ $a }}">
                                {{ $a }}
                            </option>

                        @endforeach
                    </select>
                </div>

                {{-- =========================================================
                     4. FILTRO DE MES
                ========================================================== --}}
                <div class="col-md-2">

                    <label class="font-weight-bold text-dark">
                        Mes:
                    </label>

                    <select id="mes_v" class="form-control">

                     <option value="">Elija...</option>
                        {{-- Opción general --}}
                        <option value="">
                            Todo el año
                        </option>

                        {{-- Lista de meses --}}
                        @foreach([
                            'Enero','Febrero','Marzo','Abril',
                            'Mayo','Junio','Julio','Agosto',
                            'Septiembre','Octubre','Noviembre','Diciembre'
                        ] as $i => $m)

                            <option value="{{ sprintf('%02d', $i+1) }}">
                                {{ $m }}
                            </option>

                        @endforeach
                    </select>
                </div>

                {{-- =========================================================
                     BOTÓN GENERAR GRÁFICA
                ========================================================== --}}
                <div class="col-md-2">

                    <button 
                        class="btn btn-primary btn-block shadow"
                        onclick="generarGraficaIndividual()"
                    >

                        <i class="fas fa-sync-alt mr-2"></i>

                        Generar Gráfica
                    </button>
                </div>
            </div>

            {{-- =========================================================
                 CONTENEDOR DE LA GRÁFICA
            ========================================================== --}}
            <div class="row">

                <div class="col-12">

                    {{-- Área visual de Chart.js --}}
                    <div class="grafico-container">

                        <canvas id="canvasIndividual"></canvas>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================
     SCRIPT DE FUNCIONALIDAD
========================================================== --}}
<script>

    /*
    |--------------------------------------------------------------------------
    | Variable global de la gráfica
    |--------------------------------------------------------------------------
    | Permite destruir la gráfica anterior antes de crear una nueva.
    |--------------------------------------------------------------------------
    */
    let miGraficaInd = null;
    
    /*
    |--------------------------------------------------------------------------
    | Registro del plugin ChartDataLabels
    |--------------------------------------------------------------------------
    */
    Chart.register(ChartDataLabels);

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN: cargarEmpleados()
    |--------------------------------------------------------------------------
    | Carga dinámicamente los empleados de un departamento.
    |--------------------------------------------------------------------------
    */
    function cargarEmpleados() {

        // Obtener departamento seleccionado
        const deptoId = document.getElementById('depto_id').value;

        // Select de empleados
        const selectEmp = document.getElementById('empleado_id');
        
        /*
        |--------------------------------------------------------------------------
        | Validar si no se seleccionó departamento
        |--------------------------------------------------------------------------
        */
        if (!deptoId) {

            selectEmp.innerHTML = `
                <option value="">
                    Seleccione un departamento...
                </option>
            `;

            return;
        }

        // Mensaje temporal
        selectEmp.innerHTML = `
            <option value="">
                Cargando empleados...
            </option>
        `;

        /*
        |--------------------------------------------------------------------------
        | Construcción dinámica de la ruta
        |--------------------------------------------------------------------------
        */
        const urlBase = "{{ route('get.empleados', ':id') }}"
            .replace(':id', deptoId);

        console.log("Conectando de forma segura a:", urlBase);

        /*
        |--------------------------------------------------------------------------
        | Petición AJAX usando Fetch
        |--------------------------------------------------------------------------
        */
        fetch(urlBase)

            .then(response => {

                // Validar errores HTTP
                if (!response.ok) {

                    console.error("Estado del error:", response.status);

                    throw new Error('Error en el servidor');
                }

                return response.json();
            })

            .then(data => {

                // HTML inicial
                let html = `
                    <option value="">
                        Seleccione un empleado...
                    </option>
                `;
                
                /*
                |--------------------------------------------------------------------------
                | Validar si no hay empleados
                |--------------------------------------------------------------------------
                */
                if (data.length === 0) {

                    html = `
                        <option value="">
                            No hay empleados registrados aquí
                        </option>
                    `;
                } 
                else {

                    /*
                    |--------------------------------------------------------------------------
                    | Recorrido de empleados
                    |--------------------------------------------------------------------------
                    */
                    data.forEach(emp => {

                        const nombre = emp.nombre || '';
                        const apellido = emp.apellido || '';

                        html += `
                            <option value="${emp.id}">
                                ${nombre} ${apellido}
                            </option>
                        `;
                    });
                }

                // Insertar opciones
                selectEmp.innerHTML = html;
            })

            .catch(err => {

                console.error("Error completo:", err);

                selectEmp.innerHTML = `
                    <option value="">
                        Error crítico al cargar
                    </option>
                `;

                Swal.fire(
                    'Error de Ruta',
                    'El servidor no encontró la dirección. Revisa la consola F12.',
                    'error'
                );
            });
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN: generarGraficaIndividual()
    |--------------------------------------------------------------------------
    | Genera la gráfica individual del empleado seleccionado.
    |--------------------------------------------------------------------------
    */
    function generarGraficaIndividual() {

        // Obtener filtros
        const emp = document.getElementById('empleado_id').value;
        const anio = document.getElementById('anio_v').value;
        const mes = document.getElementById('mes_v').value;

        /*
        |--------------------------------------------------------------------------
        | Validar empleado seleccionado
        |--------------------------------------------------------------------------
        */
        if (!emp) {

            Swal.fire(
                'Atención',
                'Debe seleccionar un empleado.',
                'warning'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Mostrar loading
        |--------------------------------------------------------------------------
        */
        Swal.fire({
            title: 'Generando gráfica...',
            didOpen: () => { Swal.showLoading(); }
        });

        /*
        |--------------------------------------------------------------------------
        | URL de consulta
        |--------------------------------------------------------------------------
        */
        const url = `
            {{ route('graficas.data.individual') }}
            ?empleado_id=${emp}
            &anio=${anio}
            &mes=${mes}
        `;

        /*
        |--------------------------------------------------------------------------
        | Petición AJAX
        |--------------------------------------------------------------------------
        */
        fetch(url)

            .then(response => response.json())

            .then(data => {

                Swal.close();
                
                /*
                |--------------------------------------------------------------------------
                | Destruir gráfica anterior
                |--------------------------------------------------------------------------
                */
                if (miGraficaInd) {

                    miGraficaInd.destroy();

                    miGraficaInd = null;
                }
                
                /*
                |--------------------------------------------------------------------------
                | Validar si no hay datos
                |--------------------------------------------------------------------------
                */
                if(!data.valores || data.valores.length === 0){

                    Swal.fire(
                        'Sin registros',
                        'No se encontraron evaluaciones para los filtros seleccionados.',
                        'info'
                    );

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Contexto del canvas
                |--------------------------------------------------------------------------
                */
                const ctx = document
                    .getElementById('canvasIndividual')
                    .getContext('2d');

                /*
                |--------------------------------------------------------------------------
                | Crear nueva gráfica
                |--------------------------------------------------------------------------
                */
                miGraficaInd = new Chart(ctx, {

                    // Tipo de gráfica
                    type: 'line',

                    // Datos
                    data: {

                        // Etiquetas eje X
                        labels: data.labels,

                        // Dataset principal
                        datasets: [{

                            label: 'Desempeño %',

                            data: data.valores,

                            borderColor: '#36b9cc',

                            backgroundColor: 'rgba(54, 185, 204, 0.2)',

                            pointBackgroundColor: '#ffffff',

                            pointBorderColor: '#36b9cc',

                            pointRadius: 6,

                            fill: true,

                            tension: 0.3
                        }]
                    },

                    /*
                    |--------------------------------------------------------------------------
                    | Configuración visual
                    |--------------------------------------------------------------------------
                    */
                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        scales: {

                            /*
                            |--------------------------------------------------------------------------
                            | Eje Y
                            |--------------------------------------------------------------------------
                            */
                            y: {

                                beginAtZero: true,

                                max: 110,

                                grid: {
                                    color: 'rgba(255,255,255,0.1)'
                                },

                                ticks: {
                                    color: '#ffffff',
                                    font: { weight: 'bold' }
                                }
                            },

                            /*
                            |--------------------------------------------------------------------------
                            | Eje X
                            |--------------------------------------------------------------------------
                            */
                            x: {

                                grid: {
                                    display: false
                                },

                                ticks: {
                                    color: '#ffffff',
                                    font: { size: 11 }
                                }
                            }
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | Plugins
                        |--------------------------------------------------------------------------
                        */
                        plugins: {

                            // Ocultar leyenda
                            legend: {
                                display: false
                            },

                            // Etiquetas sobre los puntos
                            datalabels: {

                                color: '#ffffff',

                                align: 'top',

                                offset: 6,

                                font: {
                                    weight: 'bold',
                                    size: 12
                                },

                                formatter: (val) => val + '%'
                            }
                        }
                    }
                });
            })

            .catch(error => {

                Swal.close();

                console.error(error);

                Swal.fire(
                    'Error',
                    'No se pudieron procesar los datos de la gráfica.',
                    'error'
                );
            });
    }
</script>

@endsection