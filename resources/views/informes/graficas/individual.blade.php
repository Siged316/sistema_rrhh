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

   /* Estilos del menú desplegable personalizado */
    .custom-dropdown { position: relative; width: 100%; }
    .dropdown-trigger { 
        width: 100%; text-align: left; background: white; 
        border: 1px solid #ced4da; padding: 0.375rem 0.75rem;
        cursor: pointer; display: flex; justify-content: space-between; align-items: center;
    }
    .dropdown-content {
        display: none; position: absolute; background: white;
        border: 1px solid #ced4da; width: 100%; z-index: 1000;
        max-height: 200px; overflow-y: auto; padding: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .dropdown-content.show { display: block; }
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
        <div class="row">
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
  
                   {{-- Contenedor padre de 4 columnas --}}
                    <div class="col-md-4">
                       <div class="row">
                          {{-- Columna del Año (50%) --}}
                          <div class="col-6">
                              <label class="font-weight-bold text-dark">Año:</label>
                            <div class="custom-dropdown">
                         
                             <div class="dropdown-trigger form-control" id="btnAnios" onclick="toggleMenu()">
                                 Seleccionar años... <i class="fas fa-caret-down"></i>
                               </div>
                             
                               <div class="dropdown-content" id="menuAnios">
                                    @foreach($anios as $a)
                                       <div class="custom-control custom-checkbox" style="padding: 5px 20px;">
                                          <input type="checkbox" class="custom-control-input anio-check" 
                                          id="anio_{{ $a }}" value="{{ $a }}" onchange="actualizarModoUI()">
                                          <label class="custom-control-label" for="anio_{{ $a }}" style="cursor:pointer;">{{ $a }}</label>
                                       </div>
                                    @endforeach
                               </div>
                         </div>
                       </div>

                       {{-- =========================================================
                        3. FILTRO DE AÑO
                         ========================================================== --}}
                        <div class="col-6" id="mesContainer" style="display:none;">
                           <label class="font-weight-bold text-dark">Mes:</label>
                           <select id="mes_v" class="form-control">
                              <option value="todo">Todo el año</option>
                                  @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $m)
                                      <option value="{{ sprintf('%02d', $i+1) }}">{{ $m }}</option>
                                  @endforeach
                           </select>
                        </div>
                    </div>

                    {{-- El indicador ahora está debajo de ambos selects pero dentro del col-md-4 --}}
                     <div class="row">
                          <div class="col-12 mt-2">
                             <div id="modoIndicador" class="font-weight-bold text-primary small"></div>
                          </div>
                      </div>
                  </div>
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
                        <div id="modoIndicador" class="mb-2 font-weight-bold text-primary"></div>
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
    const emp = document.getElementById('empleado_id').value;
    const anios = obtenerAniosSeleccionados();
    const mes = document.getElementById('mes_v').value;

    if (!emp || anios.length === 0) {
        Swal.fire('Atención', 'Seleccione empleado y al menos un año.', 'warning');
        return;
    }

    Swal.fire({ title: 'Generando gráfica...', didOpen: () => Swal.showLoading() });

    let url = `{{ route('graficas.data.individual') }}?empleado_id=${emp}`;
    anios.forEach(a => url += `&anios[]=${a}`);
    if (mes && mes !== 'todo') url += `&mes=${mes}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            Swal.close();
            if (miGraficaInd) miGraficaInd.destroy();

            const ctx = document.getElementById('canvasIndividual').getContext('2d');
            const colores = ['#3498db', '#e74c3c', '#2ecc71', '#f1c40f'];

            const datasets = Object.keys(data.series).map((anio, index) => ({
                label: anio,
                data: data.series[anio],
                backgroundColor: colores[index % colores.length],
                borderRadius: 5,
                barPercentage: 0.7
            }));

            miGraficaInd = new Chart(ctx, {
                type: 'bar',
                data: { labels: data.labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    },
                    plugins: { legend: { position: 'top' } }
                }
            });
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'No se pudo generar la gráfica', 'error');
        });
    }

    function obtenerAniosSeleccionados() {
      return Array.from(
          document.querySelectorAll('.anio-check:checked')
        ).map(c => c.value);
    }

    function actualizarModoUI() {

    const anios = obtenerAniosSeleccionados();

    const mesContainer = document.getElementById('mesContainer');

    const indicador = document.getElementById('modoIndicador');

    if (anios.length > 1) {

        mesContainer.style.display = 'block';

        indicador.innerHTML = '🔵 Modo comparativo (años múltiples)';

    } else {

        mesContainer.style.display = 'none';

        document.getElementById('mes_v').value = 'todo';

        indicador.innerHTML = '🟢 Modo normal (1 año)';
    }
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES PARA EL FILTRO DE AÑOS
    |--------------------------------------------------------------------------
    | Controla la visibilidad del menú desplegable y la lógica de selección.
    |--------------------------------------------------------------------------
    */
    
    // 1. Abrir/Cerrar menú principal
    function toggleMenu() {
    const menu = document.getElementById('menuAnios');
    menu.classList.toggle('show');
    }

    // 2. Cerrar si se hace clic fuera de todo el componente
    document.addEventListener('click', function(event) {
    const menu = document.getElementById('menuAnios');
    const btn = document.getElementById('btnAnios');
    
    // Si el clic no es en el botón ni dentro del menú, cerrar
    if (!btn.contains(event.target) && !menu.contains(event.target)) {
        menu.classList.remove('show');
    }
    });

    // 3. Lógica al marcar un checkbox
    document.querySelectorAll('.anio-check').forEach(item => {
    item.addEventListener('change', function() {
        const checked = document.querySelectorAll('.anio-check:checked');
        const btn = document.getElementById('btnAnios');
        
        // Actualizar el texto del botón
        if (checked.length === 0) {
            btn.innerHTML = 'Seleccionar años... <i class="fas fa-caret-down"></i>';
        } else if (checked.length === 1) {
            btn.innerHTML = checked[0].value + ' <i class="fas fa-caret-down"></i>';
        } else {
            btn.innerHTML = checked.length + ' años seleccionados <i class="fas fa-caret-down"></i>';
        }
        
        // Ejecutar tu lógica de modo
        actualizarModoUI();

        // --- EL TRUCO PARA EL CIERRE ---
        // Usamos un pequeño timeout para asegurar que el navegador procese el clic
        // antes de cerrar el menú.
        setTimeout(() => {
            document.getElementById('menuAnios').classList.remove('show');
        }, 100);
    });
    });

</script>

@endsection