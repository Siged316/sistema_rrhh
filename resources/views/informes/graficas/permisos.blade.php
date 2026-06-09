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
    */
    .grafico-container {
        position: relative;
        height: 500px;
        width: 100%;
        background-color: #eff1f6 !important;
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
                Análisis Estadístico de Permisos y Vacaciones
            </h2>
        </div>

        {{-- CUERPO PRINCIPAL --}}
        <div class="card-body" style="background-color: #f8f9fc;">

            {{-- FILTROS --}}
            <div class="row align-items-end mb-4">

                {{-- 0. TIPO DE SOLICITUD --}}
                <div class="col-md-2">
                    <label class="font-weight-bold text-dark">Tipo:</label>
                    <select class="form-control" id="tipo_solicitud">
                        <option value="">Elija...</option>
                        <option value="TODOS">Todos</option>
                        <option value="Vacaciones">Vacaciones</option>
                        <option value="Permiso">Permisos</option>
                    </select>
                </div>

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

                {{-- 3. FILTRO DE AÑO --}}
                <div class="col-md-1">
                    <label class="font-weight-bold text-dark">Año:</label>
                    <select id="anio_v" class="form-control">
                        <option value="">Elija...</option>
                        @foreach($anios as $a)
                            <option value="{{ $a }}">{{ $a }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 4. FILTRO DE MES --}}
                <div class="col-md-2">
                    <label class="font-weight-bold text-dark">Mes:</label>
                    <select id="mes_v" class="form-control">
                        <option value="">Elija...</option>
                        <option value="">Todo el año</option>
                        @foreach([
                            'Enero','Febrero','Marzo','Abril',
                            'Mayo','Junio','Julio','Agosto',
                            'Septiembre','Octubre','Noviembre','Diciembre'
                        ] as $i => $m)
                            <option value="{{ sprintf('%02d', $i+1) }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- BOTÓN GENERAR GRÁFICA --}}
                <div class="col-md-1">
                    <button class="btn btn-primary btn-block shadow" onclick="generarGraficaPermisos()">
                         <i class="fas fa-sync-alt mr-2"></i>
                        Generar Gráfica
                    </button>
                </div>
            </div>

            {{-- CONTENEDOR DE LA GRÁFICA --}}
            <div class="row">
                <div class="col-12">
                    <div class="grafico-container">
                        <canvas id="canvasPermisos"></canvas>
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
    let miGraficaPermisos = null;
    
    // Registrar el plugin de etiquetas
    Chart.register(ChartDataLabels);

    // FUNCIÓN: cargarEmpleados()
    function cargarEmpleados() {
        const deptoId = document.getElementById('depto_id').value;
        const selectEmp = document.getElementById('empleado_id');
        
        if (!deptoId) {
            selectEmp.innerHTML = `<option value="">Seleccione un departamento...</option>`;
            return;
        }

        selectEmp.innerHTML = `<option value="">Cargando empleados...</option>`;

        const urlBase = "{{ route('get.empleados', ':id') }}".replace(':id', deptoId);

        console.log("Conectando de forma segura a:", urlBase);

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
                console.error("Error completo:", err);
                selectEmp.innerHTML = `<option value="">Error crítico al cargar</option>`;
                Swal.fire('Error de Ruta', 'El servidor no encontró la dirección. Revisa la consola F12.', 'error');
            });
    }

    // FUNCIÓN: generarGraficaPermisos()
   function generarGraficaPermisos() {
        const emp = document.getElementById('empleado_id').value;
        const anio = document.getElementById('anio_v').value;
        const mes = document.getElementById('mes_v').value;
        const tipo = document.getElementById('tipo_solicitud').value;

        if (!emp || !anio) {
            Swal.fire('Atención', 'Debe seleccionar un empleado y un año obligatorio.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Analizando solicitudes...',
            didOpen: () => { Swal.showLoading(); }
        });

        const url = `{{ route('graficas.data.permisos') }}?empleado_id=${emp}&anio=${anio}&mes=${mes}&tipo_solicitud=${tipo}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                // Destruir gráfica anterior para evitar duplicados
                if (miGraficaPermisos) {
                    miGraficaPermisos.destroy();
                    miGraficaPermisos = null;
                }
                
                if(!data.valores || data.valores.length === 0){
                    Swal.fire('Sin registros', 'No se encontraron solicitudes aprobadas en este periodo.', 'info');
                    return;
                }

                const ctx = document.getElementById('canvasPermisos').getContext('2d');

                // ACTIVAMOS EL REGISTRO SEGURO DEL PLUGIN ANTES DE GENERAR LA INSTANCIA
                if (typeof ChartDataLabels !== 'undefined') {
                    Chart.register(ChartDataLabels);
                }

                miGraficaPermisos = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Total Horas Laborales Ausente',
                            data: data.valores,
                            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                            borderRadius: 6,
                            barPercentage: 0.5
                        }]
                    },
                    options: {
                       indexAxis: 'y',
                       responsive: true,
                       maintainAspectRatio: false,
                       layout: {
                          padding: { right: 110 }
                        },
                       scales: {
                         y: {
                             grid: { display: false },
                             // Cambiado a negro
                             ticks: { color: '#000000', font: { size: 12, weight: 'bold' } }
                            },
                          x: {
                              beginAtZero: true,
                               // Cambiado a gris oscuro para la línea de rejilla, negro para el texto
                               grid: { color: 'rgba(0,0,0,0.1)' },
                               title: {
                                 display: true,
                                 text: 'Horas', // <--- ESTA ES LA ETIQUETA QUE PIDES
                                 color: '#000000',
                                 font: { size: 14, weight: 'bold' }
                                },
                               ticks: {
                                  color: '#000000',
                                   font: { weight: 'bold' },
                                 callback: function(value) { return value + ' hrs'; }
                                }

           
                            }

                        },
                       plugins: {
                          legend: { display: false },
                          datalabels: {
                              // Cambiado a negro
                             color: '#000000', 
                             anchor: 'end',
                             align: 'right',
                             offset: 10,
                              display: true,
                              font: { weight: 'bold', size: 12 },
                              formatter: (value, context) => {
                                  const index = context.dataIndex;
                                  return data.etiquetas[index]; 
                                }
                           }
                        }
                    }
                });
            })
        .catch(error => {
            Swal.close();
            console.error(error);
            Swal.fire('Error', 'No se pudieron procesar las estadísticas.', 'error');
       });
    }
</script>
@endsection