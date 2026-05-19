<div id="contenedor-principal-show">

   <div id="area-impresion-final" style="background:white;padding:30px;color:black;font-family:sans-serif;">
       {{-- ENCABEZADO OFICIAL --}}

      <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; border: 1.5px solid black;">

          <tr>

            <td rowspan="4" style="width: 20%; border: 1px solid black; text-align: center; padding: 10px;">

                <img src="{{ asset('images/ihci_logo.jpg') }}" style="max-height: 55px;">

            </td>

            <td style="border: 1px solid black; text-align: center; font-weight: bold; font-size: 14px; width: 45%;">FORMATO</td>

            <td style="border: 1px solid black; text-align: center; font-weight: bold; width: 15%; font-size: 10px;">CÓDIGO</td>

            <td style="border: 1px solid black; text-align: center; width: 20%; font-size: 11px;">FT-GTH-001</td>

          </tr>

          <tr>

            <td rowspan="3" style="border: 1px solid black; text-align: center; font-weight: bold; font-size: 14px;">SOLICITUD DE PERMISO</td>

            <td style="border: 1px solid black; text-align: center; font-weight: bold; font-size: 10px;">VERSIÓN</td>

            <td style="border: 1px solid black; text-align: center; font-size: 11px;">2</td>

          </tr>

          <tr>

            <td style="border: 1px solid black; text-align: center; font-weight: bold; font-size: 10px;">VIGENTE DESDE</td>

            <td style="border: 1px solid black; text-align: center; font-size: 11px;">21/2/2024</td>

          </tr>

         <tr>

            <td colspan="2" style="border: 1px solid black; text-align: center; font-weight: bold; font-size: 10px;">PÁGINA 1 DE 1</td>

         </tr>

        </table>

   
       {{-- CUERPO DE DATOS --}}
      <div style="margin-top: 15px; font-size: 12px; font-family: Arial, sans-serif;">
         {{-- FILA: LUGAR Y FECHA --}}
          <div style="display: flex; align-items: flex-end; margin-bottom: 12px;">
             <span style="font-weight: bold; white-space: nowrap; width: 140px; padding-bottom: 2px;">Lugar y fecha:</span>
             <div style="flex: 1; border-bottom: 1px solid black; padding-left: 5px; padding-bottom: 2px;">
                 {{ $solicitud->lugar ?? 'Tegucigalpa, M.D.C.' }}, 
                  {{ \Carbon\Carbon::parse($solicitud->created_at)->format('d/m/Y') }}
               </div>
          </div>

           {{-- FILA: SOLICITANTE --}}
           <div style="display: flex; align-items: flex-end; margin-bottom: 12px;">
              <span style="font-weight: bold; white-space: nowrap; width: 140px; padding-bottom: 2px;">Solicitante:</span>
              <div style="flex: 1; border-bottom: 1px solid black; padding-left: 5px; padding-bottom: 2px;">
                  {{ strtoupper($solicitud->empleado?->nombre ?? $solicitud->nombre) }} {{ strtoupper($solicitud->empleado?->apellido ?? '') }}
              </div>
           </div>

           {{-- FILA: CARGO --}}
           <div style="display: flex; align-items: flex-end; margin-bottom: 12px;">
              <span style="font-weight: bold; white-space: nowrap; width: 140px; padding-bottom: 2px;">Cargo del solicitante:</span>
            <div style="flex: 1; border-bottom: 1px solid black; padding-left: 5px; padding-bottom: 2px;">
            {{ strtoupper($solicitud->empleado?->cargo ?? 'TÉCNICO') }}
        </div>
    </div>

</div>

<div style="margin-top: 15px; font-size: 12px; font-family: Arial, sans-serif;">

    {{-- SECCIÓN DE SALDOS --}}
    <div style="display: flex; align-items: center; margin-bottom: 15px;">
        {{-- Saldo Actual --}}
        <div style="display: flex; align-items: center;">
            <span style="font-weight: bold; width: 150px;">Saldo actual vacaciones:</span>
            <span style="border: 1px solid black; padding: 4px 0; width: 60px; text-align: center; display: inline-block;">
                {{ number_format($saldoActual, 2) }}
            </span>
            <span style="margin-left: 5px; width: 40px;">días</span>
        </div>

        {{-- Saldo Proyectado --}}
        <div style="display: flex; align-items: center; margin-left: 10px;">
            <span style="font-weight: bold; width: 120px; text-align: right; margin-right: 10px;">{{ $solicitud->estado === 'aprobado' ? 'Nuevo saldo:' : 'Saldo proyectado:' }}</span>
            <span style="border: 1px solid black; padding: 4px 0; width: 60px; text-align: center; display: inline-block; background-color: #f9f9f9;">
                {{ number_format($nuevoSaldo, 2) }}
            </span>
            <span style="margin-left: 5px;">días</span>
        </div>

        {{-- Info Registro --}}
        <div style="flex: 1; text-align: right; font-size: 8.5px; color: #888; border-top: 1px solid #f2f2f2; padding-top: 2px; line-height: 1;">
            INFO REGISTRO: {{ strtoupper($empleado->tipo_contrato) }} | SERV: {{ strtoupper($tiempoExacto) }} | DER: {{ $totalDerechoHistorico }}
        </div>
    </div>

    {{-- SECCIÓN SOLICITO AUTORIZACIÓN --}}
    <div style="display: flex; align-items: center; margin-bottom: 15px;">
        <span style="font-weight: bold; width: 150px; font-size: 13px;">Solicito se me autorice:</span>
        
        {{-- Cuadro Días --}}
        <div style="display: flex; align-items: center;">
            <span style="border: 1px solid black; padding: 4px 0; width: 60px; text-align: center; display: inline-block;">
                {{ number_format($solicitud->dias, 2) }}
            </span>
            <span style="margin-left: 5px; width: 40px;">días</span>
        </div>

        {{-- Cuadro Horas --}}
        <div style="display: flex; align-items: center; margin-left: 10px;">
            <span style="width: 120px; margin-right: 10px;"></span> {{-- Espacio vacío para alinear con el de arriba --}}
            <span style="border: 1px solid black; padding: 4px 0; width: 60px; text-align: center; display: inline-block;">
                {{ number_format($solicitud->horas, 2) }}
            </span>
            <span style="margin-left: 5px;">horas</span>
        </div>
    </div>
    <br>

    {{-- FECHAS DEL PERMISO --}}
    <div style="display: flex; align-items: center; margin-top: 20px;">
        <span style="font-weight: bold; text-transform: uppercase; width: 150px;">Fecha(s) del permiso:</span>
        <div style="flex: 1; border-bottom: 1px solid black; text-align: center; font-weight: bold; padding-bottom: 2px;">
            <span style="letter-spacing: 2px;">
                DEL &nbsp;&nbsp; {{ \Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d/m/Y') }}
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; AL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                {{ \Carbon\Carbon::parse($solicitud->fecha_fin)->format('d/m/Y') }}
            </span>
        </div>
    </div>

</div>

<br><br>
{{-- MOTIVO --}}
<p><b>Motivo del permiso:</b></p>
<table style="width:100%; border:1px solid black; border-collapse:collapse; font-size:12px;">
    @php 
        $tipo = strtolower($solicitud->tipo); 
        // Definimos las opciones que NO son "Otros"
        $opcionesConocidas = ['vacaciones', 'nupcias', 'sin goce', 'duelo', 'con goce', 'compensatorio', 'teletrabajo'];
        // Es otros si el tipo dice 'otros' o si el campo motivo_otro tiene contenido
        $esOtros = str_contains($tipo, 'otros') || !empty($solicitud->motivo_otro);
    @endphp

    <tr>
        <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'vacaciones') ? 'X' : '' }} ] A Cuenta de Vacaciones</td>
        <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'nupcias') ? 'X' : '' }} ] Nupcias</td>
    </tr>
    <tr>
        <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'sin goce') ? 'X' : '' }} ] Sin Goce de Sueldo</td>
        <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'duelo') ? 'X' : '' }} ] Duelo</td>
    </tr>
    <tr>
        <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'con goce') ? 'X' : '' }} ] Con Goce de Sueldo</td>
        <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'compensatorio') ? 'X' : '' }} ] A Cuenta de Tiempo Compensatorio</td>
    </tr>
    <tr>
        <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'teletrabajo') ? 'X' : '' }} ] Teletrabajo</td>
        <td style="border:1px solid black; padding:8px;">
            [ {{ $esOtros ? 'X' : '' }} ] Otros
        </td>
    </tr>
</table>

{{-- Detalle de Otros (Especifique) --}}
<div style="display: flex; align-items: flex-end; margin-top: 10px; font-size: 12px;">
    <span style="font-weight: bold; white-space: nowrap; width: 140px; padding-bottom: 2px;">Otros (Especifique):</span>
    <div style="flex: 1; border-bottom: 1px solid black; padding-left: 10px; padding-bottom: 2px;">
        {{-- Mostramos el contenido de motivo_otro si existe --}}
        @if($esOtros)
            {{ strtoupper($solicitud->motivo_otro) }}
        @else
            &nbsp;
        @endif
    </div>
</div>
    
<br>
{{-- LÓGICA DETALLE DEL PERMISO --}}
<div style="font-weight: bold; margin-bottom: 10px;">Detalles del Permiso: (Solo si aplica)</div>

<div style="position: relative;"> {{-- Contenedor relativo para el mensaje --}}
    
    {{-- El mensaje de "Realizado" (oculto por defecto) --}}
    <div id="msj-guardado" style="
        display: none; 
        position: absolute; 
        top: -25px; 
        right: 0; 
        background: #28a745; 
        color: white; 
        padding: 2px 8px; 
        border-radius: 4px; 
        font-size: 10px; 
        font-weight: bold;
        z-index: 10;">
        ✓ REALIZADO
    </div>

    <div 
        id="cuadro-detalles"
        contenteditable="true" 
        style="border: 1px solid black; width: 100%; min-height: 80px; padding: 10px; font-size: 11px; outline: none; box-sizing: border-box; background: white;"
        onblur="
            var id = '{{ $solicitud->id }}';
            var texto = this.innerText;
            var msj = document.getElementById('msj-guardado');
            
            fetch('/solicitudes/' + id + '/update-detalles', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ detalles: texto })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // Mostrar mensaje y efecto verde
                    msj.style.display = 'block';
                    this.style.backgroundColor = '#eaffea'; 
                    
                    setTimeout(() => { 
                        this.style.backgroundColor = 'transparent';
                        msj.style.display = 'none'; 
                    }, 1500);
                } else {
                    alert('Error en BD: ' + data.message);
                }
            })
            .catch(err => {
                alert('Error de conexión');
            });
        "
    >
        {{ $solicitud->detalles }}
    </div>
</div>

{{-- FIRMAS --}}

<div style="font-weight: bold; margin-bottom: 10px;">Autorización:</div>
  @php
    $firmaSolicitante = \App\Models\Firma::where('empleado_id', $solicitud->empleado_id)->where('activo',1)->first();
    $firmaJefe = $solicitud->aprobaciones->where('paso_orden',1)->first();
    $firmaGTH = $solicitud->aprobaciones->where('paso_orden',2)->first();

    $user = auth()->user();
    $empleado = $user->empleado;
    $esJefe = \App\Models\Departamento::where('nombre',$solicitud->departamento)
                    ->where('jefe_empleado_id',$empleado->id)->exists();
    $esGTH = strtolower($user->rol->nombre) == 'gth';
  @endphp

  <table style="width:100%;margin-top:40px;text-align:center;border-collapse:collapse;table-layout:fixed;">
    <tr>
        {{-- SOLICITANTE --}}
        <td>
            <div style="height:80px;display:flex;align-items:center;justify-content:center;">
                @if($firmaSolicitante && $firmaSolicitante->imagen_path)
                    <img src="data:image/png;base64,{{ base64_encode(is_resource($firmaSolicitante->imagen_path) ? stream_get_contents($firmaSolicitante->imagen_path) : $firmaSolicitante->imagen_path) }}" style="max-height:70px;">
                @else
                    <span style="color:#ccc;font-size:10px;"></span>
                @endif
            </div>
            <div style="border-top:1px solid black;width:90%;margin:5px auto 0;font-size:10px;">
                <b>SOLICITANTE</b><br>{{ strtoupper($solicitud->empleado?->nombre ?? '') }}
            </div>
        </td>

        {{-- JEFE --}}
        <td>
            <div style="height:80px;display:flex;align-items:center;justify-content:center;">
                @if($firmaJefe && $firmaJefe->firma)
                    <img src="data:image/png;base64,{{ base64_encode(is_resource($firmaJefe->firma->imagen_path) ? stream_get_contents($firmaJefe->firma->imagen_path) : $firmaJefe->firma->imagen_path) }}" style="max-height:70px;">
                @else
                    <span style="color:#ccc;font-size:10px;">PENDIENTE JEFE</span>
                @endif
            </div>
            <div style="border-top:1px solid black;width:90%;margin:5px auto 0;font-size:10px;">
                <b>JEFE INMEDIATO</b><br>{{ $firmaJefe?->user?->name ?? '' }}
            </div>
        </td>

        {{-- GTH --}}
        <td>
            <div style="height:80px;display:flex;align-items:center;justify-content:center;">
                @if($firmaGTH && $firmaGTH->firma)
                    <img src="data:image/png;base64,{{ base64_encode(is_resource($firmaGTH->firma->imagen_path) ? stream_get_contents($firmaGTH->firma->imagen_path) : $firmaGTH->firma->imagen_path) }}" style="max-height:70px;">
                @else
                    <span style="color:#ccc;font-size:10px;">PENDIENTE GTH</span>
                @endif
            </div>
            <div style="border-top:1px solid black;width:90%;margin:5px auto 0;font-size:10px;">
                <b>V°B° GESTIÓN TH</b><br>{{ $firmaGTH?->user?->name ?? '' }}
            </div>
        </td>
    </tr>
  </table>

   {{-- BOTONES DE FIRMA DEFINITIVOS CON ONCLICK DIRECTO --}}
   <div class="text-center mt-4">
    {{-- BLOQUE DEL JEFE INMEDIATO --}}
    @if($esJefe && (!$firmaJefe || !$firmaJefe->firma))
        <div class="d-inline-block">
            <form action="{{ route('solicitudes.procesar', $solicitud->id) }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="estado" value="aprobado">
                <button type="submit" class="btn btn-primary btn-sm mx-1">Firmar como Jefe</button>
            </form>

            <form action="{{ route('solicitudes.procesar', $solicitud->id) }}" method="POST" class="d-inline" 
                  onsubmit="let motivo = prompt('Por favor, ingrese el motivo del rechazo (Obligatorio):'); if(!motivo){ return false; } this.observaciones.value = motivo; return true;">
                @csrf
                <input type="hidden" name="estado" value="rechazado">
                <input type="hidden" name="observaciones" value="">
                <button type="submit" class="btn btn-danger btn-sm mx-1">Rechazar</button>
            </form>
        </div>
    @endif

    {{-- BLOQUE DE GESTIÓN DE TALENTO HUMANO --}}
    @if($esGTH && (!$firmaGTH || !$firmaGTH->firma))
        <div class="d-inline-block">
            <form action="{{ route('solicitudes.procesar', $solicitud->id) }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="estado" value="aprobado">
                <button type="submit" class="btn btn-success btn-sm mx-1">Firmar como GTH</button>
            </form>

            <form action="{{ route('solicitudes.procesar', $solicitud->id) }}" method="POST" class="d-inline"
                  onsubmit="let motivo = prompt('Por favor, ingrese el motivo del rechazo (Obligatorio):'); if(!motivo){ return false; } this.observaciones.value = motivo; return true;">
                @csrf
                <input type="hidden" name="estado" value="rechazado">
                <input type="hidden" name="observaciones" value="">
                <button type="submit" class="btn btn-danger btn-sm mx-1">Rechazar</button>
            </form>
        </div>
    @endif
   </div>

   <div style="margin-top: 60px; font-size: 10px; color: black; font-style: italic; text-align: center;">

    Original: Gestión de Talento Humano / Copia: Expediente colaborador

   </div>

   {{-- BOTÓN CON LÓGICA REPARADA E INTEGRADA --}}

   <div style="text-align: right; padding: 15px; background: #f8f9fa; border-top: 1px solid #ddd;" class="no-print">

       

    <button type="button" class="btn btn-primary px-4 fw-bold shadow no-print"

       onclick="(function(){
           var content = document.getElementById('area-impresion-final').innerHTML;
           var iframe = document.createElement('iframe');
           iframe.style.position = 'fixed';
          iframe.style.bottom = '0';
          iframe.style.right = '0';
          iframe.style.width = '0';
          iframe.style.height = '0';
          iframe.style.border = '0';
           document.body.appendChild(iframe);
          var doc = iframe.contentWindow.document;
          doc.open();
           doc.write('<html><head><style>' +
               // RESET CRÍTICO: Asegura que el padding no estire los cuadros
               '*{ box-sizing: border-box; } ' + 
               'body{ font-family: Arial, sans-serif; padding: 0; margin: 0; width: 100%; } ' +
               '@page{ size: letter; margin: 1.5cm; } ' +
               // Forzamos que los contenedores no se salgan del margen
               '#area-impresion-final, div { max-width: 100% !important; overflow: hidden; } ' +
               'table{ width: 100%; border-collapse: collapse; table-layout: fixed; } ' +
               '@media print { .no-print { display: none !important; } }' + 
               '</style></head><body>' + content + '</body></html>');
               doc.close();
               iframe.contentWindow.focus();
               setTimeout(function(){
                iframe.contentWindow.print();
                document.body.removeChild(iframe);
                }, 600);
            })()"

            <i class="fas fa-print me-2"></i>Imprimir

        </button>

    </div>
   </div>

</div>

{{-- LÓGICA UNIFICADA PARA APERTURA DE MODAL --}}
<script>
function procesarFirmaModal(e, accion, estado) {
    // Frenamos en seco cualquier envío o redirección nativa del formulario
    if(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    let observaciones = '';

    // Si es un rechazo, forzamos la captura del motivo
    if (estado === 'rechazado') {
        observaciones = prompt('Por favor, ingrese el motivo del rechazo (Obligatorio):');
        if (observaciones === null) return; // Si cancela, no hace nada
        if (observaciones.trim() === '') {
            alert('Debe especificar un motivo para poder rechazar la solicitud.');
            return;
        }
    }

    // Buscamos el token CSRF disponible en el documento
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    // Deshabilitamos el botón que originó el evento para evitar doble petición
    if(e && e.target) {
        e.target.disabled = true;
    }

    // Petición directa y limpia mediante fetch
    fetch('/solicitudes/{{ $solicitud->id }}/procesar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            estado: estado,
            observaciones: observaciones
        })
    })
    .then(response => response.json())
    .then(data => {
        // Ahora la respuesta sí saldrá en un alert controlado dentro de tu aplicación
        alert(data.message);
        if (data.success) {
            location.reload(); // Recarga la vista para actualizar el estatus de la firma
        } else {
            if(e && e.target) e.target.disabled = false;
        }
    })
    .catch(err => {
        console.error("Error en la petición:", err);
        alert('Ocurrió un inconveniente de red al procesar la solicitud.');
        if(e && e.target) e.target.disabled = false;
    });
}
</script>