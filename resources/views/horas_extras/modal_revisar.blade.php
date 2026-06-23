<!-- Modal principal para la solicitud específica -->
<div class="modal fade" id="modal-{{ $solicitud->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:15px;">

            <!-- Cabecera del modal -->
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    Revisión Formato FT-GTH-002 - Solicitud #{{ $solicitud->id }}
                </h5>
                <!-- Botón para cerrar el modal -->
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Cuerpo del modal -->
            <div class="modal-body bg-light p-4">
                <!-- Área principal que se puede imprimir -->
                <div id="area-impresion-final-{{ $solicitud->id }}" class="bg-white shadow-sm p-5 mx-auto" 
                     style="max-width:950px; color:#000; border:1px solid #dee2e6;">

                    {{-- CABECERA DEL FORMATO --}}
                  <table style="width:100%; border-collapse:collapse; margin-bottom:25px; border:1.5px solid black; table-layout: fixed;">
                      <tr>
                          <td rowspan="4" style="width:20%; border:1px solid black; text-align:center; padding:10px;">
                             <img src="{{ asset('images/ihci_logo.jpg') }}" style="max-height:55px;">
                          </td>
        
                          <td rowspan="2" style="width:50%; border:1px solid black; text-align:center; font-weight:bold; font-size:12px;">FORMATO</td>
        
                            <td style="width:15%; border:1px solid black; text-align:center; font-weight:bold; font-size:10px;">CÓDIGO</td>
                           <td style="width:15%; border:1px solid black; text-align:center; font-size:11px;">FT-GTH-002</td>
                      </tr>
                    
                      <tr>
                          <td style="border:1px solid black; text-align:center; font-weight:bold; font-size:10px;">VERSIÓN</td>
                          <td style="border:1px solid black; text-align:center; font-size:11px;">1.1</td>
                      </tr>
                    
                      <tr>
                         <td rowspan="2" style="border:1px solid black; text-align:center; font-weight:bold; padding:5px; font-size:12px;">
                             SOLICITUD DE AUTORIZACIÓN DE TIEMPO COMPENSADO
                         </td>
                          <td style="border:1px solid black; text-align:center; font-weight:bold; font-size:10px;">VIGENTE DESDE</td>
                          <td style="border:1px solid black; text-align:center; font-size:11px;">28/3/2023</td>
                      </tr>
  
                      <tr>
                         <td colspan="2" style="border:1px solid black; text-align:center; font-weight:bold; font-size:10px;">PÁGINA 1 DE 1</td>
                       </tr>
                   </table>
                    {{-- DATOS GENERALES DE LA SOLICITUD --}}
                    <div class="row mb-3" style="font-size:12px;">
                        <div class="row mb-3" style="font-size:12px; font-family: Arial, sans-serif; color: #333;">
                            
                            <!-- Lugar y fecha de la solicitud -->
                            <div class="col-12 mb-2">
                                <div style="background-color: #eeeeee; padding: 8px; border: 1px solid #ccc; border-radius: 2px;">
                                    <strong>LUGAR Y FECHA:</strong> 
                                    {{ strtoupper($solicitud->lugar) }}, {{ $solicitud->created_at->format('d/m/Y') }}
                                </div>
                            </div>

                            <!-- Jefe a quien se solicita autorización -->
                            <div class="col-12 mb-2">
                                <div style="background-color: #eeeeee; padding: 8px; border: 1px solid #ccc; border-radius: 2px;">
                                    <strong>SOLICITADO A:</strong> 
                                    @php
                                        $nombreJefe = 'NO ASIGNADO';
                                        if ($solicitud->empleado && $solicitud->empleado->departamento) {
                                            $jefe = \App\Models\Empleado::find($solicitud->empleado->departamento->jefe_empleado_id);
                                            $nombreJefe = $jefe ? ($jefe->nombre . ' ' . $jefe->apellido) : 'JEFE DE DEPARTAMENTO';
                                        }
                                    @endphp
                                    {{ strtoupper($nombreJefe) }}
                                </div>
                            </div>

                            <!-- Solicitante y cargo -->
                            <div class="col-12 d-flex gap-2">
                                <div style="background-color: #eeeeee; padding: 8px; border: 1px solid #ccc; border-radius: 2px; flex: 2;">
                                    <strong>SOLICITADO POR:</strong> 
                                   
                                    {{-- Si hay empleado muestra nombre y apellido; si no, el texto plano de la BD --}}
                                    {{ 
                                      $solicitud->empleado 
                                      ? strtoupper($solicitud->empleado->nombre . ' ' . $solicitud->empleado->apellido) 
                                      : strtoupper($solicitud->nombre ?? 'Sin Identificar') 
                                    }}

                                </div>

                                <div style="background-color: #eeeeee; padding: 8px; border: 1px solid #ccc; border-radius: 2px; flex: 1;">
                                    <strong>CARGO:</strong> 
                                    {{ strtoupper($solicitud->cargo_solicitante ?? ($solicitud->empleado?->cargo ?? 'CARGO NO DEFINIDO')) }}
                                </div>
                            </div>

                            <!-- Cálculo de horas totales solicitadas -->
                            <div class="row mt-3 mb-2" style="font-size: 13px; font-family: Arial, sans-serif;">
                               @php 
                                 $totalMinutosReloj = 0; 
                                 foreach($solicitud->detalles as $det) {
                                      for($i = 1; $i <= 10; $i++) {
                                           $campoAct = "actividad{$i}";
                                            $campoIni = "hora_inicio".$i;
                                            $campoFin = "hora_fin".$i;

                                            // Primero verificamos que los campos no estén vacíos
                                           if(!empty($det->$campoAct) && !empty($det->$campoIni) && !empty($det->$campoFin)) {
                
                                                // 1. DEFINIR las variables PRIMERO
                                               $h_ini = \Carbon\Carbon::parse($det->$campoIni);
                                               $h_fin = \Carbon\Carbon::parse($det->$campoFin);

                                                // 2. Ahora que existen, aplicamos la lógica de 7 a 4 (9 horas)
                                               if ($h_fin->lt($h_ini)) {
                                                 $h_fin->addHours(12); // Si entró a las 7 y salió a las 4, esto lo vuelve 16:00
                                                }

                                               $totalMinutosReloj += $h_ini->diffInMinutes($h_fin);
                                            }
                                       }
                                    }

                                    $horasTotales = floor($totalMinutosReloj / 60);
                                    $minutosTotales = $totalMinutosReloj % 60;

                                    // Formateamos el resultado final para la vista
                                    $totalFinalExito = $horasTotales . ":" . str_pad($minutosTotales, 2, "0", STR_PAD_LEFT);
                                @endphp
                                
                                <div class="col-12 d-flex align-items-center">
                                    <span>Por este medio solicito me autorice:</span>
                                    <div class="mx-3 text-center" style="background-color: #dddddd; width: 100px; padding: 5px; border: 1px solid #ccc; font-weight: bold; font-size: 14px;">
                                        {{ str_replace('.', ':', $totalFinalExito) }}
                                    </div>
                                    <span><u>horas</u> a cuenta de tiempo compensatorio.</span>
                                </div>
                            </div>

                            <!-- Detalle de actividades -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <strong style="font-size: 13px;">Detalle de actividades realizadas a cuenta de tiempo compensatorios:</strong>
                                </div>
                            </div>
                        </div>

                        {{-- TABLA CON DETALLE DE ACTIVIDADES --}}
                        
                        <div class="row mt-3 mb-2" style="font-size: 13px; font-family: Arial, sans-serif;">
                            
                           <table class="table table-bordered border-dark text-center small mb-2">
                                <thead>
                                    <tr style="font-size:10px;">
                                        <th>FECHA</th>
                                        <th>ACTIVIDAD REALIZADA</th>
                                        <th>INICIO</th>
                                        <th>FIN</th>
                                        <th>TOTAL HRS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($solicitud->detalles as $det)
                                        @for($i = 1; $i <= 10; $i++)
                                            @php 
                                                $act = "actividad{$i}";
                                                $ini = "hora_inicio{$i}";
                                                $fin = "hora_fin{$i}";
                                            @endphp
                                            @if(!empty($det->$act))
                                            @php
                                               $h_ini = \Carbon\Carbon::parse($det->$ini);
                                               $h_fin = \Carbon\Carbon::parse($det->$fin);

                                                // Si la hora final es menor (ej. 4 es menor que 7), ajustamos a PM
                                                if ($h_fin->lt($h_ini)) {
                                                 $h_fin->addHours(12); 
                                                }

                                                $minutosFila = $h_ini->diffInMinutes($h_fin);
                                                $hF = floor($minutosFila / 60);
                                                $mF = $minutosFila % 60;
                                                $resultadoFila = $hF . ":" . str_pad($mF, 2, "0", STR_PAD_LEFT);
                                            @endphp
                                                <tr style="font-size:11px;">
                                                    <td>{{ \Carbon\Carbon::parse($det->{"fecha".$i})->format('d/m/Y') }}</td>
                                                    <td class="text-start">{{ strtoupper($det->$act) }}</td>
                                                    <td>{{ $h_ini->format('H:i') }}</td>
                                                    <td>{{ $h_fin->format('H:i') }}</td>
                                                    <td class="fw-bold">{{ $resultadoFila }}</td>
                                                </tr>
                                            @endif
                                        @endfor
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Total de horas al final de la tabla -->
                            <div class="d-flex justify-content-end" style="margin-top: 5px; padding-right: 50px;">
                                <div style="width: 250px; display: flex; justify-content: flex-end; align-items: center;">
                                    <span style="font-size: 11px; font-weight: bold; margin-right: 40px;">TOTAL HORAS:</span>
                                    <span style="font-size: 11px; font-weight: bold; width: 60px; text-align: center;">
                                        {{ str_replace('.', ':', $totalFinalExito) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- BLOQUE DE FIRMAS Y FLUJO --}}
                        @php
                            $user = auth()->user();
                            $empLog = $user->empleado;
                            $pasosActivosFlujo = $pasosConfigurados->where('activo', 1)->values();
                            $pasoActualSoli = (int)($solicitud->paso_actual ?? 0);
                            $controller = app(\App\Http\Controllers\HoraExtraController::class);
                            $configPasoActual = $pasosActivosFlujo[$pasoActualSoli] ?? null;
                            $idJefeAutorizado = $controller->obtenerJefeId($configPasoActual, $solicitud, $pasoActualSoli);
                            $puedeFirmarActualmente = (
                                in_array($solicitud->estado, ['pendiente', 'proceso']) && 
                                $idJefeAutorizado && 
                                $empLog && 
                                $empLog->id == $idJefeAutorizado
                            );
                        @endphp

                        <!-- Visualización de firmas -->
                        <div style="width:100%; margin-top:30px; display:flex; flex-wrap:wrap; justify-content:space-between; text-align:center;">
                            @foreach($pasosActivosFlujo as $idx => $p)
                                @php
                                    $idJefePaso = $controller->obtenerJefeId($p, $solicitud, $idx);
                                    $imgFirma = null;
                                    if ($pasoActualSoli > $idx && $idJefePaso && $idJefePaso != -1) {
                                        $f = DB::table('firmas')->where('empleado_id', $idJefePaso)->where('activo', 1)->first();
                                        if ($f) {
                                            $imgFirma = base64_encode(is_resource($f->imagen_path) ? stream_get_contents($f->imagen_path) : $f->imagen_path);
                                        }
                                    }
                                    $esTurno = ($pasoActualSoli == $idx && in_array($solicitud->estado, ['pendiente', 'proceso']));
                                @endphp
                                <div style="flex:1; padding:10px;">
                                    <div style="height:70px; display:flex; align-items:center; justify-content:center;">
                                        @if($imgFirma)
                                            <img src="data:image/png;base64,{{ $imgFirma }}" style="max-height:65px;">
                                        @elseif($esTurno)
                                            <span style="color:#e67e22; font-size:9px; font-weight:bold;">TURNO:<br>{{ $p->nombre_paso }}</span>
                                        @else
                                            <span style="color:#ccc; font-size:9px;">PENDIENTE</span>
                                        @endif
                                    </div>
                                    <div style="border-top:1px solid #333; font-size:9px; padding-top:5px;">
                                        <b>{{ strtoupper($p->nombre_paso) }}</b>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Formulario para aprobar o rechazar la solicitud -->
                        <div class="text-center mt-4">
                            @if($puedeFirmarActualmente)
                                <form action="{{ route('horas_extras.validar', $solicitud->id) }}" method="POST">
                                    @csrf @method('PATCH')

                                    <!--  EL TOTAL CALCULADO -->
                                    <input type="hidden" name="total_calculado_vista" value="{{ $totalFinalExito }}">
                                    <!-- Lógica para determinar si el paso es dirección -->
                                    @php
                                        $pasoNombreA = strtoupper($configPasoActual->nombre_paso ?? '');
                                        $nombreLimpioA = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $pasoNombreA);
                                        $esDireccion = str_contains($nombreLimpioA, 'DIRECCION') || str_contains($nombreLimpioA, 'EJECUTIVA');
                                    @endphp
                                    @if($esDireccion)
                                        <!-- Bloque exclusivo para dirección -->
                                        <div class="card border-primary mb-4 mx-auto shadow" style="max-width: 380px; border-radius: 12px; border-width: 2px; overflow: hidden;">
                                            <div class="card-header bg-primary text-white fw-bold py-2 text-center">
                                                <i class="fas fa-money-check-alt me-2"></i> HORAS A PAGAR (DIRECCIÓN)
                                            </div>
                                            <div class="card-body p-3" style="background-color: #f7faff;">
                                                <label class="fw-bold mb-2 text-dark">¿Cuántas horas autoriza para pago?</label>
                                                <div class="input-group input-group-lg">
                                                    <input type="number" step="0.5" name="horas_pagadas"
                                                        class="form-control text-center fw-bold border-primary"
                                                        placeholder="{{ $solicitud->horas_trabajadas }}"
                                                        min="0" max="{{ $solicitud->horas_trabajadas }}">
                                                    <span class="input-group-text bg-primary text-white fw-bold">HRS</span>
                                                </div>
                                                <p class="text-muted small mt-2 mb-0">Total solicitado: <b>{{ $solicitud->horas_trabajadas }} h</b></p>
                                            </div>
                                        </div>
                                    @endif
                                    <!-- Botones de aprobar o rechazar -->
                                    <input type="hidden" name="accion" class="accion-input" value="">

                                    <div class="d-flex justify-content-center gap-3">
                                       
                                       <button type="button" class="btn-firmar btn btn-success btn-lg px-5 fw-bold" data-id="{{ $solicitud->id }}">
                                          FIRMAR
                                      </button>
                                      
                                  
                                      <button type="button" class="btn-iniciar-rechazo btn btn-outline-danger btn-lg">
                                         RECHAZAR
                                      </button>
                                 
                                      <button type="button" class="btn-rechazar-confirmar btn btn-danger btn-lg" style="display:none;">
                                          CONFIRMAR RECHAZO
                                      </button>
                                  </div>

                                   <div class="div-motivo mt-3" style="display: none; max-width: 400px; margin: 15px auto;">
                                      <label class="fw-bold">Motivo del rechazo:</label>
                                      <textarea name="motivo_rechazo" class="form-control" rows="2"></textarea>
                                   </div>
    
    
                                </form>
                            @else
                                <!-- Bloques para mostrar estado de la solicitud -->
                                @if($solicitud->estado == 'aprobado')
                                    <script>
                                        document.addEventListener("DOMContentLoaded", function() {
                                            var miModal = new bootstrap.Modal(document.getElementById('modalExitoFirmas'));
                                            miModal.show();
                                            setTimeout(() => { miModal.hide(); }, 4000);
                                        });
                                    </script>
                                    <div class="badge bg-light text-success border p-2">
                                        <i class="fas fa-check-circle me-1"></i> Completado
                                    </div>
                                @elseif($solicitud->estado == 'rechazado')
                                    <div class="alert alert-danger border d-inline-block p-3 shadow-sm" style="border-radius: 10px;">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <strong class="text-danger">Esta solicitud ha sido rechazada</strong>
                                    </div>
                                @else
                                    <div class="alert alert-light border d-inline-block p-3 shadow-sm" style="border-radius: 10px; color: #555;">
                                        <i class="fas fa-lock me-2 text-warning"></i>
                                        Esperando firma de: <strong class="text-primary">{{ $configPasoActual->nombre_paso ?? 'Siguiente nivel' }}</strong>
                                    </div>
                                @endif
                            @endif
                        </div>

                        @if(!empty($solicitud->observaciones_jefe))
                            <div class="mt-4 p-3 border border-danger rounded" style="background-color: #fff5f5;">
                                <h6 class="text-danger">
                                    <i class="fas fa-exclamation-circle"></i> Observación:
                                </h6>
                                <p class="mb-0 text-dark">
                                    {{ $solicitud->observaciones_jefe }}
                                </p>
                            </div>
                        @endif

                    </div>      
                </div> {{-- Fin área bg-white --}}

                <!-- Botones de acción fuera del área de impresión -->
                <div class="d-flex w-100">
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-primary px-4 fw-bold shadow no-print"
                            onclick="imprimirSolicitud({{ $solicitud->id }})">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

            </div> {{-- Fin modal-body --}}
        </div> {{-- Fin modal-content --}}
    </div> {{-- Fin modal-dialog --}}
</div> {{-- Fin modal --}}

{{-- Script --}}
<script>
    //Imprimir
    function imprimirSolicitud(id) {
      let contenido = document.getElementById('area-impresion-final-' + id).innerHTML;

     let iframe = document.createElement('iframe');
     iframe.style.position = 'absolute';
     iframe.style.width = '0px';
     iframe.style.height = '0px';
     iframe.style.border = '0';
     document.body.appendChild(iframe);

     let doc = iframe.contentWindow.document;
     doc.open();
     doc.write(`
            <html>
              <head>
                 <title>&nbsp;</title>
                 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
                 <style>
                     /* Estilos para limpiar la impresión */
                     @page { 
                         size: auto;   /* auto es el valor inicial */
                         margin: 0mm;  /* Esto elimina los encabezados y pies de página del navegador */
                        }

                        body { 
                          margin: 15mm; /* Margen real para el contenido del IHCI */
                          font-family: Arial, sans-serif; 
                          background-color: white !important; 
                        }

                       /* OCULTAR TODO LO QUE NO ES EL FORMATO */
                       .no-print, .btn, .badge, button, .alert, 
                       [class*="badge"], [class*="status"] { 
                         display: none !important; 
                        }
 
                       table { width: 100%; border-collapse: collapse; }
                          td, th { border: 1px solid black !important; padding: 5px; }
                    </style>
               </head>
               <body>
                  <div class="container-fluid">
                     ${contenido}
                   </div>
               </body>
            </html>
        `);
       doc.close();

       iframe.contentWindow.onload = function() {
         setTimeout(() => {
             iframe.contentWindow.focus();
             iframe.contentWindow.print();
             document.body.removeChild(iframe);
            }, 300);
        };
    }

    //Firmar y Rechazar
    document.addEventListener("DOMContentLoaded", function() {
    
      // 1. BLOQUEADOR DE CIERRE: Evita que el modal se cierre si está en proceso
      document.addEventListener('hide.bs.modal', function (e) {
         const btnFirmar = e.target.querySelector('.btn-firmar');
           if (btnFirmar && btnFirmar.disabled && !e.target.dataset.terminado) {
             e.preventDefault();
             e.stopPropagation();
            }
        }, true);

      // 2. REAPERTURA AUTOMÁTICA
      const urlParams = new URLSearchParams(window.location.search);
      const idModal = urlParams.get('abrir_modal');

      if (idModal) {
          // Usamos 'load' en lugar de 'DOMContentLoaded' para asegurar que 
          // todo el contenido (incluyendo CSS de Bootstrap) esté cargado
           window.addEventListener('load', function() {
              const btn = document.querySelector('[data-id="' + idModal + '"]');
        
              if (btn) {
                  const modalElement = btn.closest('.modal');
                   if (modalElement) {
                      const myModal = new bootstrap.Modal(modalElement, {
                          backdrop: 'static',
                          keyboard: false
                       });
                       myModal.show();
                
                    }
                }
            });
        }

        // 3. LOGICA DE FIRMA

      document.addEventListener('click', function(e) {
         const btn = e.target.closest('.btn-firmar');
          if (!btn) return;

              e.preventDefault();
              e.stopImmediatePropagation();

               const form = btn.closest('form');
                btn.disabled = true;
                btn.innerText = "Firmando...";

                const formData = new FormData();
                formData.append('_method', 'PATCH');
                formData.append('accion', 'aprobado');

               const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

               console.log("Iniciando petición de firma...");

               fetch(form.action, {
                  method: 'POST',
                  body: formData,
                  headers: { 
                     'X-Requested-With': 'XMLHttpRequest',
                     'X-CSRF-TOKEN': csrfToken 
                    }
                })
             .then(res => {
                  console.log("Respuesta del servidor recibida:", res.status);
                  if (res.ok) {
                      console.log("Firma exitosa. Solicitando actualización...");
                      // Usamos un timestamp para forzar que el servidor no envíe caché
                      return fetch(window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_t=' + Date.now());
                    } else {
                      throw new Error("Error en el servidor: " + res.status);
                    }
                })
              .then(response => response.text())
              .then(html => {
                  console.log("HTML recibido, actualizando modal...");
                  const parser = new DOMParser();
                   const doc = parser.parseFromString(html, 'text/html');
    
                  const modalActual = btn.closest('.modal');
                   const modalId = modalActual.id;
                   const nuevoModal = doc.getElementById(modalId);
    
                   if (nuevoModal) {
                      const nuevoModalBody = nuevoModal.querySelector('.modal-body');
                       const modalActualBody = modalActual.querySelector('.modal-body');
        
                      if (nuevoModalBody && modalActualBody) {
                          // 1. Actualizamos el modal visualmente para el usuario
                           modalActualBody.innerHTML = nuevoModalBody.innerHTML;
                           
                           // Creamos el contenedor del mensaje
                           const msg = document.createElement('div');
                            msg.className = 'alert alert-success text-center mt-3';
                            msg.style.marginBottom = '0'; // Eliminamos margen inferior extra
                            msg.innerHTML = 'Firma registrada.';
                            modalActualBody.append(msg);
            
                            // NUEVO: Eliminar el mensaje automáticamente después de 5 segundos
                            setTimeout(() => {
                              msg.remove(); 
                            }, 2000);

                            console.log("Modal actualizado. Iniciando recarga de página en 1.5 segundos...");
            
                           // 2. RECARGAMOS LA PÁGINA 
                          // Esto permite que el usuario vea el mensaje de éxito antes del refresco
                          setTimeout(() => {
                             window.location.reload();
                            }, 1000);
            
                        } else {
                          console.error("No se encontró el .modal-body");
                          window.location.reload(); // Fallback
                        }
                    } else {
                      console.error("El modal no existe en la página nueva. Recargando...");
                      window.location.reload(); // Fallback
                    }
                })
            .catch(err => {
                console.error("ERROR DETECTADO:", err);
                // Si falla, el usuario al menos sabe que algo salió mal
                btn.innerText = "Error - Clic para recargar";
                btn.disabled = false;
                btn.onclick = () => window.location.reload();
            });
       });


        // 3. LOGICA DE Rechazar
        document.addEventListener('click', function(e) {

          // INICIAR RECHAZO
          if (e.target.closest('.btn-iniciar-rechazo')) {
             const btn = e.target.closest('.btn-iniciar-rechazo');
             const form = btn.closest('form');
             btn.style.display = 'none';
             form.querySelector('.btn-rechazar-confirmar').style.display = 'inline-block';
             form.querySelector('.div-motivo').style.display = 'block';
            }

          // 2. Lógica INICIAR RECHAZO
          if (e.target.closest('.btn-iniciar-rechazo')) {
              const btn = e.target.closest('.btn-iniciar-rechazo');
              const form = btn.closest('form');
              btn.style.display = 'none';
              form.querySelector('.btn-rechazar-confirmar').style.display = 'inline-block';
              form.querySelector('.div-motivo').style.display = 'block';
            }

           // 3. Lógica CONFIRMAR RECHAZO
           if (e.target.closest('.btn-rechazar-confirmar')) {
              e.preventDefault(); 
            
              const btn = e.target.closest('.btn-rechazar-confirmar');
              const form = btn.closest('form');
              const textarea = form.querySelector('textarea[name="motivo_rechazo"]');
            
              if (!textarea.value.trim()) {
                  alert("Por favor, escriba el motivo.");
                  return;
                }

               btn.disabled = true;
               btn.innerText = "Procesando...";
            
               const formData = new FormData(form);
               formData.set('accion', 'rechazado');

               fetch(form.action, {
                 method: 'POST',
                 body: formData,
                 headers: { 
                     'X-Requested-With': 'XMLHttpRequest',
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
               .then(response => {
                 if (!response.ok) throw new Error('Error en el servidor');
                 return response.json();
                })
                .then(data => {
                  if (data.success) {
                     // 1. Bloquear propagación para evitar disparos múltiples
                     e.stopImmediatePropagation();

                     // 2. Limpiar todo el contenido del formulario para que no se vean botones ni nada viejo
                     // En lugar de ocultar uno por uno, reemplazamos el contenido con el mensaje de éxito
                      form.innerHTML = `
                          <div class="alert alert-success text-center">
                              <i class="fas fa-check-circle"></i> ${data.message}
                          </div>
                           <div class="mt-4 p-3 border border-danger rounded" style="background-color: #fff5f5;">
                              <h6 class="text-danger"><i class="fas fa-exclamation-circle"></i> Observación:</h6>
                              <p class="mb-0 text-dark">${textarea.value}</p>
                          </div>
                       `;

                      // 3. Forzar la recarga global después de un breve tiempo
                      // Esto refrescará toda la página principal, cerrando el modal automáticamente
                      setTimeout(() => {
                         window.location.reload(true); 
                        }, 1500);

                    } else {
                        throw new Error(data.message || 'Error al procesar');
                    }
                })
               .catch(error => {
                  console.error('Error:', error);
                  btn.disabled = false;
                  btn.innerText = "CONFIRMAR RECHAZO";
                  alert('Error al procesar la solicitud: ' + error.message);
                });
            }
        });
    });
</script> 



<style>
.swal2-container {
    z-index: 99999!important;
}



.my-swal-container {
    z-index: 10000 !important;
}
</style>