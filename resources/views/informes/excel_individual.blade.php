<table>
    {{-- Dejamos filas vacías para el logo en la esquina --}}
    <tr></tr>

    <thead>
        {{-- Fila 1: Encabezado Institucional --}}
        <tr>
            <td style="width: 100px;"></td> {{-- Espacio para el logo --}}
            <th colspan="2" style="font-size: 16pt; font-weight: bold; text-align: left; color: #003366;">
                INSTITUTO HONDUREÑO DE CULTURA INTERAMERICANA
            </th>
        </tr>

        {{-- Fila 2: Título del Reporte --}}
        <tr>
            <td></td>
            <th colspan="2" style="font-size: 12pt; text-align: left; font-weight: bold;">
                Reporte de Desempeño Individual del Colaborador
            </th>
        </tr>
    
        <tr></tr>
        <tr></tr>

        {{-- Información Personal del Colaborador --}}
        <tr>
            <td style="font-weight: bold; background-color: #f2f2f2;">Colaborador:</td>
            <td colspan="2" style="text-transform: uppercase;">{{ $empleado->nombre }} {{ $empleado->apellido }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; background-color: #f2f2f2;">Departamento:</td>
            <td colspan="2">{{ $empleado->departamento->nombre }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; background-color: #f2f2f2;">Período:</td>
            <td colspan="2">{{ $periodo_texto }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; background-color: #f2f2f2;">Año Fiscal:</td>
            <td colspan="2">{{ $anio }}</td>
        </tr>
    
        <tr></tr>

        {{-- Encabezados de la Tabla --}}
        <tr>
            <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center; width: 350px;">Actividad / Proyecto Evaluado</th>
            <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center; width: 150px;">Fecha de Registro</th>
            <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center; width: 120px;">Puntuación</th>
        </tr>
    </thead>
    
    <tbody>
        @foreach($datos as $d)
        <tr>
            <td style="border: 1px solid #000000;">{{ $d->actividad }}</td>
            <td style="border: 1px solid #000000; text-align: center;">{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
            <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">
                {{ number_format($d->resultado, 2) }}%
            </td>
        </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr></tr>
        <tr>
            <th colspan="2" style="text-align: right; font-weight: bold;">PROMEDIO DE RENDIMIENTO INDIVIDUAL:</th>
            <th style="background-color: #f2f2f2; font-weight: bold; text-align: center; border: 1px solid #000000;">
                {{ number_format($promedio_individual, 2) }}%
            </th>
        </tr>
        
        <tr></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>
        <tr><td colspan="6" style="background-color: #ffffff;">&nbsp;</td></tr>


      {{-- Texto de Gestión de Talento Humano --}}
        <tr>
          <td style="background-color: #ffffff;"></td>
           <td colspan="3" style="border-top: 2px solid #000000; text-align: center; font-weight: bold; background-color: #ffffff;">
               GESTIÓN DE TALENTO HUMANO
           </td>
           <td style="background-color: #ffffff;"></td>
          <td style="background-color: #ffffff;"></td>
       </tr>
       <tr>
          <td style="background-color: #ffffff;"></td>
          <td colspan="3" style="text-align: center; background-color: #ffffff;">GTH</td>
          <td style="background-color: #ffffff;"></td>
          <td style="background-color: #ffffff;"></td>
       </tr>
    </tfoot>
</table>