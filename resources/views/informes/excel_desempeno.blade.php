<table>
  {{-- Dejamos 4 filas vacías para que el logo se vea bien en la esquina --}}
    <tr></tr>

  <thead>
      {{-- Fila 1: El Logo estará en A1, el texto empieza en B1 --}}
        <tr>
         <td style="width: 100px;"></td> <!-- Espacio reservado para el logo en Columna A -->
          <th colspan="2" style="font-size: 16pt; font-weight: bold; text-align: left; color: #003366;">
             INSTITUTO HONDUREÑO DE CULTURA INTERAMERICANA
          </th>
      </tr>

      {{-- Fila 2: Subtítulo --}}
       <tr>
         <td></td> <!-- Espacio para el logo -->
         <th colspan="2" style="font-size: 12pt; text-align: left; font-weight: bold;">
             Reporte Institucional de Desempeño por Departamento
         </th>
      </tr>
    
      {{-- Filas vacías para que el logo respire --}}
      <tr></tr>
      <tr></tr>

      {{-- Información General --}}
      <tr>
         <td style="font-weight: bold; background-color: #f2f2f2;">Departamento:</td>
         <td colspan="2">{{ $departamento->nombre }}</td>
      </tr>
      <tr>
         <td style="font-weight: bold; background-color: #f2f2f2;">Período:</td>
         <td colspan="2">{{ $periodo_texto }}</td>
      </tr>
      <tr>
         <td style="font-weight: bold; background-color: #f2f2f2;">Año Fiscal:</td>
         <td colspan="2">{{ $anio }}</td>
      </tr>
    
      <tr></tr> {{-- Espacio antes de los datos --}}

      {{-- Encabezados de la Tabla de Datos --}}
        <tr>
         <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Actividad / Proyecto Evaluado</th>
         <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Fecha de Registro</th>
         <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Puntuación</th>
       </tr>
    </thead>
    <tbody>
        @foreach($datos as $d)
        <tr>
            <td style="border: 1px solid #000000;">{{ $d->actividad }}</td>
            <td style="border: 1px solid #000000;">{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
            <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">
                {{ number_format($d->resultado, 2) }}%
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr></tr>
        <tr>
            <th colspan="2" style="text-align: right; font-weight: bold;">RENDIMIENTO GLOBAL DEL DEPARTAMENTO:</th>
            <th style="background-color: #f2f2f2; font-weight: bold; text-align: center; border: 1px solid #000000;">
                {{ number_format($promedio_depto, 2) }}%
            </th>
        </tr>
    </tfoot>
</table>