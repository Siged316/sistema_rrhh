@extends('layouts.app')

@section('content')
<style>
    .grafico-container {
        position: relative; 
        height: 500px; 
        width: 100%; 
        background-color: #141820 !important; 
        border-radius: 15px; 
        padding: 30px; 
        border: 1px solid #d1d3e2;
    }

    #canvasDepto {
        background-color: rgba(0,0,0,0) !important;
    }

    /* Estilo para el contenedor de checkboxes */
    .checkbox-group {
        background-color: #ffffff;
        border: 1px solid #d1d3e2;
        border-radius: 5px;
        padding: 10px;
        max-height: 150px;
        overflow-y: auto;
    }
    .custom-control-label { cursor: pointer; }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2 class="font-weight-bold text-dark">
                <i class="fas fa-chart-bar text-primary mr-2"></i> Comparativa de Desempeño por Departamento
            </h2>
            <hr>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body" style="background-color: #f8f9fc;"> 
            {{-- Filtros --}}
            <div class="row align-items-end mb-4">
                {{-- Checkboxes de Departamentos --}}
                <div class="col-md-4">
                    <label class="font-weight-bold text-gray-800">Departamentos a Comparar:</label>
                    <div class="checkbox-group">
                        @foreach($departamentos as $depto)
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input depto-check" id="depto_{{ $depto->id }}" value="{{ $depto->id }}">
                                <label class="custom-control-label text-gray-800" for="depto_{{ $depto->id }}">{{ $depto->nombre }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Selector de Año --}}
                <div class="col-md-2">
                    <label class="font-weight-bold text-gray-800">Año:</label>
                    <select id="anio_valor" class="form-control">
                        @foreach($anios as $anio)
                            <option value="{{ $anio }}">{{ $anio }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Selector de Mes --}}
                <div class="col-md-3">
                    <label class="font-weight-bold text-gray-800">Mes (Opcional):</label>
                    <select id="mes_valor" class="form-control">
                        <option value="">Todo el Año (Acumulado)</option>
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

                <div class="col-md-3">
                    <button class="btn btn-primary btn-block shadow" onclick="cargarDatosGrafica()">
                        <i class="fas fa-sync-alt mr-2"></i> Generar Visualización
                    </button>
                </div>
            </div>

            {{-- ÁREA DE LA GRÁFICA --}}
            <div class="row mt-4">
                <div class="col-12">
                    <div class="grafico-container">
                        <canvas id="canvasDepto"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    let miGrafica = null;

   function cargarDatosGrafica() {
    const checkboxes = document.querySelectorAll('.depto-check:checked');
    const depto_ids = Array.from(checkboxes).map(cb => cb.value);
    
    const anio = document.getElementById('anio_valor').value;
    const mes = document.getElementById('mes_valor').value;

    if (depto_ids.length === 0) {
        Swal.fire('Atención', 'Seleccione al menos un departamento.', 'warning');
        return;
    }

    Swal.fire({ title: 'Generando...', didOpen: () => { Swal.showLoading(); } });

    // Construcción limpia de la URL
    let url = `{{ route('graficas.data.depto') }}?${depto_ids.map(id => `departamento_ids[]=${id}`).join('&')}&anio=${anio}`;
    
    // IMPORTANTE: Aseguramos que el mes no sea una cadena vacía
    if (mes !== "" && mes !== null) {
        url += `&mes=${mes}&periodo=mensual`;
    }

    console.log("URL solicitada:", url); // Revisa esto en F12

    fetch(url)
        .then(response => response.json())
        .then(data => {
            Swal.close();
            const ctx = document.getElementById('canvasDepto').getContext('2d');
            
            if (miGrafica) { miGrafica.destroy(); }

            // Registrar el plugin ANTES de crear la instancia
            Chart.register(ChartDataLabels);

            const colores = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6610f2'];

            miGrafica = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Desempeño %',
                        data: data.valores,
                        backgroundColor: colores.slice(0, data.labels.length),
                        borderRadius: 5,
                        barPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: 110,
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            ticks: { color: '#ffffff' }
                        },
                        x: { 
                            ticks: { color: '#ffffff' }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            color: '#ffffff',
                            anchor: 'end',
                            align: 'top',
                            offset: 5,
                            font: { weight: 'bold', size: 13 },
                            formatter: (value) => value + '%'
                        }
                    }
                }
            });
        });
   }
</script>
@endsection