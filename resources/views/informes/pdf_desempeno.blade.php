<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Desempeño - {{ $departamento->nombre }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #003366; padding-bottom: 10px; }
        .titulo { font-size: 18px; font-weight: bold; color: #003366; text-transform: uppercase; }
        .info-reporte { margin-bottom: 20px; width: 100%; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla th { background-color: #003366; color: white; padding: 10px 8px; text-align: left; }
        .tabla td { border: 1px solid #ddd; padding: 8px; }
        .total-row { background-color: #f2f2f2; font-weight: bold; font-size: 13px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #777; }
        .firmas { margin-top: 60px; width: 100%; text-align: center; }
        .espacio-firma { display: inline-block; width: 200px; border-top: 1px solid #000; margin: 0 40px; padding-top: 5px; }
    </style>
</head>
<body>
   <table style="width: 100%; border-bottom: 2px solid #003366; padding-bottom: 10px; margin-bottom: 20px;">
       <tr>
           {{-- Logo a la izquierda --}}
           <td style="width: 80px; vertical-align: middle;">
             @php
                 $path = public_path('images/IHCI.png');
                  $type = pathinfo($path, PATHINFO_EXTENSION);
                  $data = file_get_contents($path);
                  $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
               @endphp
               <img src="{{ $base64 }}" style="width: 70px; height: auto;">
           </td>

           {{-- Texto Central --}}
           <td style="text-align: center; vertical-align: middle;">
             <div style="font-size: 18px; font-weight: bold; color: #003366; text-transform: uppercase;">
                 Instituto Hondureño de Cultura Interamericana
              </div>
              <div style="font-size: 14px; margin-top: 5px;">Reporte Institucional de Desempeño por Departamento</div>
           </td>
        
           {{-- Espacio para equilibrar o logo de certificación si tuvieran --}}
           <td style="width: 80px;"></td>
       </tr>
   </table>
   

    <table class="info-reporte">
        <tr>
            <td><strong>Departamento:</strong> {{ $departamento->nombre }}</td>
            <td align="right"><strong>Período:</strong> {{ $periodo_texto }}</td>
        </tr>
        <tr>
            <td><strong>Generado el:</strong> {{ date('d/m/Y ') }}</td>
            <td align="right"><strong>Año Fiscal:</strong> {{ $anio }}</td>
        </tr>
    </table>

    <table class="tabla">
        <thead>
            <tr>
                <th width="60%">Actividad / Proyecto Evaluado</th>
                <th width="20%">Fecha de Registro</th>
                <th width="20%" style="text-align: center;">Puntuación</th>
            </tr>
        </thead>
       <tbody>
       @forelse($datos as $d)
          <tr>
              <td>{{ $d->actividad }}</td>
              <td>{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
              <td align="center"><strong>{{ number_format($d->resultado, 2) }}%</strong></td>
          </tr>
        @empty
          <tr>
              <td colspan="3" align="center">No se encontraron registros de evaluación para este período.</td>
           </tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" align="right">RENDIMIENTO GLOBAL DEL DEPARTAMENTO:</td>
                <td align="center" style="color: #003366;">{{ number_format($promedio_depto, 2) }}%</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; font-style: italic; color: #555;">
        * Este reporte consolida todas las evaluaciones (Autoevaluaciones y Evaluaciones de Jefe) completadas en el periodo seleccionado.
    </div>

    <div class="firmas">
        <br><br><br>
        
        <div class="espacio-firma">Sello Gerencia de Talento Humano</div>
    </div>

    <div class="footer">
        Documento de carácter institucional - Generado por Sistema RRHH IHCI
    </div>

</body>
</html>