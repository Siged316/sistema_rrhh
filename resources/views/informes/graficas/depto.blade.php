@extends('layouts.app')

@section('content')

{{-- =========================================================
     ESTILOS PERSONALIZADOS
========================================================== --}}
<style>

    /*
    |--------------------------------------------------------------------------
    | Contenedor principal de la gráfica
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

    /*
    |--------------------------------------------------------------------------
    | Canvas de la gráfica
    |--------------------------------------------------------------------------
    */
    #canvasDepto {
        background-color: rgba(0,0,0,0) !important;
    }

    /*
    |--------------------------------------------------------------------------
    | Contenedor de checkboxes
    |--------------------------------------------------------------------------
    */
    .checkbox-group {
        background-color: #ffffff;
        border: 1px solid #d1d3e2;
        border-radius: 5px;
        padding: 10px;
        max-height: 150px;
        overflow-y: auto;
    }

    /*
    |--------------------------------------------------------------------------
    | Cursor para labels
    |--------------------------------------------------------------------------
    */
    .custom-control-label {
        cursor: pointer;
    }
</style>

{{-- =========================================================
     CONTENEDOR PRINCIPAL
========================================================== --}}
<div class="container-fluid">

    {{-- =========================================================
         TÍTULO PRINCIPAL
    ========================================================== --}}
    <div class="row mb-4">

        <div class="col-12 text-center">

            <h2 class="font-weight-bold text-dark">

                <i class="fas fa-chart-bar text-primary mr-2"></i>

                Comparativa de Desempeño por Departamento
            </h2>

            <hr>
        </div>
    </div>

    {{-- =========================================================
         TARJETA PRINCIPAL
    ========================================================== --}}
    <div class="card shadow mb-4">

        {{-- =========================================================
             CUERPO DE LA TARJETA
        ========================================================== --}}
        <div class="card-body" style="background-color: #f8f9fc;"> 

            {{-- =========================================================
                 FILTROS
            ========================================================== --}}
            <div class="row align-items-end mb-4">

                {{-- =========================================================
                     CHECKBOXES DE DEPARTAMENTOS
                ========================================================== --}}
                <div class="col-md-4">

                    <label class="font-weight-bold text-gray-800">
                        Departamentos a Comparar:
                    </label>

                    {{-- Contenedor scrollable --}}
                    <div class="checkbox-group">

                        {{-- Recorrido de departamentos --}}
                        @foreach($departamentos as $depto)

                            <div class="custom-control custom-checkbox mb-1">

                                {{-- Checkbox --}}
                                <input 
                                    type="checkbox"
                                    class="custom-control-input depto-check"
                                    id="depto_{{ $depto->id }}"
                                    value="{{ $depto->id }}"
                                >

                                {{-- Nombre del departamento --}}
                                <label 
                                    class="custom-control-label text-gray-800"
                                    for="depto_{{ $depto->id }}"
                                >
                                    {{ $depto->nombre }}
                                </label>
                            </div>

                        @endforeach
                    </div>
                </div>

                {{-- =========================================================
                     SELECTOR DE AÑO
                ========================================================== --}}
                <div class="col-md-2">

                    <label class="font-weight-bold text-gray-800">
                        Año:
                    </label>

                    <select id="anio_valor" class="form-control">

                        <option value="" selected disabled>
                            Elija...
                        </option>

                        {{-- Recorrido de años --}}
                        @foreach($anios as $anio)

                            <option value="{{ $anio }}">
                                {{ $anio }}
                            </option>

                        @endforeach
                    </select>
                </div>

                {{-- =========================================================
                     SELECTOR DE MES
                ========================================================== --}}
                <div class="col-md-3">

                    <label class="font-weight-bold text-gray-800">
                        Mes:
                    </label>

                    <select id="mes_valor" class="form-control">

                        <option value="" selected disabled>
                            Elija...
                        </option>

                        {{-- Opción acumulada --}}
                        <option value="">
                            Todo el Año (Acumulado)
                        </option>

                        {{-- Lista de meses --}}
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>

                {{-- =========================================================
                     BOTÓN GENERAR VISUALIZACIÓN
                ========================================================== --}}
                <div class="col-md-3">

                    <button 
                        class="btn btn-primary btn-block shadow"
                        onclick="cargarDatosGrafica()"
                    >

                        <i class="fas fa-sync-alt mr-2"></i>

                        Generar Visualización
                    </button>
                </div>
            </div>

            {{-- =========================================================
                 ÁREA DE LA GRÁFICA
            ========================================================== --}}
            <div class="row mt-4">

                <div class="col-12">

                    {{-- Contenedor visual --}}
                    <div class="grafico-container">

                        {{-- Canvas Chart.js --}}
                        <canvas id="canvasDepto"></canvas>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================
     SCRIPT PRINCIPAL
========================================================== --}}
<script>

    /*
    |--------------------------------------------------------------------------
    | Variable global de la gráfica
    |--------------------------------------------------------------------------
    */
    let miGrafica = null;

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN: cargarDatosGrafica()
    |--------------------------------------------------------------------------
    | Obtiene datos desde Laravel y genera la gráfica.
    |--------------------------------------------------------------------------
    */
    function cargarDatosGrafica() {

        /*
        |--------------------------------------------------------------------------
        | Obtener departamentos seleccionados
        |--------------------------------------------------------------------------
        */
        const checkboxes = document.querySelectorAll('.depto-check:checked');

        const depto_ids = Array
            .from(checkboxes)
            .map(cb => cb.value);
        
        /*
        |--------------------------------------------------------------------------
        | Obtener filtros
        |--------------------------------------------------------------------------
        */
        const anio = document.getElementById('anio_valor').value;

        const mes = document.getElementById('mes_valor').value;

        /*
        |--------------------------------------------------------------------------
        | Validar selección mínima
        |--------------------------------------------------------------------------
        */
        if (depto_ids.length === 0) {

            Swal.fire(
                'Atención',
                'Seleccione al menos un departamento.',
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
            title: 'Generando...',
            didOpen: () => { Swal.showLoading(); }
        });

        /*
        |--------------------------------------------------------------------------
        | Construcción dinámica de URL
        |--------------------------------------------------------------------------
        */
        let url = `
            {{ route('graficas.data.depto') }}
            ?${depto_ids.map(id => `departamento_ids[]=${id}`).join('&')}
            &anio=${anio}
        `;
        
        /*
        |--------------------------------------------------------------------------
        | Agregar mes si fue seleccionado
        |--------------------------------------------------------------------------
        */
        if (mes !== "" && mes !== null) {

            url += `&mes=${mes}&periodo=mensual`;
        }

        console.log("URL solicitada:", url);

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
                | Obtener contexto del canvas
                |--------------------------------------------------------------------------
                */
                const ctx = document
                    .getElementById('canvasDepto')
                    .getContext('2d');
                
                /*
                |--------------------------------------------------------------------------
                | Destruir gráfica anterior
                |--------------------------------------------------------------------------
                */
                if (miGrafica) {

                    miGrafica.destroy();
                }

                /*
                |--------------------------------------------------------------------------
                | Registrar plugin de etiquetas
                |--------------------------------------------------------------------------
                */
                Chart.register(ChartDataLabels);

                /*
                |--------------------------------------------------------------------------
                | Lista de colores dinámicos
                |--------------------------------------------------------------------------
                */
                const colores = [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e',
                    '#e74a3b',
                    '#6610f2'
                ];

                /*
                |--------------------------------------------------------------------------
                | Crear gráfica
                |--------------------------------------------------------------------------
                */
                miGrafica = new Chart(ctx, {

                    // Tipo de gráfica
                    type: 'bar',

                    // Datos
                    data: {

                        // Etiquetas del eje X
                        labels: data.labels,

                        // Dataset principal
                        datasets: [{

                            label: 'Desempeño %',

                            data: data.valores,

                            // Colores dinámicos
                            backgroundColor: colores.slice(
                                0,
                                data.labels.length
                            ),

                            // Bordes redondeados
                            borderRadius: 5,

                            // Tamaño de barras
                            barPercentage: 0.7
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
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },

                                ticks: {
                                    color: '#ffffff'
                                }
                            },

                            /*
                            |--------------------------------------------------------------------------
                            | Eje X
                            |--------------------------------------------------------------------------
                            */
                            x: {

                                ticks: {
                                    color: '#ffffff'
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

                            /*
                            |--------------------------------------------------------------------------
                            | Etiquetas encima de barras
                            |--------------------------------------------------------------------------
                            */
                            datalabels: {

                                color: '#ffffff',

                                anchor: 'end',

                                align: 'top',

                                offset: 5,

                                font: {
                                    weight: 'bold',
                                    size: 13
                                },

                                formatter: (value) => value + '%'
                            }
                        }
                    }
                });
            });
   }
</script>

@endsection