<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Actividades - Tiempo Compensatorio</title>
    <style>
        @page {
            size: letter;
            margin: 1.2cm;
        }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #333;
            font-size: 8.5pt;
            line-height: 1.3;
        }

        /* Encabezado Institucional */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #003366;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }
        .titulo-entidad {
            color: #003366;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        .nombre-reporte {
            font-size: 10pt;
            font-weight: bold;
            color: #555;
            text-align: center;
        }

        /* Bloque de fecha y periodo (Fuera del encabezado) */
        .meta-data {
            text-align: right;
            font-size: 8pt;
            color: #444;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        /* Información General */
        .info-container {
            width: 97%;
            border: 1px solid #ccc;
            padding: 8px 12px;
            margin-bottom: 15px;
            background-color: #fcfcfc;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 0;
        }
        .label { font-weight: bold; color: #003366; }

        /* Tabla de Actividades */
        .tabla-actividades {
            width: 100%;
            border-collapse: collapse;
        }
        .tabla-actividades th {
            background-color: #003366;
            color: white;
            text-transform: uppercase;
            font-size: 7.5pt;
            padding: 7px;
            border: 1px solid #002244;
        }
        .tabla-actividades td {
            border: 1px solid #ddd;
            padding: 6px;
            vertical-align: middle;
        }
        
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .hrs-acumulada { color: #198754; font-weight: bold; }
        .hrs-pagada { color: #0d6efd; font-weight: bold; }

        /* Saldo Final */
        .seccion-saldo {
            margin-top: 10px;
            text-align: right;
        }
        .tabla-saldo {
            margin-left: auto;
            border-collapse: collapse;
        }
        .tabla-saldo td {
            padding: 8px 12px;
           
            font-size: 10pt;
        }
        .saldo-label { font-weight: bold; color: #1d1f20; text-transform: uppercase; }
        .saldo-valor { font-weight: bold; font-size: 12pt; color: #333; }

        /* Firmas */
        .seccion-firmas {
            width: 100%;
            margin-top: 40px;
        }
        .firma-box {
            text-align: center;
            width: 33.3%;
            vertical-align: bottom;
        }
        .linea-firma {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto;
            padding-top: 5px;
            font-weight: bold;
            font-size: 8pt;
        }
    </style>
</head>
<body>

    <!-- ENCABEZADO (Solo Logo y Títulos) -->
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                @php
                    $path = public_path('images/IHCI.png');
                    $base64 = file_exists($path) ? 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path)) : '';
                @endphp
                @if($base64) <img src="{{ $base64 }}" style="width: 70px;"> @endif
            </td>
            <td style="text-align: center; padding-right: 80px;">
                <div class="titulo-entidad">Instituto Hondureño de Cultura Interamericana</div>
                <div class="nombre-reporte">Reporte De Tiempo Compensatorio</div>
            </td>
        </tr>
    </table>

    <!-- INFO COLABORADOR -->
    <div class="info-container">
        <table class="info-table">
           <tr>
             <td style="width: 40%;"><span class="label">Colaborador:</span> {{ strtoupper($empleado->nombre . ' ' . $empleado->apellido) }}</td>
             <td style="width: 35%;"><span class="label">Departamento:</span> {{ mb_convert_case($empleado->departamento->nombre ?? 'N/A', MB_CASE_TITLE, "UTF-8") }}</td>
             <td style="width: 25%;"><span class="label">Periodo:</span> {{ $anio }}</td>
           </tr>
           <tr>
             <td><span class="label">Código Empleado:</span> {{ $empleado->codigo_empleado ?? '---' }}</td>
              <td><span class="label">Cargo:</span> {{ mb_convert_case($empleado->cargo, MB_CASE_TITLE, "UTF-8") }}</td>
             <td><span class="label">Generado:</span> {{ date('d/m/Y') }}</td>
          </tr>
      </table>
    </div>

  <!-- TABLA DE ACTIVIDADES -->
    <table class="tabla-actividades">
       <thead>
         <tr>
            <th width="12%">Fecha</th>
            <th width="48%">Concepto / Actividad Realizada</th>
            <th width="13%">Acum. (+)</th>
            <th width="13%">Cons. (-)</th>
            <th width="14%">Pag. ($)</th>
         </tr>
       </thead>
      <tbody>
        @php 
            $totalAcumulado = 0; 
            $totalConsumido = 0; 
            $totalPagado = 0;
        @endphp

        @foreach($todosLosRegistros as $item)
            @php 
                $hAcum = 0;
                $hCons = 0;
                $hPag = 0;
                $valorConsumo = 0;
                $unidad = "hrs";
                $concepto = "";

                $esMovimiento = isset($item->horas_acumuladas);
                
                if ($esMovimiento) {
                    $hAcum = $item->horas_acumuladas ?? 0;
                    $hPag = $item->horas_pagadas ?? 0;
                    $fecha = $item->fecha;

                    $actividades = [];
                    if($item->detalles) {
                        for($i=1; $i<=5; $i++) {
                            $col = "actividad_$i";
                            if(!empty($item->detalles->$col)) { $actividades[] = $item->detalles->$col; }
                        }
                    }
                    $concepto = !empty($actividades) ? implode(', ', $actividades) : ($item->descripcion ?? 'ACTIVIDAD TECNICA');
                } else {
                    $valorConsumo = ($item->horas > 0) ? $item->horas : ($item->dias ?? 0);
                    $unidad = ($item->horas > 0) ? "hrs" : "días";
                    $hCons = ($item->horas > 0) ? $item->horas : (($item->dias ?? 0) * 8);
                    
                    $concepto = $item->tipo ?? 'SOLICITUD';
                    $fecha = $item->fecha_inicio;
                }

                // Sumatorias para el final
                $totalAcumulado += $hAcum;
                $totalConsumido += $hCons;
                $totalPagado += $hPag;
            @endphp

            <tr>
                <td class="text-center">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</td>
                <td class="text-left" style="font-size: 8pt; text-transform: uppercase;">{{ $concepto }}</td>

                <td class="text-center">{{ $hAcum > 0 ? number_format($hAcum, 2) . ' hrs' : '-' }}</td>

                <td class="text-center" style="color: #dc3545;">
                    @if(!$esMovimiento && $valorConsumo > 0)
                        {{ number_format($valorConsumo, 2) . ' ' . $unidad }}
                    @else
                        -
                    @endif
                </td>

                <td class="text-center">{{ $hPag > 0 ? number_format($hPag, 2) . ' hrs' : '-' }}</td>
            </tr>
        @endforeach
       </tbody>
    </table>

    <!-- RESUMEN DE SALDOS AL FINAL -->
  <div class="seccion-saldo" style="margin-top: 20px;">
    @php
        $saldoFinal = $totalAcumulado - $totalConsumido;
        $esSaldoDisponible = $saldoFinal >= 0;
    @endphp
    <table class="tabla-saldo" style="width: 300px; border: 1px solid #ccc;">
        <tr>
            <td class="saldo-label" style="font-size: 8pt; border-bottom: 1px solid #eee;">Total Acumulado:</td>
            <td class="text-right" style="border-bottom: 1px solid #eee;">{{ number_format($totalAcumulado, 2) }} hrs</td>
        </tr>
        <tr>
            <td class="saldo-label" style="font-size: 8pt; border-bottom: 1px solid #eee;">Total Consumido:</td>
            <td class="text-right" style="color: #dc3545; border-bottom: 1px solid #eee;">- {{ number_format($totalConsumido, 2) }} hrs</td>
        </tr>
        <tr>
            <td class="saldo-label" style="font-size: 8pt; border-bottom: 1px solid #eee;">Total Pagado:</td>
            <td class="text-right" style="color: #0d6efd; border-bottom: 1px solid #eee;">- {{ number_format($totalPagado, 2) }} hrs</td>
        </tr>
        <tr style="background-color: {{ $esSaldoDisponible ? '#e8f5e9' : '#ffebee' }};">
            <td class="saldo-label" style="font-size: 10pt;">
                {{ $esSaldoDisponible ? 'HORAS DISPONIBLES:' : 'HORAS A DEBER:' }}
            </td>
            <td class="saldo-valor" style="text-align: right; color: {{ $esSaldoDisponible ? '#198754' : '#dc3545' }};">
                {{ number_format(abs($saldoFinal), 2) }} hrs
            </td>
        </tr>
    </table>
</div>

   <!-- SECCIÓN DE GRÁFICA -->
    @if(!empty($graficaBase64))

       <div style="margin-top:30px; text-align:center;">
          <h3 style="color:#003366;">
              Análisis Gráfico De Tiempo Compensatorio
          </h3>

           <img
            src="{{ $graficaBase64 }}"
            style="width:500px; height:auto;"
            
            >
       </div>

    @endif

   <!-- SECCIÓN DE FIRMA AUTOGENERADA -->
   <table style="width: 80%; margin-top: 50px; border-collapse: collapse;">
        <tr>
           <td style="text-align: center;">
               <div style="display: inline-block; width: 200px;">
                   <div style="height: 60px; margin-bottom: 5px; position: relative;">
                       @php
                          $firma = \DB::table('firmas')->where('activo', 1)->first();
                         $base64Image = null;

                           if ($firma && $firma->imagen_path) {
                              $imageData = $firma->imagen_path;
                              if (is_resource($imageData)) {
                                  $imageData = stream_get_contents($imageData);
                                }
                               $base64Image = 'data:image/png;base64,' . base64_encode($imageData);
                            }
                        @endphp

                        @if($base64Image)
                          <img src="{{ $base64Image }}" style="max-height: 60px; max-width: 180px; width: auto; height: auto;">
                        @else
                          <div style="padding-top: 20px; color: #ccc; font-size: 7pt; border: 1px dashed #ddd; height: 40px;">
                              ESPACIO FIRMA
                           </div>
                        @endif
                   </div>
                
                    <div style="border-top: 1px solid #000; padding-top: 3px;">
                      <strong style="font-size: 8pt; text-transform: uppercase; display: block;">
                          Gestión de Talento Humano
                       </strong>
                       <span style="font-size: 7pt; color: #444;">
                         GTH
                       </span>
                   </div>
               </div>
           </td>
       </tr>
    </table>
    <br> <br>
   <!-- NOTA LEGAL AL PIE DE LA PÁGINA -->
   <div style="position: absolute; bottom: 10px; width: 100%; text-align: center;">
    <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 5px;">
    <p style="font-size: 7pt; color: #999; margin: 0;">
        * Este reporte contiene información de solicitudes aprobadas y firmadas digitalmente según los registros de la tabla de firmas del sistema_rrhh.
    </p>
   </div>

</body>
</html>