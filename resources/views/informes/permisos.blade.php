@extends('layouts.app') {{-- Extiende la plantilla principal de la aplicación --}}

@section('content') {{-- Inicio de la sección content que se inyecta en el layout --}}
<div class="container-fluid"> {{-- Contenedor principal fluido --}}
    
    {{-- Botón para regresar al centro de informes --}}
    <div class="mb-3">
        <a href="{{ route('informes.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Centro de Informes
        </a>
    </div>

    {{-- Fila principal centrada --}}
    <div class="row justify-content-center">
        <div class="col-lg-11"> {{-- Columna de ancho grande --}}
            
            {{-- Tarjeta principal del reporte --}}
            <div class="card shadow-lg border-0">
                
                {{-- Encabezado institucional --}}
                <div class="card-header py-3" style="background-color: #003366 !important; color: white;">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-umbrella-beach me-2"></i> 
                        Reporte de Permisos y Vacaciones
                    </h5>
                </div>

                {{-- Cuerpo de la tarjeta --}}
                <div class="card-body p-5">

                    {{-- Formulario principal --}}
                    <form id="formPermisos">

                        {{-- Token CSRF para protección de formularios --}}
                        @csrf

                        {{-- Fila de filtros --}}
                        <div class="row g-4">

                        {{-- =========================================================
      NUEVO: SELECCIÓN DE TIPO DE SOLICITUD
========================================================== --}}
<div class="col-md-2">
    <label class="form-label fw-bold text-dark">
        Tipo de Solicitud
    </label>
    <select 
        class="form-select form-select-lg border-2 shadow-sm" 
        name="tipo_solicitud" 
        id="tipo_solicitud"
    >
        <option value="TODOS">Todos</option>
        <option value="Vacaciones">Vacaciones</option>
        <option value="Permiso">Permisos</option>
    </select>
</div>

                            {{-- =========================================================
                                 1. FILTRO POR DEPARTAMENTO
                            ========================================================== --}}
                            <div class="col-md-3">

                                {{-- Etiqueta del filtro --}}
                                <label class="form-label fw-bold text-dark">
                                    1. Filtrar por Depto.
                                </label>

                                {{-- Select de departamentos --}}
                                <select 
                                    class="form-select form-select-lg border-2 shadow-sm" 
                                    id="depto_filtro" 
                                    onchange="filtrarEmpleados()"
                                >

                                    {{-- Opción para mostrar todos --}}
                                    <option value="todos">
                                        Todos los Departamentos
                                    </option>

                                    {{-- Recorrido de departamentos --}}
                                    @foreach($departamentos as $d)

                                        {{-- Opción dinámica de departamento --}}
                                        <option value="{{ $d->id }}">
                                            {{ $d->nombre }}
                                        </option>

                                    @endforeach
                                </select>
                            </div>

                            {{-- =========================================================
                                 2. SELECCIÓN DE COLABORADOR
                            ========================================================== --}}
                            <div class="col-md-3">

                                {{-- Etiqueta del colaborador --}}
                                <label class="form-label fw-bold text-dark">
                                    2. Colaborador
                                </label>

                                {{-- Select de empleados --}}
                                <select 
                                    class="form-select form-select-lg border-2 shadow-sm" 
                                    name="empleado_id" 
                                    id="empleado_id" 
                                    required
                                >

                                    {{-- Opción inicial --}}
                                    <option value="" selected disabled>
                                        Seleccione...
                                    </option>

                                    {{-- Recorrido de empleados --}}
                                    @foreach($empleados as $e)

                                        {{-- 
                                            Cada empleado guarda su departamento
                                            en data-depto para poder filtrarlo
                                            mediante JavaScript
                                        --}}
                                        <option 
                                            value="{{ $e->id }}" 
                                            data-depto="{{ $e->departamento_id }}"
                                        >
                                            {{ $e->nombre }} {{ $e->apellido }}
                                        </option>

                                    @endforeach
                                </select>
                            </div>

                            {{-- =========================================================
                                 3. SELECCIÓN DE PERÍODO
                            ========================================================== --}}
                            <div class="col-md-2">

                                {{-- Etiqueta del período --}}
                                <label class="form-label fw-bold text-dark">
                                    3. Período
                                </label>

                                {{-- Select del tipo de período --}}
                                <select 
                                    class="form-select form-select-lg border-2 shadow-sm" 
                                    name="periodo" 
                                    id="periodo" 
                                    onchange="actualizarInterfaz()"
                                >

                                    {{-- Opción inicial --}}
                                    <option value="" selected disabled>
                                        Elija...
                                    </option>

                                    {{-- Opciones disponibles --}}
                                    <option value="anual">Anual</option>
                                    <option value="mensual">Mensual</option>
                                </select>
                            </div>

                            {{-- =========================================================
                                 4. SELECCIÓN DE AÑO FISCAL
                                 (Oculto inicialmente)
                            ========================================================== --}}
                            <div class="col-md-2 d-none" id="div_anio">

                                {{-- Etiqueta del año --}}
                                <label class="form-label fw-bold text-dark">
                                    4. Año
                                </label>

                                {{-- Select de años --}}
                                <select 
                                    class="form-select form-select-lg border-2 shadow-sm" 
                                    name="anio" 
                                    id="anio_valor"
                                >

                                    {{-- Opción inicial --}}
                                    <option value="" selected disabled>
                                        Elija...
                                    </option>

                                    {{-- Recorrido dinámico de años --}}
                                    @foreach($anios as $a)

                                        <option value="{{ $a }}">
                                            {{ $a }}
                                        </option>

                                    @endforeach
                                </select>
                            </div>

                            {{-- =========================================================
                                 5. SELECCIÓN DE MES
                                 (Oculto inicialmente)
                            ========================================================== --}}
                            <div class="col-md-2 d-none" id="div_mes">

                                {{-- Etiqueta del mes --}}
                                <label class="form-label fw-bold text-dark">
                                    5. Mes
                                </label>

                                {{-- Select de meses --}}
                                <select 
                                    class="form-select form-select-lg border-2 shadow-sm" 
                                    name="mes" 
                                    id="mes_valor"
                                >

                                    {{-- Opción inicial --}}
                                    <option value="" selected disabled>
                                        Elija...
                                    </option>

                                    {{-- Lista de meses --}}
                                    @foreach([
                                        '01'=>'Enero',
                                        '02'=>'Febrero',
                                        '03'=>'Marzo',
                                        '04'=>'Abril',
                                        '05'=>'Mayo',
                                        '06'=>'Junio',
                                        '07'=>'Julio',
                                        '08'=>'Agosto',
                                        '09'=>'Septiembre',
                                        '10'=>'Octubre',
                                        '11'=>'Noviembre',
                                        '12'=>'Diciembre'
                                    ] as $val => $nombre)

                                        <option value="{{ $val }}">
                                            {{ $nombre }}
                                        </option>

                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- =========================================================
                             SEPARADOR VISUAL
                        ========================================================== --}}
                        <div class="text-center my-5">

                            {{-- Texto del separador --}}
                            <span 
                                class="px-3 bg-white text-muted small fw-bold text-uppercase" 
                                style="position: relative; z-index: 1;"
                            >
                                Seleccione Formato de Reporte
                            </span>

                            {{-- Línea horizontal --}}
                            <hr style="margin-top: -10px;">
                        </div>

                        {{-- =========================================================
                             BOTONES DE EXPORTACIÓN
                        ========================================================== --}}
                        <div class="row text-center mt-5">

                            {{-- Botón PDF --}}
                            <div class="col-md-6 mb-3">
                                <button 
                                    type="button" 
                                    onclick="generarReporte('pdf')" 
                                    class="btn btn-outline-primary w-100 p-4 border-2 btn-reporte-perm"
                                >

                                    {{-- Ícono --}}
                                    <i class="fas fa-file-pdf fa-4x mb-3"></i><br>

                                    {{-- Texto principal --}}
                                    <b>Descargar Historial PDF</b>

                                    {{-- Descripción --}}
                                    <p class="small mb-0">
                                        Resumen detallado de ausencias aprobadas.
                                    </p>
                                </button>
                            </div>

                            {{-- Botón Excel --}}
                            <div class="col-md-6 mb-3">
                                <button 
                                    type="button" 
                                    onclick="generarReporte('excel')" 
                                    class="btn btn-outline-success w-100 p-4 border-2 btn-reporte-excel"
                                >

                                    {{-- Ícono --}}
                                    <i class="fas fa-file-excel fa-4x mb-3"></i><br>

                                    {{-- Texto principal --}}
                                    <b>Descargar Listado Excel</b>

                                    {{-- Descripción --}}
                                    <p class="small mb-0">
                                        Exportación de datos para análisis.
                                    </p>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================
     ESTILOS PERSONALIZADOS
========================================================= --}}
<style>

    {{-- Estilo base de los botones --}}
    .btn-reporte-perm, 
    .btn-reporte-excel {
        cursor: pointer;
        transition: all 0.3s ease;
        border-style: dashed !important;
    }

    {{-- Colores del botón PDF --}}
    .btn-reporte-perm {
        border-color: #0b3d68 !important;
        color: #0b3d68;
    }

    {{-- Colores del botón Excel --}}
    .btn-reporte-excel {
        border-color: #198754 !important;
        color: #198754;
    }

    {{-- Efecto hover botón PDF --}}
    .btn-reporte-perm:hover {
        transform: translateY(-5px);
        border-style: solid !important;
        background-color: #0b3d68 !important;
        color: white !important;
    }

    {{-- Efecto hover botón Excel --}}
    .btn-reporte-excel:hover {
        transform: translateY(-5px);
        border-style: solid !important;
        background-color: #198754 !important;
        color: white !important;
    }
</style>

{{-- =========================================================
     SCRIPT JAVASCRIPT
========================================================= --}}
<script>

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN: filtrarEmpleados()
    |--------------------------------------------------------------------------
    | Filtra los empleados según el departamento seleccionado.
    | Muestra únicamente los colaboradores pertenecientes
    | al departamento elegido.
    |--------------------------------------------------------------------------
    */
    function filtrarEmpleados() {

        // Obtiene el departamento seleccionado
        const deptoId = document.getElementById('depto_filtro').value;

        // Select de empleados
        const selectEmp = document.getElementById('empleado_id');

        // Todas las opciones del select
        const opciones = selectEmp.querySelectorAll('option');

        // Reinicia la selección del empleado
        selectEmp.value = "";

        // Recorre cada opción
        opciones.forEach(opcion => {

            // Ignora la opción vacía inicial
            if (opcion.value === "") return;

            // Obtiene el departamento del empleado
            const deptoOpcion = opcion.getAttribute('data-depto');

            // Verifica si coincide con el filtro
            const coincide = (
                deptoId === "todos" || 
                deptoOpcion === deptoId
            );

            // Muestra u oculta la opción
            opcion.style.display = coincide ? 'block' : 'none';
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN: actualizarInterfaz()
    |--------------------------------------------------------------------------
    | Muestra u oculta los campos de año y mes
    | dependiendo del período seleccionado.
    |--------------------------------------------------------------------------
    */
    function actualizarInterfaz() {

        // Obtiene el valor del período
        const periodo = document.getElementById('periodo').value;

        // Div del año
        const divAnio = document.getElementById('div_anio');

        // Div del mes
        const divMes = document.getElementById('div_mes');

        /*
        |--------------------------------------------------------------------------
        | Mostrar u ocultar el año
        |--------------------------------------------------------------------------
        */
        if (periodo === 'anual' || periodo === 'mensual') {

            // Muestra el campo año
            divAnio.classList.remove('d-none');

        } else {

            // Oculta el campo año
            divAnio.classList.add('d-none');

            // Limpia el valor
            document.getElementById('anio_valor').value = "";
        }

        /*
        |--------------------------------------------------------------------------
        | Mostrar u ocultar el mes
        |--------------------------------------------------------------------------
        */
        if (periodo === 'mensual') {

            // Muestra el campo mes
            divMes.classList.remove('d-none');

        } else {

            // Oculta el campo mes
            divMes.classList.add('d-none');

            // Limpia el valor
            document.getElementById('mes_valor').value = "";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN: generarReporte(tipo)
    |--------------------------------------------------------------------------
    | Valida los filtros seleccionados,
    | verifica si existen registros
    | y genera el reporte PDF o Excel.
    |--------------------------------------------------------------------------
    */

function generarReporte(formato) {
        // Obtener valores de los campos
        const empleado = document.getElementById('empleado_id').value;
        const periodo  = document.getElementById('periodo').value;
        const anio     = document.getElementById('anio_valor').value;
        const mes      = document.getElementById('mes_valor').value;
        const tipo     = document.getElementById('tipo_solicitud').value;

        // Validaciones básicas
        if (!empleado) return Swal.fire('Atención', 'Seleccione un colaborador', 'warning');
        if (!periodo)  return Swal.fire('Atención', 'Seleccione el período', 'warning');
        if (!anio)     return Swal.fire('Atención', 'Seleccione el año', 'warning');

        // Construir URL de descarga
        // Asegúrate de que esta ruta coincida con la de tu archivo web.php
        let url = formato === 'pdf' ? "{{ route('informes.permisos.pdf') }}" : "{{ route('informes.permisos.excel') }}";
        
        // Agregar parámetros
        const params = new URLSearchParams({
            empleado_id: empleado,
            periodo: periodo,
            anio: anio,
            mes: mes,
            tipo_solicitud: tipo
        });

        // Abrir en nueva pestaña
        window.open(`${url}?${params.toString()}`, '_blank');
    }
</script>

@endsection {{-- Fin de la sección content --}}