

@extends('layouts.app')

@section('styles')
<style>
    body { background-color: #f8fafc; }
    .section-title { font-weight: 800; font-size: 1.4rem; color: #1e293b; }
    .card-soft { border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.07); }
    .card-pending { border-left: 5px solid #0ea5e9 !important; background: #fff; }

    /* Estilos para el PDF */
    @media print {
        .page-break { page-break-before: always; }
    }
    
    #contenedorComparativa canvas {
        max-width: 100% !important;
        height: auto !important;
        
    }

    table { page-break-inside: avoid; }
    .card { page-break-inside: avoid; }

   /* Asegura que el clon mantenga sus colores al ser procesado */
    #encabezado-pdf, .bg-light {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Evita que el contenedor oculte el contenido por accidente */
    #contenedorComparativa {
        min-height: 500px;
        overflow: visible !important;
    }
    /* Asegurar que las tablas ocupen el 100% del ancho asignado sin salirse */
    #contenedorComparativa table {
        width: 100% !important;
        table-layout: fixed; /* Ayuda a que las celdas no empujen el borde */
        word-wrap: break-word;
    }

    /* Evita que las filas de la tabla se dividan entre dos hojas */
    #contenedorComparativa table tr {
        page-break-inside: avoid !important;
    }

    /* Asegura que la gráfica o imagen ocupe el espacio correcto */
    #contenedorComparativa img {
        display: block;
        margin: 0 auto;
        max-height: 400px; /* Evita que la gráfica sea demasiado alta y empuje la tabla */
        object-fit: contain;
    }


</style>
@endsection

@section('content')
<div class="container py-4">

    {{-- PENDIENTES --}}
    <div class="mb-4">
        <h3 class="section-title">Evaluaciones Pendientes</h3>
        <div class="row">
            @forelse($evaluaciones as $eval)
                <div class="col-md-6 mb-3">
                    <div class="card card-soft card-pending">
                        <div class="card-body d-flex justify-content-between">
                            <div>
                                <h5>{{ $eval->nombre_formulario }}</h5>
                                <small class="text-muted">{{ $eval->tipo }}</small>
                                <small class="text-primary fw-bold">
                                   → Evaluado: {{ $eval->nombre_colaborador }}
                               </small>
                            </div>
                            <a href="{{ route('evaluaciones.llenar', $eval->id) }}" class="btn btn-primary btn-sm">Llenar</a>
                        </div>
                    </div>
                </div>
            @empty
                <p class="ps-3 text-muted">No hay evaluaciones pendientes por ahora.</p>
            @endforelse
        </div>
    </div>

    {{-- COMPARATIVA --}}
    <div class="card card-soft p-4">
        <h5 class="fw-bold mb-3">Historial y Comparación Institucional</h5>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Proyecto</label>
                <select id="select_proyecto" class="form-select">
                    <option value="">Seleccione el proyecto</option>
                    @foreach($proyectos as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Departamento</label>
                <select id="select_depto" class="form-select">
                    <option value="">Seleccione Departamento</option>
                    @foreach($departamentos as $depto)
                        <option value="{{ $depto->id }}">{{ $depto->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Colaborador</label>
                <select id="select_empleado" class="form-select" onchange="mostrarConfiguracion()">
                    <option value="">Seleccione Colaborador</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Período</label>
                <select id="select_periodo" class="form-select">
                    <option value="">Seleccione un período</option>
                    <option value="mensual">Mensual</option>
                    <option value="trimestral">Trimestral</option>
                    <option value="anual">Anual</option>
                </select>
            </div>
            <div id="configuracion_metodo" class="col-md-4 d-none">
                <label class="form-label small fw-bold">Método de Cálculo</label>
                <select id="tipo_promedio" class="form-select">
                    <option value="simple">Promedio simple</option>
                    <option value="ponderado">Promedio ponderado</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="button" onclick="cargarComparativa()" class="btn btn-success fw-bold px-4">
                <i class="fas fa-sync me-2"></i>Generar Comparativa
            </button>

            <button type="button" id="btnPdf" onclick="generarPDF()" class="btn btn-primary d-none fw-bold px-4">
                <i class="fas fa-file-pdf me-2"></i> PDF
            </button>
        </div>

        {{-- 🔥 CONTENEDOR DONDE SE CARGA EL AJAX --}}
        <div id="contenedorComparativa" class="mt-4 bg-white p-2 rounded"></div>
    </div>

    <hr class="my-5">

   <div class="card card-soft p-4">
    <h5 class="fw-bold mb-3">
        Historial General de Formularios
    </h5>

    <button class="btn btn-secondary mb-3"
            onclick="cargarHistorialProyecto()">
        Ver Historial del Proyecto
    </button>

    <div id="contenedorHistorial"></div>
   </div>


</div>

<script>
// Espera a que todo el DOM esté completamente cargado antes de ejecutar el código
document.addEventListener('DOMContentLoaded', function () {

    // Obtiene el <select> de departamentos
    const deptoSelect = document.getElementById('select_depto');

    // Obtiene el <select> donde se cargarán los empleados
    const empleadoSelect = document.getElementById('select_empleado');

    // Datos de departamentos enviados desde Laravel (PHP → JS)
    // Contiene algo como: [{id: 1, nombre: ..., empleados: [...]}, ...]
    const data = @json($departamentos);

    // Evento cuando el usuario cambia el departamento seleccionado
    deptoSelect.addEventListener('change', function () {

        // Busca el departamento seleccionado dentro del array "data"
        const depto = data.find(d => d.id == this.value);

        // Reinicia el select de empleados con una opción por defecto
        empleadoSelect.innerHTML = '<option value="">Seleccione Colaborador</option>';

        // Si el departamento existe y tiene empleados asociados
        if (depto && depto.empleados) {

            // Recorre los empleados del departamento seleccionado
            depto.empleados.forEach(emp => {

                // Agrega cada empleado como una opción al <select>
                empleadoSelect.add(
                    new Option(
                        emp.nombre + ' ' + emp.apellido, // texto visible
                        emp.id // valor del option
                    )
                );
            });
        }
    });
});

// Función que muestra u oculta la sección de configuración según el empleado seleccionado
function mostrarConfiguracion() {

    // Obtiene el valor del <select> de empleados (ID del empleado seleccionado)
    const empleadoId = document.getElementById('select_empleado').value;

    // Obtiene el contenedor de configuración del método
    // y alterna la clase 'd-none' (oculta el elemento si no hay empleado seleccionado)
    document.getElementById('configuracion_metodo').classList.toggle('d-none', !empleadoId);
}

function cargarComparativa() {
    const empleadoId = document.getElementById('select_empleado').value;
    const proyectoId = document.getElementById('select_proyecto').value;
    const periodo = document.getElementById('select_periodo').value;
    const tipo = document.getElementById('tipo_promedio').value;
    const contenedor = document.getElementById('contenedorComparativa');

    // Validación básica de campos
    if (!empleadoId || !proyectoId || !periodo) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Por favor seleccione Colaborador, Proyecto y Período.',
            confirmButtonColor: '#003366'
        });
        return;
    }

    contenedor.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border" style="color: #003366;" role="status"></div>
            <p class="mt-2">Cargando historial...</p>
        </div>`;

    // Hacemos la petición directamente a la ruta que ya tienes
    fetch(`/evaluaciones/comparar/${empleadoId}?proyecto_id=${proyectoId}&tipo=${tipo}&periodo=${periodo}`)
        .then(r => r.text())
        .then(html => {
            contenedor.innerHTML = html;

            // Buscamos los datos que el controlador envía ocultos
            const datosContenedor = document.getElementById('datos-grafica');
            
            if (datosContenedor) {
                const valores = JSON.parse(datosContenedor.dataset.valores || "[]");

                // VALIDACIÓN: Si eligió Trimestral o Anual pero solo hay 1 dato o ninguno
                if ((periodo === 'trimestral' || periodo === 'anual') && valores.length < 2) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Nota Informativa',
                        text: `No hay suficientes evaluaciones para una comparativa ${periodo}. Se mostrará el detalle individual.`,
                        confirmButtonColor: '#d9534f'
                    });
                    
                    // Ocultamos el cuadro de la gráfica porque no hay qué comparar
                    const canvasBox = document.querySelector('#graficaEvolucion')?.closest('.card');
                    if (canvasBox) canvasBox.style.display = 'none';

                } else if (valores.length > 0) {
                    // Si hay datos (aunque sea uno mensual), mostramos la gráfica
                    inicializarGraficaGlobal();
                }
            }
            
            document.getElementById('btnPdf').classList.remove('d-none');
        })
        .catch(err => {
            console.error(err);
            contenedor.innerHTML = `<div class="alert alert-danger">Error al cargar los datos.</div>`;
        });
}

function generarPDF() {
    const original = document.getElementById('contenedorComparativa');
    const empSelect = document.getElementById('select_empleado');
    const proSelect = document.getElementById('select_proyecto');

    const canvasOriginal = original ? original.querySelector('canvas') : null;
    if (!canvasOriginal) { 
        alert('Por favor, genere la comparativa en pantalla primero.'); 
        return; 
    }

    const btn = document.getElementById('btnPdf');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajustando márgenes...';

    // 1. Clonar
    const clon = original.cloneNode(true);

    // 2. FORZAR ANCHO ESTRECHO (Para que no se salga por la derecha)
    // Reducimos a 700px para garantizar que quepa en el A4
    clon.setAttribute('style', `
        width: 680px !important; 
        max-width: 680px !important;
        background: white !important;
        margin: 0 !important;
        padding: 15px !important;
        position: relative !important;
          display: block !important;
      
        box-sizing: border-box !important;
    `);

    // 3. Limpiar todas las tablas del clon para que no se desborden
    const tablas = clon.querySelectorAll('table');
    tablas.forEach(t => {
        t.style.width = "100%";
        t.style.tableLayout = "fixed"; // Fuerza a las celdas a quedarse dentro
        t.style.wordWrap = "break-word";
    });

    // 4. Procesar Gráfica
    const canvasClon = clon.querySelector('canvas');
    if (canvasClon) {
        const imgData = canvasOriginal.toDataURL('image/png', 1.0);
        const img = document.createElement('img');
        img.src = imgData;
        img.style.width = '100%';
        img.style.height = 'auto';
        canvasClon.parentNode.replaceChild(img, canvasClon);
    }

    // 5. Configurar Encabezado
    const encabezadoClon = clon.querySelector('#encabezado-pdf');
    if (encabezadoClon) {
        encabezadoClon.classList.remove('d-none');
        encabezadoClon.style.display = "block";
        encabezadoClon.querySelector('#pdf_proyecto_nombre').innerText = 
            proSelect.selectedIndex > 0 ? proSelect.options[proSelect.selectedIndex].text : 'SISTEMA DE RRHH';
    }

    // 6. OPCIONES CON ANCHO REDUCIDO
    const opciones = {
        margin: [10, 10, 10, 10],
        filename: `Reporte_IHCI.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2, 
            useCORS: true, 
            width: 680,         // Captura solo 700px
            windowWidth: 680,   // Emula una pantalla de 700px
            x: 0,
            y: 0,
            scrollX: 0,
            scrollY: 0
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    // 7. Ejecución
    html2pdf().set(opciones).from(clon).save().then(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i>Descargar Reporte PDF';
    }).catch(err => {
        console.error(err);
        btn.disabled = false;
    });
}

function inicializarGraficaGlobal() {

    const contenedor = document.getElementById('datos-grafica');
    const canvas = document.getElementById('graficaEvolucion');

    if (!contenedor || !canvas) {
        console.log("No hay datos o canvas");
        return;
    }

    const labels = JSON.parse(contenedor.dataset.labels || "[]");
    const base = JSON.parse(contenedor.dataset.valores || "[]");

    if (base.length === 0) {
        console.log("Sin datos");
        return;
    }

    const ctx = canvas.getContext('2d');

    if (window.myChartInstance) {
        window.myChartInstance.destroy();
    }

    // 🔵 Línea principal (REAL)
    const lineaReal = {
        label: 'Promedio consolidado',
        data: base,
        borderColor: '#0d6efd',
        backgroundColor: 'transparent',
        tension: 0.3,
        borderWidth: 3
    };

    // 🟢 Tendencia suavizada
    const tendencia = {
        label: 'Tendencia',
        data: base.map((v, i, arr) => {
            const prev = arr[i - 1] ?? v;
            return ((Number(v) + Number(prev)) / 2).toFixed(2);
        }),
        borderColor: '#198754',
        borderDash: [6, 4],
        backgroundColor: 'transparent',
        tension: 0.3
    };

    // 🔴 Variación visual
    const variacion = {
        label: 'Variación',
        data: base.map(v => {
            const ruido = (Math.random() * 0.6 - 0.3);
            return (Number(v) + ruido).toFixed(2);
        }),
        borderColor: '#dc3545',
        backgroundColor: 'transparent',
        tension: 0.3
    };

    window.myChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                lineaReal,
                tendencia,
                variacion
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });
}

// Función que carga el historial de un proyecto seleccionado
function cargarHistorialProyecto()
{
    const proyectoId = document.getElementById('select_proyecto').value;

    if (!proyectoId) {
        Swal.fire({ icon: 'warning', title: 'Seleccione un proyecto' });
        return;
    }

    document.getElementById('contenedorHistorial').innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border"></div>
        </div>
    `;

    fetch(`/evaluaciones/historial-proyecto/${proyectoId}`)
        .then(r => r.text())
        .then(html => {

            const contenedor = document.getElementById('contenedorHistorial');
            contenedor.innerHTML = html;

           setTimeout(() => {

             if (typeof $.fn.dataTable === 'undefined') {
                  console.error("DataTables no está cargado");
                 return;
                }

              const table = $('#tablaHistorialProyecto');
           
              if (!table.length) return;

              if ($.fn.dataTable.isDataTable(table)) {
                  table.DataTable().destroy();
                }

               table.DataTable({
                 pageLength: 2, 
                 paging: true,
                 // Configuración de idioma para español
                 language: {
                     "decimal": "",
                     "emptyTable": "No hay información disponible en la tabla",
                      "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                      "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
                      "infoFiltered": "(filtrado de _MAX_ entradas totales)",
                      "infoPostFix": "",
                      "thousands": ",",
                       "lengthMenu": "Mostrar _MENU_ entradas",
                      "loadingRecords": "Cargando...",
                      "processing": "Procesando...",
                       "search": "Buscar:",
                       "zeroRecords": "No se encontraron resultados",
                       "paginate": {
                         "first": "Primero",
                         "last": "Último",
                          "next": "Siguiente",
                          "previous": "Anterior"
                        },
                          "aria": {
                            "sortAscending": ": activar para ordenar la columna ascendente",
                            "sortDescending": ": activar para ordenar la columna descendente"
                        }
                    }
                });

            }, 200);

        });
}

// Función que abre el detalle de un historial específico
function verDetalleHistorial(id)
{
    // Construye la URL del detalle usando el ID recibido
    const url = `/evaluaciones/historial-detalle/${id}`;

    // Abre la vista en la misma pestaña
    window.open(url, '_self');
}
</script>

@endsection