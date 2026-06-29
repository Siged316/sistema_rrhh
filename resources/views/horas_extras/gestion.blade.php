@extends('layouts.app')
<style>
    /* Aseguramos que el modal esté por encima de todo */
    .modal {
        z-index: 9999 !important;
    }
    .modal-backdrop {
        z-index: 9998 !important;
    }
    /* Esto evita que algo interno oculte el modal */
    body.modal-open {
        overflow: auto !important; 
    }
</style>

@section('content')

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        @if ($errors->any())
         <div class="alert alert-danger">
             <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                     <li>{{ $error }}</li>
                  @endforeach
             </ul>
         </div>
       @endif
        <h3 class="fw-bold">Gestión de Tiempo Compensatorio</h3>
        @if($esAdmin || $esGTH )
        <a href="{{ route('configuracion.firmas') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-gear me-1"></i> Configurar Flujo de Firmas
        </a>
        @endif
       
        {{-- Intenta con esta línea, es más probable que funcione --}}
       @if($esAdmin || $esGTH || $esJefe)
         <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAcumular">
              <i class="fa-solid fa-plus me-1"></i> Cargar Horas
          </button>
        @endif
    </div>

     {{-- BLOQUE DE MENSAJES --}}
   @if(session('success') && !request()->is('horas-extras*')) 
     <div class="alert alert-success">
         {{ session('success') }}
      </div>
    @endif

  {{-- BUSCADOR PARA ADMIN, GTH, DIRECCIÓN Y JEFES --}}
  @if($esAdmin || $esGTH || $esDireccion || $esJefe)
       <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-primary text-white fw-bold">
              <i class="fas fa-search me-2"></i> CONSULTAR SALDOS POR COLABORADOR
          </div>
           <div class="card-body bg-light">
               <form action="{{ url()->current() }}" method="GET" id="formConsulta" autocomplete="off">
                  <div class="row g-3">
                       <div class="col-md-4">
                          <label class="form-label small fw-bold text-uppercase">1. Departamento</label>
                          {{-- AÑADIDO: name="departamento_id" --}}
                    
                          <select id="select_depto" name="departamento_id" class="form-select border-primary">
                              <option value="">-- Seleccione un depto --</option>
                                @foreach($departamentos as $depto)
                                  <option value="{{ $depto->id }}" {{ request('departamento_id') == $depto->id ? 'selected' : '' }}>
                                     {{ $depto->nombre }}
                                  </option>
                                @endforeach
                          </select>
                       </div>

                        <div class="col-md-5">
                          <label class="form-label small fw-bold text-uppercase">2. Colaborador</label>
                          <select name="empleado_id" id="select_empleado" class="form-select border-primary" required>
                              <option value="">-- Seleccione Colaborador --</option>
                              {{-- Aquí tu JS debe llenar las opciones --}}
                          </select>
                       </div>



                       <div class="col-md-3 d-flex align-items-end">
                          <button type="submit" class="btn btn-primary w-100 fw-bold">
                             <i class="fas fa-sync-alt me-1"></i> CONSULTAR
                          </button>
                      </div>
                  </div>
              </form>
         </div>
       </div>
    @endif

    {{-- BARRA DE INFORMACIÓN (Solo si hay búsqueda activa) --}}
    @if($esBusquedaActiva && $empleadoAConsultar)
        <div class="alert alert-info d-flex justify-content-between align-items-center shadow-sm mb-4 border-0" style="border-left: 5px solid #0dcaf0;">
            <span><i class="fas fa-user-circle me-2"></i> Información de: <strong>{{ strtoupper($empleadoAConsultar->nombre . ' ' . $empleadoAConsultar->apellido) }}</strong></span>
            <span class="badge bg-dark px-3 py-2 text-uppercase">{{ $empleadoAConsultar->departamento->nombre ?? 'N/A' }}</span>
        </div>
    @endif

   {{-- INDICADORES --}}
 
   @if($empleadoAConsultar)
    <div class="row g-3 mb-4">
        {{-- TARJETA ACUMULADAS --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark text-white h-100" role="button" data-bs-toggle="modal" data-bs-target="#modalAcumuladas" style="cursor: pointer;">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <small class="text-uppercase fw-bold text-info mb-1">Acumuladas</small>
                    <h3 class="fw-bold mb-0 text-info">{{ number_format($totalAcumuladas, 2) }} h</h3>
                </div>
                <div style="height: 5px; background-color: #0dcaf0; border-radius: 0 0 5px 5px;"></div>
            </div>
        </div>

        {{-- TARJETA PAGADAS --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-secondary text-white h-100 opacity-75" role="button" data-bs-toggle="modal" data-bs-target="#modalPagadas" style="cursor: pointer;">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <small class="text-uppercase fw-bold text-light mb-1">Pagadas</small>
                    <h3 class="fw-bold mb-0 text-light">{{ number_format($totalPagadas, 2) }} h</h3>
                </div>
                <div style="height: 5px; background-color: #adb5bd; border-radius: 0 0 5px 5px;"></div>
            </div>
        </div>

        {{-- TARJETA CONSUMIDAS --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark text-white h-100" role="button" data-bs-toggle="modal" data-bs-target="#modalConsumidas" style="cursor: pointer;">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <small class="text-uppercase fw-bold text-warning mb-1">Consumidas</small>
                    <h3 class="fw-bold mb-0 text-warning">{{ number_format($totalConsumidas, 2) }} h</h3>
                </div>
                <div style="height: 5px; background-color: #ffc107; border-radius: 0 0 5px 5px;"></div>
            </div>
        </div>

        {{-- TARJETA SALDO DE TIEMPO --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark text-white h-100" role="button" data-bs-toggle="modal" data-bs-target="#modalPendientes" style="cursor: pointer;">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <small class="text-uppercase fw-bold text-danger mb-1">Saldo de Tiempo</small>
                    <h3 class="fw-bold mb-0 text-danger">{{ number_format($saldoRestante, 2) }} h</h3>
                </div>
                <div style="height: 5px; background-color: #dc3545; border-radius: 0 0 5px 5px;"></div>
            </div>
        </div>
    </div> <div class="mb-4 text-end">
        <button class="btn btn-dark btn-sm rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalDetalleSaldo">
            <i class="fas fa-list-ul me-2"></i> Ver Desglose
        </button>
    </div>
   @endif

    {{-- TABLA --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table align-middle tabla-personalizada">
               <thead style="background-color: #1e40af; color: white;"> 
                   <tr>
                      <th style="padding: 12px; width: 320px; vertical-align: middle;">
                         <div class="d-flex flex-column align-items-center">
                               <span class="fw-bold text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 1px;">Colaborador</span>
        
                              <div class="d-flex align-items-center justify-content-center w-100">
                                  <button type="button" id="btn-activar-busqueda" class="btn btn-sm btn-light border-0 shadow-sm" style="border-radius: 50%; width: 32px; height: 32px;">
                                     <i class="fas fa-search text-primary"></i>
                                   </button>

                                  <form action="{{ route('horas_extras.gestion') }}" method="GET" id="form-busqueda" class="ms-2 d-none animacion-fade" style="flex-grow: 1;">
                                      <div class="input-group input-group-sm">
                                          <input type="text" 
                                           name="buscar" 
                                           class="form-control border-0 shadow-none" 
                                           placeholder="Escribe el nombre..." 
                                          value="{{ request('buscar') }}"
                                          style="border-radius: 4px 0 0 4px; height: 32px;">
                                          <button class="btn btn-light btn-sm border-0" type="submit" style="border-radius: 0 4px 4px 0;">
                                              <i class="fas fa-chevron-right text-primary"></i>
                                           </button>
                                       </div>
                                   </form>
                              </div>
                           </div>
                       </th>
                       <th class="text-center" style="vertical-align: middle; font-size: 0.85rem;">RUTA DE FIRMAS</th>
                       <th class="text-center" style="vertical-align: middle; font-size: 0.85rem;">ACCIONES</th>
                   </tr>
                </thead>
                <tbody>
                    @forelse($solicitudes as $solicitud)
                        <tr>
                            <td class="ps-4">
                                <strong>{{ strtoupper(($solicitud->empleado->nombre ?? 'N/A') . ' ' . ($solicitud->empleado->apellido ?? '')) }}</strong>
                                <br><small class="text-muted">{{ $solicitud->horas_trabajadas }} hrs - {{ $solicitud->created_at->format('d/m/Y') }}</small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                  @foreach($pasosConfigurados as $paso)
                                     @php
                                         $pasoActualSoli = intval($solicitud->paso_actual ?? 0); 
        
                                         // Determinamos los estados
                                          $esRechazado = ($solicitud->estado == 'rechazado');
                                           $esCompletado = ($solicitud->estado == 'aprobado' || ($pasoActualSoli > $loop->index && !$esRechazado));
                                           $esActual = (!$esCompletado && !$esRechazado && $pasoActualSoli == $loop->index);
        
                                           // Lógica de colores e iconos
                                           if ($esRechazado && $pasoActualSoli == $loop->index) {
                                              $bgColor = '#dc3545'; // Rojo para el paso donde se rechazó
                                              $icon = 'fa-times';
                                            } elseif ($esCompletado) {
                                              $bgColor = '#198754'; // Verde
                                              $icon = 'fa-check';
                                            } elseif ($esActual) {
                                              $bgColor = '#ffc107'; // Amarillo
                                              $icon = 'fa-pen-nib';
                                            } else {
                                              $bgColor = '#e9ecef'; // Gris
                                              $icon = 'fa-lock';
                                           }
                                        @endphp

                                      <div class="text-center" style="width: 60px;" title="{{ $esRechazado && $pasoActualSoli == $loop->index ? 'RECHAZADO' : $paso->nombre_paso }}">
                                         <div class="rounded-circle shadow-sm d-flex align-items-center justify-content-center mx-auto mb-1 border" 
                                             style="width: 30px; height: 30px; background-color: {{ $bgColor }}; color: {{ $esCompletado || $esActual || $esRechazado ? 'white' : '#6c757d' }};">
                                             <i class="fa-solid {{ $icon }}" style="font-size: 0.7rem;"></i>
                                          </div>
                                          <span style="font-size: 0.6rem;" class="text-uppercase fw-bold {{ $esRechazado ? 'text-danger' : 'text-muted' }} d-block">
                                              {{ $esRechazado && $pasoActualSoli == $loop->index ? 'RECHAZADO' : $paso->nombre_corto }}
                                           </span>
                                       </div>
    
                                       @if(!$loop->last) <i class="fa-solid fa-chevron-right small opacity-25"></i> @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-{{ $solicitud->id }}">
                                    <i class="fa fa-pen-fancy"></i> Revisar
                                </button>
                                @include('horas_extras.modal_revisar', ['solicitud' => $solicitud])
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-5 text-muted">No hay registros para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
            
         <div class="d-flex justify-content-center mt-4 custom-pagination">
               {{ $solicitudes->links('pagination::bootstrap-5') }}
           </div>
       </div>
    </div>
</div>


{{-- MODAL DESGLOSE PARA IMPRESIÓN  --}}
@if($empleadoAConsultar)
<div class="modal fade" id="modalDetalleSaldo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white">
                <h5 class="modal-title"><i class="fas fa-calculator me-2"></i> Detalle de Tiempos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="areaImpresion">
                <div class="p-4 text-center d-none d-print-block">
                    <h4 class="fw-bold mb-0">INSTITUTO HONDUREÑO DE CULTURA INTERAMERICANA</h4>
                    <p class="text-muted">Reporte de Tiempo Compensatorio</p>
                    <hr>
                    <h5 class="text-uppercase fw-bold">{{ $empleadoAConsultar->nombre }} {{ $empleadoAConsultar->apellido }}</h5>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="fw-bold text-primary">Total Horas Acumuladas</span>
                        <span class="badge bg-primary rounded-pill">+ {{ number_format($totalAcumuladas, 2) }} h</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="fw-bold text-success">Horas Pagadas en Efectivo</span>
                            <br><small class="text-muted">Compensación monetaria realizada</small>
                        </div>
                        <span class="badge bg-success rounded-pill">{{ number_format($totalPagadas, 2) }} h</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="fw-bold text-warning">Horas Consumidas (Descanso)</span>
                        <span class="badge bg-warning text-dark rounded-pill">- {{ number_format($totalConsumidas, 2) }} h</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-4 bg-light">
                   <div>
                     @if($saldoRestante >= 0)
                         <span class="fw-bold fs-5 text-success">Saldo Disponible Neto</span>
                         <br><small class="text-muted">Horas acumuladas.</small>
                       @else
                         <span class="fw-bold fs-5 text-danger">Horas a Deber </span>
                         <br><br>
                         <small class="text-muted">El empleado ha consumido más tiempo del acumulado.</small>
                       @endif
                    </div>
    
                   <!-- El badge cambia de color dinámicamente según el valor -->
                   <span class="fw-bold fs-4 badge {{ $saldoRestante >= 0 ? 'bg-success' : 'bg-danger' }} rounded-pill p-2 px-3">
                     {{ number_format($saldoRestante, 2) }} h
                  </span>
                </div>
                
                <div class="p-3">
                    <div class="alert alert-warning border-0 mb-0 small shadow-sm">
                        <i class="fas fa-exclamation-circle me-2"></i> 
                        Hay <strong>{{ number_format($totalPendientesSolicitud, 2) }} horas</strong> en solicitudes pendientes.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3">
               <button type="button" class="btn btn-primary" onclick="window.print()">
                 <i class="fas fa-print me-2"></i> IMPRIMIR
               </button>
            </div>
        </div>
    </div>
</div>
@endif


{{-- Crear Horas extra --}}
@include('horas_extras.horas')

<script>

document.addEventListener('DOMContentLoaded', function() {
    $(document).ready(function() {
   
      $('#select_depto').select2({ 
          placeholder: "Seleccione...", 
          allowClear: true, 
           width: '100%', 
           minimumResultsForSearch: 0 
        });

        $('#select_empleado').select2({ 
          placeholder: "Escribe para buscar...", 
          allowClear: true, 
           width: '100%', 
           minimumResultsForSearch: 0 
        });
    });
    // 1. Auto-cerrar alertas
    document.querySelectorAll('.auto-close').forEach(alert => {
        setTimeout(() => { new bootstrap.Alert(alert).close(); }, 5000);
    });  

    // 2. Lógica de Selectores Dinámicos
    const deptoSelect = document.getElementById('select_depto');
    const empleadoSelect = document.getElementById('select_empleado');
    const data = @json($departamentos);
    const empleadoSeleccionadoId = "{{ request('empleado_id') }}";
    const urlParams = new URLSearchParams(window.location.search);
    //Ocultar el buscador
    const btnActivar = document.getElementById('btn-activar-busqueda');
    const formBusqueda = document.getElementById('form-busqueda');

  

    // Si el usuario recargó y el valor persiste, vuelve a cargar los empleados de inmediato
    if (deptoSelect.value) {
     cargarEmpleados(deptoSelect.value, "{{ request('empleado_id') }}");
    }

    // SI EXISTE empleado_id EN LA URL, procesamos la carga y LUEGO LIMPIAMOS
   if (urlParams.has('empleado_id')) {
     const deptoId = urlParams.get('departamento_id');
     const empId = urlParams.get('empleado_id');

     // Cargamos los datos
     if (deptoId && empId) {
         cargarEmpleados(deptoId, empId);
       }
    
    }
    
   function cargarEmpleados(deptoId, seleccionarId = null) {
      const $selectEmp = $('#select_empleado');
    
      // 1. Limpiamos las opciones usando jQuery
       // MANTENEMOS la primera opción (la vacía) y borramos solo el resto
      $selectEmp.find('option:not(:first)').remove();
    
       if (deptoId) {
          const depto = data.find(d => d.id == deptoId);
           if (depto && depto.empleados) {
              depto.empleados.forEach(emp => {
                  const nombre = (emp.nombre + ' ' + (emp.apellido || '')).toUpperCase();
                  const newOption = new Option(nombre, emp.id, false, (seleccionarId == emp.id));
                  $selectEmp.append(newOption);
               });
            }
        }
    
       // 2. ¡ESTO ES LO MÁS IMPORTANTE!
      // Esta línea obliga a Select2 a reconstruir su buscador interno con los nuevos datos
      $selectEmp.trigger('change.select2');
    }

   if (deptoSelect) {
        // Usamos jQuery para escuchar el cambio, así es más fácil disparar el trigger
        $('#select_depto').on('change', function() {
            const $empSelect = $('#select_empleado');
            
            if (!this.value) {
                // Limpiamos usando jQuery y disparando el trigger
                $empSelect.empty().append('<option value="">-- Seleccione Colaborador --</option>');
                $empSelect.trigger('change.select2'); 
                return;
            }
            
            // Cargamos los empleados (que ya tiene el trigger dentro)
            cargarEmpleados(this.value);
        });

        // Carga inicial
        if (deptoSelect.value && empleadoSeleccionadoId) {
            cargarEmpleados(deptoSelect.value, empleadoSeleccionadoId);
        }
    }

    function imprimirReporte() {
     // Esta función abre el diálogo de impresión del navegador
     window.print();
    }

    //para el filtro del buscador en colaborador
    // Si ya existe una búsqueda activa, mostrar el input de inmediato
    if ("{{ request('buscar') }}" !== "") {
        formBusqueda.classList.remove('d-none');
        btnActivar.classList.add('d-none');
    }

    btnActivar.addEventListener('click', function() {
        formBusqueda.classList.remove('d-none'); // Muestra el input
        btnActivar.classList.add('d-none');      // Esconde el botón de lupa solo
        formBusqueda.querySelector('input').focus(); // Pone el cursor dentro
    });

});

function abrirModalManual() {
    var myModal = new bootstrap.Modal(document.getElementById('modalAcumuladas'));
    myModal.show();
}

//Refrescar pantalla 
if (window.location.search.length > 0) {
    window.history.replaceState(
        {},
        document.title,
        window.location.pathname
    );
}



</script>

@endsection


{{-- MODAL ACUMULADAS --}}
<div class="modal fade" id="modalAcumuladas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Detalle de Horas Acumuladas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover">
                    <thead><tr><th>Fecha de Aprobación</th><th>Observación</th><th>Horas</th></tr></thead>
                    <tbody>
                      @foreach($historialAcumuladas as $item)
                          <tr>
                              <td>
                                 {{ $item->fecha_aprobacion ? \Carbon\Carbon::parse($item->fecha_aprobacion)->format('d/m/Y') : 'Sin aprobación' }}
                               </td>
                             
                               <td>
                                    <div class="row">

                                     {{-- OBSERVACIONES --}}
                                     <div class="col-md-5 border-end">
                                         <small class="fw-bold text-muted d-block">Observaciones</small>
                                           {{ $item->observaciones_jefe ?? 'N/A' }}
                                       </div>

                                      {{-- ACTIVIDADES --}}
                                      <div class="col-md-7">
                                         <small class="fw-bold text-muted d-block">Actividades</small>

                                           @if($item->detalles->count())

                                              @foreach($item->detalles as $detalle)

                                                  @for($i = 1; $i <= 5; $i++)
                                                       @php
                                                          $actividad = $detalle->{"actividad$i"};
                                                          $fecha = $detalle->{"fecha$i"};
                                                        @endphp

                                                        @if($actividad)
                                                          <div class="small">
                                                                •
                                                              {{ $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : 'Sin fecha' }}
                                                               |
                                                              {{ $detalle->{"hora_inicio$i"} }} {{ $detalle->{"periodo_inicio$i"} }}
                                                               -
                                                              {{ $detalle->{"hora_fin$i"} }} {{ $detalle->{"periodo_fin$i"} }}
                                                               → {{ $actividad }}
                                                          </div>
                                                        @endif
                                                   @endfor

                                                @endforeach

                                            @else
                                              <small class="text-muted">Sin actividades</small>
                                           @endif
                                       </div>

                                    </div>
                                </td>

                                <td class="text-end fw-bold">
                                   +{{ number_format($item->horas_acumuladas, 2) }}
                               </td>
                           </tr>

                        @endforeach
                   </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PAGADAS --}}
<div class="modal fade" id="modalPagadas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Detalle de Horas Pagadas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover">
                    <thead><tr><th>Fecha de Aprobación</th><th>Observación</th><th>Horas</th></tr></thead>
                    <tbody>
                        @foreach($historialPagadas as $item)
                        <tr>
                            <td>{{ $item->fecha_aprobacion ? \Carbon\Carbon::parse($item->fecha_aprobacion)->format('d/m/Y') : 'Sin aprobación' }}</td>
                            <td>{{ $item->observaciones_jefe ?? 'N/A' }}</td>
                            <td class="text-end fw-bold">{{ number_format($item->horas_pagadas, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL CONSUMIDAS --}}
<div class="modal fade" id="modalConsumidas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Detalle de Horas Consumidas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha aprobación</th>
                            <th>Fecha inicio</th>
                            <th>Fecha fin</th>
                            <th>Motivo</th>
                            <th class="text-end">Horas</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($historialConsumidas as $item)
                        <tr>

                            <td>
                                {{ $item->fecha_aprobacion
                                    ? \Carbon\Carbon::parse($item->fecha_aprobacion)->format('d/m/Y')
                                    : 'Sin aprobación'
                                }}
                            </td>

                            <td>
                                {{ $item->fecha_inicio
                                    ? \Carbon\Carbon::parse($item->fecha_inicio)->format('d/m/Y')
                                    : 'N/A'
                                }}
                            </td>

                            <td>
                                {{ $item->fecha_fin
                                    ? \Carbon\Carbon::parse($item->fecha_fin)->format('d/m/Y')
                                    : 'N/A'
                                }}
                            </td>

                            <td>{{ $item->motivo ?? 'Compensatorio' }}</td>

                            <td class="text-end fw-bold">
                                {{ number_format((($item->dias * 8) + $item->horas), 2) }}
                            </td>

                        </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>

        </div>
    </div>
</div>

{{-- MODAL PENDIENTES --}}
@php
    $saldo = $totalAcumuladas - $totalConsumidas;
@endphp

<div class="modal fade" id="modalPendientes" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Estado de Horas (Detalle Completo)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                {{-- ================= RESUMEN ================= --}}
                <div class="mb-4">

                    <div class="d-flex justify-content-between">
                        <span>Horas acumuladas</span>
                        <strong>{{ number_format($totalAcumuladas, 2) }} h</strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Horas consumidas</span>
                        <strong>-{{ number_format($totalConsumidas, 2) }} h</strong>
                    </div>

                    <br>

                   <div class="d-flex justify-content-between fs-5">
                      <span class="fw-bold">
                          {{ $saldo >= 0 ? 'Saldo disponible' : 'Saldo a deber' }}
                        </span>

                       <strong class="{{ $saldo >= 0 ? 'text-success' : 'text-danger' }}">
                          {{ number_format($saldo, 2) }} h
                       </strong>
                   </div>

                </div>

                {{-- ================= ACUMULADAS ================= --}}
                <h6 class="fw-bold">Detalle de horas acumuladas</h6>

                @foreach($historialAcumuladas as $item)
                    <div class="border rounded p-3 mb-3">

                        <div class="d-flex justify-content-between">
                            <span>Fecha registro:</span>
                            <strong>{{ $item->created_at->format('d/m/Y') }}</strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>Observación:</span>
                            <span>{{ $item->observaciones_jefe ?? 'Sin observación' }}</span>
                        </div>

                        {{-- ACTIVIDADES --}}
                        <div class="mt-2">
                            <small class="fw-bold">Actividades realizadas:</small>

                            @foreach($item->detalles as $detalle)
                                @for($i = 1; $i <= 5; $i++)
                                    @php
                                        $actividad = $detalle->{"actividad$i"};
                                    @endphp

                                    @if($actividad)
                                        <div class="small text-muted">
                                            • {{ $detalle->{"fecha$i"} }} |
                                            {{ $detalle->{"hora_inicio$i"} }} {{ $detalle->{"periodo_inicio$i"} }}
                                            -
                                            {{ $detalle->{"hora_fin$i"} }} {{ $detalle->{"periodo_fin$i"} }}
                                            → {{ $actividad }}
                                        </div>
                                    @endif
                                @endfor
                            @endforeach
                        </div>

                        <div class="text-end fw-bold text-success mt-2">
                            +{{ number_format($item->horas_acumuladas, 2) }} h
                        </div>

                    </div>
                @endforeach

                {{-- ================= CONSUMIDAS ================= --}}
                <h6 class="fw-bold mt-4">Detalle de horas consumidas</h6>

                @foreach($historialConsumidas as $item)
                    <div class="border rounded p-3 mb-3">

                        <div class="d-flex justify-content-between">
                            <span>Fecha aprobación:</span>
                            <strong>
                                {{ $item->fecha_aprobacion
                                    ? \Carbon\Carbon::parse($item->fecha_aprobacion)->format('d/m/Y')
                                    : 'N/A'
                                }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>Rango de fechas:</span>
                            <span>
                                {{ $item->fecha_inicio ? \Carbon\Carbon::parse($item->fecha_inicio)->format('d/m/Y') : 'N/A' }}
                                →
                                {{ $item->fecha_fin ? \Carbon\Carbon::parse($item->fecha_fin)->format('d/m/Y') : 'N/A' }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>Motivo:</span>
                            <span>{{ $item->motivo ?? 'Compensatorio' }}</span>
                        </div>

                        <div class="text-end fw-bold text-danger mt-2">
                            -{{ number_format((($item->dias * 8) + $item->horas), 2) }} h
                        </div>

                    </div>
                @endforeach

            </div>

        </div>
    </div>
</div>