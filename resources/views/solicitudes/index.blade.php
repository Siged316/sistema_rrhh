@extends('layouts.app')

@section('content')

<div class="container mt-4 mb-5">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">
                <i class="fas fa-file-invoice me-2 text-primary"></i>Historial de Solicitudes
            </h2>
            <p class="text-muted small mb-0">Gestión de permisos y formato oficial FT-GTH-001</p>
        </div>
    </div>

    {{-- FORMULARIO DE FILTRADO --}}
    <form action="{{ route('solicitudes.index') }}" method="GET" id="form-filtros-unico">
        <div class="card mb-4 border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    {{-- Buscar empleado --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">BUSCAR EMPLEADO</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Nombre..." value="{{ request('search') }}">
                        </div>
                    </div>

                    {{-- Filtrar por Mes --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">MES</label>
                        <select name="mes" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $index => $mes)
                                <option value="{{ $index + 1 }}" {{ request('mes') == ($index + 1) ? 'selected' : '' }}>
                                    {{ $mes }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Botones --}}
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">BUSCAR</button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('solicitudes.index') }}" class="btn btn-outline-secondary w-100 fw-bold">LIMPIAR</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- TABLA DE SOLICITUDES --}}
    <div class="card shadow-sm border-0 overflow-hidden" style="border-radius:15px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle shadow-sm mb-0">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-primary text-white">Empleado / Cargo</th>
                            <th class="bg-primary text-white">Tipo</th>
                            <th class="bg-primary text-white">Periodo</th>
                            <th class="bg-primary text-white">Estado</th>
                            <th class="bg-primary text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                       
                         @forelse(isset($solicitudes) ? $solicitudes : [] as $solicitud)
                        
                           @php
                           // 1. Inicialización y carga de objetos principales
                           $aprobaciones = $solicitud->aprobaciones ?? collect();
                           $estadoDB = strtolower($solicitud->estado);
                           $user = auth()->user();
    
                          // 2. Buscamos el depto
                           $deptoObj = \App\Models\Departamento::where('nombre', $solicitud->departamento)->first();
    
                          // 3. Definimos al Jefe del Departamento (Lo que faltaba)
                          $jefeDelDepto = $deptoObj ? \App\Models\Empleado::find($deptoObj->jefe_empleado_id) : null;
    
                          // 4. Lógica de quién es jefe (usando comparativa de correos)
                          $esSolicitudDeJefe = false;
                         if ($jefeDelDepto && !empty($solicitud->correo)) {
                              $esSolicitudDeJefe = (strtolower(trim($solicitud->correo)) == strtolower(trim($jefeDelDepto->email)));
                            }
    
                          // 5. Lógica para saber si el usuario logueado es el jefe
                          $esJefeDeEstaSol = false;
                          if ($deptoObj && isset($user->empleado)) {
                                $esJefeDeEstaSol = ($deptoObj->jefe_empleado_id == $user->empleado->id);
                            }

                           // 6. Variables de firma
                           $tieneFirmaJefe = $aprobaciones->where('paso_orden', 1)->isNotEmpty();
                           $tieneFirmaGTH = $aprobaciones->where('paso_orden', 2)->isNotEmpty();
                            $tieneFirmaDE = $aprobaciones->where('paso_orden', 3)->isNotEmpty();

                           // 7. Lógica de Colores
                           if ($estadoDB == 'rechazado') {
                              $color = 'danger'; $texto = 'RECHAZADO'; $icono = 'fa-times-circle';
                            } elseif ($tieneFirmaDE) {
                              $color = 'success'; $texto = 'APROBADO'; $icono = 'fa-check-double';
                            } elseif ($tieneFirmaGTH) {
                              $color = 'primary'; $texto = 'EN DIR. EJEC.'; $icono = 'fa-building';
                            } elseif ($tieneFirmaJefe || $estadoDB == 'en proceso') {
                               $color = 'info'; $texto = 'GTH'; $icono = 'fa-spinner fa-spin';
                            } else {
                              $color = 'warning'; $texto = 'JEFE'; $icono = 'fa-clock';
                            }

                           // 8. Lógica de Turnos
                          $esMiTurnoParaFirmar = false;
                          $rolNombre = auth()->user()->rol->nombre ?? 'Sin Rol';
                          $rolNombreLower = strtolower(trim($user->rol->nombre ?? ''));
                          $esDireccion = (str_contains($rolNombreLower, 'dirección') || str_contains($rolNombreLower, 'direccion') || $rolNombreLower === 'administrador');

                          if ($esSolicitudDeJefe) {
                             if ($esDireccion && !$tieneFirmaDE && $estadoDB !== 'rechazado') {
                                  $esMiTurnoParaFirmar = true;
                                }
                            } else {
                               if ($esJefeDeEstaSol && !$tieneFirmaJefe && $estadoDB !== 'rechazado') {
                                  $esMiTurnoParaFirmar = true;
                                } elseif ($rolNombreLower === 'gth' && $tieneFirmaJefe && !$tieneFirmaGTH && $estadoDB !== 'rechazado') {
                                  $esMiTurnoParaFirmar = true;
                                } elseif ($esDireccion && $tieneFirmaGTH && !$tieneFirmaDE && $estadoDB !== 'rechazado') {
                                  $esMiTurnoParaFirmar = true;
                                }
                            }
                           @endphp
                           <td class="ps-4"> 
                             <div class="fw-bold text-primary">
                                 {{-- Aquí accedes directamente al nombre que ya está en la tabla solicitudes --}} 
                                  {{ strtoupper($solicitud->nombre) }} 
                                </div> 
                                <div class="small text-muted"> 
                                   {{-- Si quieres mostrar el cargo, lo ideal es que esté en la tabla solicitudes --}} 
                                  {{-- Si no existe en solicitudes, muestra un valor por defecto --}} 
                                 <b>{{ strtoupper($solicitud->empleado_info->cargo ?? 'CARGO NO DEFINIDO') }}</b>
                              </div> 
                            </td>                   
            
                            {{-- Columna: Tipo --}}
                            <td class="text-center">
                                <span class="badge bg-white text-dark border shadow-sm">{{ strtoupper(str_replace('_',' ',$solicitud->tipo)) }}</span>
                            </td>

                            {{-- Columna: Periodo --}}
                            <td class="text-center">
                                <div class="small text-success"><b>INICIO:</b> {{ \Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d/m/Y') }}</div>
                                <div class="small text-danger"><b>FIN:</b> {{ \Carbon\Carbon::parse($solicitud->fecha_fin)->format('d/m/Y') }}</div>                                </td>

                                {{-- Columna: Estado visual --}}
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center" style="min-width: 150px;">
                                        <span class="badge bg-{{ $color }} mb-2 shadow-sm px-3 py-2" style="font-size: 0.75rem; min-width: 140px;">
                                            <i class="fas {{ $icono }} me-1"></i> {{ $texto }}
                                        </span>

                                        {{-- Línea de flujo visual corregida --}}
                                        <div class="d-flex align-items-center justify-content-center gap-2 mt-2">
                                            <div class="text-center">
                                                <div class="rounded-circle p-2 {{ $tieneFirmaJefe ? 'bg-success text-white' : 'bg-light text-muted' }}" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                    <i class="fas fa-user-tie small"></i>
                                                </div>
                                                <small style="font-size: 0.7rem; display: block; mt-1;">JEFE</small>
                                            </div>

                                            <div style="width:40px; height:2px; background:#ccc; margin-bottom: 15px;"></div>

                                            <div class="text-center">
                                                <div class="rounded-circle p-2 {{ $tieneFirmaGTH ? 'bg-success text-white' : 'bg-light text-muted' }}" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                    <i class="fas fa-users small"></i>
                                                </div>
                                                <small style="font-size: 0.7rem; display: block; mt-1;">GTH</small>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Columna: Acciones --}}
                                <td class="text-center">
                                    <div style="background: #fff3cd; padding: 10px; border: 1px solid #ffeeba; margin: 5px 0;">
   
                                    @if($esMiTurnoParaFirmar)
                                        <button type="button" class="btn btn-success btn-sm shadow-sm" onclick="verDetalles({{ $solicitud->id }})">
                                            <i class="fas fa-file-signature me-1"></i> <b>GESTIONAR</b>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="verDetalles({{ $solicitud->id }})">
                                            <i class="fas fa-eye me-1"></i> Ver
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No se encontraron registros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if(isset($solicitudes) && method_exists($solicitudes, 'links'))
    <div class="d-flex justify-content-center mt-4 custom-pagination">
        {{ $solicitudes->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
@endif
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE DETALLES --}} 
<div class="modal fade" id="verSolicitudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:15px;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Formato FT-GTH-001</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBodyContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p>Cargando información...</p>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    //Botón de ver
function verDetalles(id) {
    const content = document.getElementById('modalBodyContent');
    const modalElement = document.getElementById('verSolicitudModal');
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);

    // 1. Mostrar cargando
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p>Cargando información...</p>
        </div>
    `;
    modalInstance.show();

    // 2. Construir URL y realizar UNA SOLA petición
    const url = `${window.location.origin}/solicitudes/${id}`;
    
    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error ' + response.status);
        }
        return response.text(); // Recibimos el HTML como texto
    })
    .then(html => {
        content.innerHTML = html;
        
        // 3. Vincular botones de firma
        const signatureButtons = content.querySelectorAll('button[data-firma]');
        signatureButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                firmarModal(id, this.dataset.firma);
            });
        });
    })
    .catch(err => {
        console.error("Error detallado:", err);
        content.innerHTML = `<div class="alert alert-danger">Error al cargar datos: ${err.message}</div>`;
    });
}

//Firmas
window.procesarFirma = function(id, estado = 'aprobado', observaciones = '') {
  
    fetch(`/solicitudes/${id}/procesar`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            modo: 'accionar',
            estado: estado,
            observaciones: observaciones
        })
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {

           Swal.fire({
              icon: 'success',
              title: 'Éxito',
              text: data.message,
              timer: 2000,
             showConfirmButton: false
            }).then(() => {

              const jefe = document.querySelector('#area-firma-jefe');
               if (jefe && data.jefe_html) {
                  jefe.innerHTML = data.jefe_html;
                }

              const gth = document.querySelector('#area-firma-gth');
              if (gth && data.gth_html) {
                  gth.innerHTML = data.gth_html;
                }

              // Ocultar botones
              const contenedorBotones = document.querySelector('#contenedor-botones');
              if (contenedorBotones) {
                  contenedorBotones.style.display = 'none';
                }

   

            });

        } else {

            Swal.fire('Error', data.message, 'error');

        }

    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Ocurrió un error inesperado.', 'error');
    });

};

window.procesarSolicitud = function(id, estado) {
    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch(`/solicitudes/${id}/procesar`, {
        
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ estado: estado })
    })
    .then(res => res.json())
    .then(data => {
        console.log("Respuesta del servidor:", data);
        if (data.success) {
            // 1. Actualizamos el HTML SIN recargar la página
            const elJefe = document.getElementById('area-firma-jefe');
            const elGth = document.getElementById('area-firma-gth');
            
            if (elJefe) elJefe.innerHTML = data.jefe_html;
            if (elGth) elGth.innerHTML = data.gth_html;

            // 2. Ocultar botones de acción en el modal
            const contBotones = document.getElementById('contenedor-botones-' + id);
            if (contBotones) contBotones.style.display = 'none';

            // 3. Notificación (SweetAlert no cierra el modal por sí solo)
            Swal.fire({
                icon: 'success',
                title: '¡Firmado!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // 2. UNA VEZ EL MENSAJE SE CIERRA, ejecutamos la limpieza visual
                
                // Ocultar formulario de entrada
                document.getElementById('cuerpo-entrada-rechazo').style.display = 'none';
                document.getElementById('footer-entrada-rechazo').style.display = 'none';
                document.querySelector('#modalRechazo .modal-header').style.display = 'none';

                // Actualizar firmas
                if(data.jefe_html) document.getElementById('area-firma-jefe').innerHTML = data.jefe_html;
                if(data.gth_html) document.getElementById('area-firma-gth').innerHTML = data.gth_html;

                // Mostrar motivo debajo de las firmas
                const zonaRechazo = document.getElementById('zona-rechazo-debajo-firmas');
                if (zonaRechazo) {
                    zonaRechazo.style.display = 'block';
                    document.getElementById('texto-motivo-final').innerText = motivo;
                }
            });

          

        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
};

// Actualizar la pagina 
document.addEventListener('hidden.bs.modal', function (event) {
    // Verificamos si el elemento que se cerró es realmente un modal
    if (event.target.classList.contains('modal')) {
        console.log("Detectado cierre de modal, recargando...");
        window.location.href = window.location.href; 
    }
});


// Rechazar solicitud

window.confirmarRechazoIntegrado = function(id) {
    // Buscamos el textarea específico de esta solicitud
    const motivo = document.getElementById(`txtMotivoRechazo-${id}`).value;
    
    if (!motivo.trim()) return Swal.fire('Error', 'Escribe un motivo.', 'warning');

    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch(`/solicitudes/${id}/procesar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ estado: 'rechazado', observaciones: motivo })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return Swal.fire('Error', data.message, 'error');

        // Ocultar elementos de la solicitud específica
        document.getElementById(`zona-rechazo-${id}`).style.display = 'none';
        document.getElementById(`contenedor-botones-${id}`).style.display = 'none';

        // Mostrar resultado de la solicitud específica
        const zonaFinal = document.getElementById(`zona-rechazo-debajo-firmas-${id}`);
        const spanTexto = document.getElementById(`texto-motivo-final-${id}`);
        
        spanTexto.innerText = motivo;
        zonaFinal.style.display = 'block';

        Swal.fire({ icon: 'success', title: 'Rechazado', timer: 1500, showConfirmButton: false });
    });
};
</script>

@endsection