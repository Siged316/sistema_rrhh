<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: Arial, sans-serif; color: black; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .border { border: 1px solid black; }
        .center { text-align: center; }
        .p-10 { padding: 10px; }
    </style>
</head>
<body>

    {{-- ENCABEZADO --}}
    <table class="border">
        <tr>
            <td rowspan="4" class="border center p-10" style="width: 20%;">
                <img src="{{ public_path('images/ihci_logo.jpg') }}" style="max-height: 55px;">
            </td>
            <td class="border center" style="font-weight: bold; font-size: 14px;">FORMATO</td>
            <td class="border center" style="font-weight: bold;">CÓDIGO</td>
            <td class="border center">FT-GTH-001</td>
        </tr>
        <tr>
            <td rowspan="3" class="border center" style="font-weight: bold; font-size: 14px;">
                SOLICITUD DE PERMISO
            </td>
            <td class="border center" style="font-weight: bold;">VERSIÓN</td>
            <td class="border center">2</td>
        </tr>
        <tr>
            <td class="border center" style="font-weight: bold;">VIGENTE DESDE</td>
            <td class="border center">21/2/2024</td>
        </tr>
        <tr>
            <td colspan="2" class="border center" style="font-weight: bold;">PÁGINA 1 DE 1</td>
        </tr>
    </table>

    <br><br>

    {{-- DATOS PRINCIPALES --}}
   <div style="margin-top: 15px; font-size: 12px; font-family: Arial, sans-serif;">

      {{-- FILA: LUGAR Y FECHA --}}
       <div style="display: table; width: 100%; margin-bottom: 12px;">
          <div style="display: table-cell; font-weight: bold; width: 140px; vertical-align: bottom;">
              Lugar y fecha:
           </div>
         
           <div style="display: table-cell; border-bottom: 1px solid black; vertical-align: bottom; padding-left: 5px;">
              {{ $solicitud->lugar ?? 'Comayagua' }}, 
              {{ \Carbon\Carbon::parse($solicitud->created_at)->format('d/m/Y') }}
           </div>
      </div>

       {{-- FILA: SOLICITANTE --}}
      <div style="display: table; width: 100%; margin-bottom: 12px;">
          <div style="display: table-cell; font-weight: bold; width: 140px; vertical-align: bottom;">
              Solicitante:
          </div>
    
          <div style="display: table-cell; border-bottom: 1px solid black; vertical-align: bottom; padding-left: 5px;">
              {{ strtoupper($solicitante) }}
           </div>
       </div>

        {{-- FILA: CARGO --}}
        <div style="display: table; width: 100%; margin-bottom: 12px;">
           <div style="display: table-cell; font-weight: bold; width: 140px; vertical-align: bottom;">
              Cargo:
           </div>
  
           <div style="display: table-cell; border-bottom: 1px solid black; vertical-align: bottom; padding-left: 5px;">
              {{ strtoupper($empleado->cargo ?? 'N/A') }}
          </div>
       </div>

    </div>

    <br>
    
   <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-family: Arial, sans-serif; font-size: 12px;">
    
    {{-- FILA SALDOS --}}
    <tr>
        <td style="width: 150px; font-weight: bold; padding: 5px 0;">Saldo actual:</td>
        <td style="width: 120px; padding: 5px 0;">
            <span style="border: 1px solid black; padding: 2px 10px; display: inline-block;">{{ number_format($saldoActual, 2) }}</span> días
        </td>
        <td style="width: 120px; font-weight: bold; padding: 5px 0;">Nuevo saldo:</td>
        <td style="padding: 5px 0;">
            <span style="border: 1px solid black; padding: 2px 10px; display: inline-block; background-color: #f9f9f9;">{{ number_format($nuevoSaldo, 2) }}</span> días
        </td>
    </tr>

    {{-- FILA SOLICITUD --}}
    <tr>
        <td style="font-weight: bold; padding: 5px 0;">Solicito se me autorice:</td>
        <td style="padding: 5px 0;">
            <span style="border: 1px solid black; padding: 2px 10px; display: inline-block;">{{ number_format($solicitud->dias, 2) }}</span> días
        </td>
        <td colspan="2" style="padding: 5px 0;">
            <span style="border: 1px solid black; padding: 2px 10px; display: inline-block;">{{ number_format($solicitud->horas, 2) }}</span> horas
        </td>
    </tr>
    
    <br>
    
    {{-- FILA FECHAS --}}
    <tr>
        <td style="font-weight: bold; padding: 5px 0; vertical-align: middle;">Fecha(s) del permiso:</td>
        <td colspan="3" style="padding: 5px 0; border-bottom: 1px solid black; vertical-align: middle; text-align: center; font-weight: bold;">
            DEL {{ \Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d/m/Y') }} 
            &nbsp;&nbsp;&nbsp; AL &nbsp;&nbsp;&nbsp; 
            {{ \Carbon\Carbon::parse($solicitud->fecha_fin)->format('d/m/Y') }}
        </td>
    </tr>

</table>


</div>

   <br><br>

    {{-- MOTIVO --}}
    <p><b>Motivo del permiso:</b></p>
    <table style="width:100%; border:1px solid black; border-collapse:collapse; font-size:12px;">
        <tr>
          <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'vacaciones') ? 'X' : '' }} ] A Cuenta de Vacaciones</td>
          <td style="border:1px solid black; padding:8px;">[ {{ str_contains($tipo,'nupcias') ? 'X' : '' }} ] Nupcias</td>          </tr>
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

    <table style="margin-top: 10px; width: 100%; font-size: 12px;">
       <tr>
          <td style="font-weight: bold; width: 140px; white-space: nowrap; vertical-align: bottom;">
              Otros (Especifique):
          </td>

           <td style="border-bottom: 1px solid black; padding-left: 10px; vertical-align: bottom;">
              @if($esOtros)
                  {{ strtoupper($solicitud->motivo_otro ?? '') }}
              @else
                  &nbsp;
               @endif
           </td>
       </tr>
   </table>
   
   <br><br>

    {{-- DETALLES --}}
    <div style="margin-top: 15px; border: 1px solid black; padding: 10px; height: 50px;">
        <b>Detalles:</b> {{ $solicitud->detalles }}
    </div>

    {{-- FIRMAS DINÁMICAS --}}
    <table style="margin-top: 50px; text-align: center; width: 100%;">
    <tr>
        {{-- COLUMNA 1: SOLICITANTE --}}
        <td style="width: 33%;">
            <div style="height: 60px;">
                @php $solicitante = $aprobaciones->firstWhere('rol', 'solicitante'); @endphp
                @if($solicitante && $solicitante->firma && $solicitante->firma->imagen_path)
                    <img src="data:image/png;base64,{{ base64_encode(is_resource($solicitante->firma->imagen_path) ? stream_get_contents($solicitante->firma->imagen_path) : $solicitante->firma->imagen_path) }}" style="max-height: 60px;">
                @endif
            </div>
            <div style="border-top: 1px solid black; width: 80%; margin: auto; padding-top: 5px;">
                SOLICITANTE
            </div>
        </td>

        {{-- COLUMNA 2: JEFE INMEDIATO --}}
       <td style="width: 33%;">
          <div style="height: 60px; margin-bottom: 5px;">
              @php 
                 // Buscamos cualquier registro que contenga la palabra 'jefe' ignorando mayúsculas/minúsculas
                  $jefe = $aprobaciones->filter(function($item) {
                      return str_contains(strtolower($item->rol_nombre), 'jefe');
                    })->first();
               @endphp

                @if($jefe && $jefe->firma && $jefe->firma->imagen_path)
                    <img src="data:image/png;base64,{{ base64_encode(is_resource($jefe->firma->imagen_path) ? stream_get_contents($jefe->firma->imagen_path) : $jefe->firma->imagen_path) }}" style="max-height: 60px;">
               @else
                    @if(!$jefe) 
                      <span style="font-size: 10px; color: red;">(Rol jefe no encontrado)</span>
                  @endif
               @endif
          </div>
        
          <div style="border-top: 1px solid black; width: 80%; margin: auto; padding-top: 5px;">
             JEFE INMEDIATO
         </div>
        </td>

        {{-- COLUMNA 3: V°B° GESTIÓN TH --}}
        {{-- COLUMNA 3: V°B° GESTIÓN TH --}}
<td style="width: 33%;">
    <div style="height: 60px; margin-bottom: 5px; border: 1px dashed #ccc;"> {{-- Borde temporal para ver el contenedor --}}
        @php 
            // Buscamos el registro de GTH (ajusta 'gth' si tu base de datos lo guarda diferente)
            $gth = $aprobaciones->first(function($item) {
                return str_contains(strtolower($item->rol_nombre ?? ''), 'gth') || str_contains(strtolower($item->rol_nombre ?? ''), 'recursos humanos');
            });
        @endphp

        @if($gth)
            @if($gth->firma && $gth->firma->imagen_path)
                <img src="data:image/png;base64,{{ base64_encode(is_resource($gth->firma->imagen_path) ? stream_get_contents($gth->firma->imagen_path) : $gth->firma->imagen_path) }}" style="max-height: 60px;">
            @else
                <span style="font-size: 10px; color: orange;">(Registro encontrado, sin firma)</span>
            @endif
        @else
            <span style="font-size: 10px; color: red;">(No se encontró rol GTH)</span>
        @endif
    </div>
    <div style="border-top: 1px solid black; width: 80%; margin: auto; padding-top: 5px;">
        V°B° GESTIÓN TH
    </div>
</td>
    </tr>
    </table>

  {{-- MOTIVO DE RECHAZO --}}
  @if($solicitud->estado == 'rechazado')
      <div style="margin-top: 25px; border: 1px solid #dc3545; padding: 10px;">
          <strong>MOTIVO DEL RECHAZO:</strong><br><br>
          {{ $solicitud->observaciones ?? 'No se especificó un motivo.' }}
      </div>
   @endif

</body>
</html>