<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>FT-GTH-002 - Solicitud</title>

    <style>
        /* 1. Definimos los márgenes de la página */
    @page {
        margin: 1cm 1cm 2cm 1cm; /* Superior, Derecho, Inferior (espacio para el footer), Izquierdo */
    }

    body { font-family: sans-serif; font-size: 11px; }
    
    .tabla-bordeada {
        width: 100%;
        border-collapse: collapse;
    }
    .tabla-bordeada th, .tabla-bordeada td {
        border: 1px solid #000;
        padding: 5px;
    }

    /* Estilo para los bloques grises de la imagen */
    .info-block {
        background-color: white; /* Gris claro */
        border: 1px solid white;
        padding: 8px;
        margin-bottom: 4px;
        width: 100%;
        box-sizing: border-box;
    }

    .text-center { text-align: center; }
    .text-start { text-align: left; }
    .text-end { text-align: right; }
    .fw-bold { font-weight: bold; }
    .bg-light { background-color: #f8f9fa; }

    /* Ajuste para que la tabla de firmas no se rompa */
    .firma-table { width: 100%; margin-top: 30px; border-collapse: collapse; }
    .firma-cell { width: 25%; text-align: center; padding: 10px; }
    .linea-firma { border-top: 1px solid #000; padding-top: 5px; font-size: 9px; }
</style>

<body>

{{-- CABECERA --}}
<table class="tabla-bordeada">
    <tr>
        <td rowspan="4" style="width:20%; text-align:center;">
            <img src="{{ public_path('images/ihci_logo.jpg') }}" style="max-height:50px;">
        </td>
        <td class="text-center fw-bold">FORMATO</td>
        <td class="text-center fw-bold" style="font-size:9px;">CÓDIGO</td>
        <td class="text-center">FT-GTH-002</td>
    </tr>
    <tr>
        <td rowspan="3" class="text-center fw-bold" style="font-size:12px;">
            SOLICITUD DE AUTORIZACIÓN DE TIEMPO COMPENSADO
        </td>
        <td class="text-center fw-bold" style="font-size:9px;">VERSIÓN</td>
        <td class="text-center">1.1</td>
    </tr>
    <tr>
        <td class="text-center fw-bold" style="font-size:9px;">VIGENTE DESDE</td>
        <td class="text-center">28/3/2023</td>
    </tr>
    <tr>
        <td colspan="2" class="text-center fw-bold" style="font-size:9px;">
            PÁGINA 1 DE 1
        </td>
    </tr>
</table>

<br>

{{-- DATOS CON FORMATO DE LA IMAGEN --}}
<div class="info-block">
    <strong>LUGAR Y FECHA:</strong> {{ $solicitud->lugar }}, {{ date('d/m/Y') }}
</div>

<div class="info-block">
    <strong>SOLICITADO A:</strong> {{ auth()->user()->empleado->nombre }} {{ auth()->user()->empleado->apellido }}
</div>

{{-- Fila dividida para Solicitado por y Cargo --}}
<table style="width: 100%; border-spacing: 0; margin-bottom: 4px;">
    <tr>
        <td style="width: 60%; padding-right: 4px; vertical-align: top;">
            <div class="info-block">
                <strong>SOLICITADO POR:</strong> {{ $solicitud->nombre }}
            </div>
        </td>
        <td style="width: 40%; vertical-align: top;">
            <div class="info-block">
                <strong>CARGO:</strong> {{ $solicitud->empleado->cargo ?? 'N/A' }}
            </div>
        </td>
    </tr>
</table>

{{-- TEXTO DE AUTORIZACIÓN --}}
<div style="margin: 20px 0; font-size: 12px;">
    Por este medio solicito me autorice: 
    <span style="background:#e0e0e0; padding:4px 15px; border:1px solid #999; font-weight:bold; margin: 0 5px;">
        {{ $solicitud->horas_acumuladas }}
    </span>
    horas a cuenta de tiempo compensatorio.
</div>

<p><strong>Detalle de actividades:</strong></p>

{{-- TABLA DE ACTIVIDADES --}}
<table class="tabla-bordeada text-center">
    <thead style="background-color: #f2f2f2;">
        <tr>
            <th style="width:15%;">FECHA</th>
            <th style="width:55%;">ACTIVIDAD</th>
            <th style="width:15%;">INICIO</th>
            <th style="width:15%;">FIN</th>
        </tr>
    </thead>
    <tbody>
        @php $d = $solicitud->detalles->first(); @endphp
        @for ($i = 1; $i <= 5; $i++)
            @php
                $f = "fecha".$i; $a = "actividad".$i;
                $hi = "hora_inicio".$i; $hf = "hora_fin".$i;
                $pi = "periodo_inicio".$i; $pf = "periodo_fin".$i;
            @endphp
            <tr style="height: 25px;">
                <td>{{ $d->$f ?? '' }}</td>
                <td class="text-start">{{ $d->$a ?? '' }}</td>
                <td>{{ $d->$hi ? ($d->$hi . ' ' . $d->$pi) : '' }}</td>
                <td>{{ $d->$hf ? ($d->$hf . ' ' . $d->$pf) : '' }}</td>
            </tr>
        @endfor
    </tbody>
    <tfoot>
        <tr class="fw-bold">
            <td colspan="3" class="text-end" style="background-color: #f2f2f2;">TOTAL HORAS:</td>
            <td style="background-color: #f2f2f2;">{{ $solicitud->horas_acumuladas }}</td>
        </tr>
    </tfoot>
</table>

{{-- FIRMAS --}}
<table class="firma-table">
    <tr>
       <td class="firma-cell" style="text-align: center; vertical-align: bottom;">
            {{-- Contenedor de la imagen para que no empuje la línea --}}
           <div style="height: 60px; display: flex; align-items: flex-end; justify-content: center;">
              @if(isset($firma_jefe) && $firma_jefe)
                  <img src="data:image/png;base64,{{ base64_encode($firma_jefe) }}" style="height: 60px; display: block; margin: 0 auto;">
                @endif
            </div>

            {{-- Esta es la línea que faltaba. 
             Le ponemos un border-top para que sea igual a las otras del PDF.
            --}}
            <div style="border-top: 1px solid #000; width: 85%; margin: 0 auto; padding-top: 5px; font-size: 10px;">
                Vo. Bo. Del Jefe Inmediato
            </div>
        </td>
        <td class="firma-cell">
            <div style="height:50px;"></div>
            <div class="linea-firma">Vo. Bo. Área a cargo</div>
        </td>
        <td class="firma-cell">
            <div style="height:50px;"></div>
            <div class="linea-firma">Vo. Bo. G.T.H.</div>
        </td>
        <td class="firma-cell">
            <div style="height:50px;"></div>
            <div class="linea-firma">Dirección Ejecutiva</div>
        </td>
    </tr>
</table>

<div style="position: absolute; bottom: 0; left: 0; font-size: 8px;">
    Original: Expediente / Copia: Empleado
</div>

</body>
</html>