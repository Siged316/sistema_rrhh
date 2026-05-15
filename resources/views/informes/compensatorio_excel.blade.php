<table>

    {{-- =========================================================
         ENCABEZADO INSTITUCIONAL
    ========================================================== --}}
    <tr>
        <td style="background-color: #ffffff;"></td> {{-- Espacio para el logo --}}

        <th colspan="4" style="font-size: 16pt; font-weight: bold; text-align: left; color: #003366; background-color: #ffffff;">
            INSTITUTO HONDUREÑO DE CULTURA INTERAMERICANA
        </th>
    </tr>

    {{-- =========================================================
         TÍTULO DEL REPORTE
    ========================================================== --}}
    <tr>
        <td style="background-color: #ffffff;"></td>

        <th colspan="4" style="text-align: center; background-color: #ffffff;">
            Reporte de Tiempo Compensatorio - {{ $anio }}
        </th>
    </tr>

    {{-- Espacio visual --}}
    <tr>
        <td colspan="5" style="background-color: #ffffff;"></td>
    </tr>

    {{-- =========================================================
         INFORMACIÓN DEL EMPLEADO
    ========================================================== --}}
    <tr>

        {{-- Nombre del colaborador --}}
        <td style="background-color: #ffffff;">
            <b>Colaborador:</b>
        </td>

        <td colspan="2" style="background-color: #ffffff;">
            {{ strtoupper($empleado->nombre . ' ' . $empleado->apellido) }}
        </td>

        {{-- Departamento --}}
        <td style="background-color: #ffffff;">
            <b>Departamento:</b>
        </td>

        <td style="background-color: #ffffff;">
            {{ $empleado->departamento->nombre ?? 'N/A' }}
        </td>
    </tr>

    {{-- Segunda fila de información --}}
    <tr>

        {{-- Código del empleado --}}
        <td style="background-color: #ffffff;">
            <b>Código:</b>
        </td>

        <td colspan="2" style="background-color: #ffffff;">
            {{ $empleado->codigo_empleado }}
        </td>

        {{-- Fecha de generación --}}
        <td style="background-color: #ffffff;">
            <b>Generado:</b>
        </td>

        <td style="background-color: #ffffff;">
            {{ date('d/m/Y') }}
        </td>
    </tr>

    {{-- Espacio visual --}}
    <tr>
        <td colspan="5" style="background-color: #ffffff;"></td>
    </tr>

    {{-- =========================================================
         ENCABEZADO DE LA TABLA
    ========================================================== --}}
    <thead>
        <tr>

            {{-- Fecha --}}
            <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">
                Fecha
            </th>

            {{-- Descripción --}}
            <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">
                Descripción de actividad
            </th>

            {{-- Horas acumuladas --}}
            <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">
                Acum. (+)
            </th>

            {{-- Horas consumidas --}}
            <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">
                Cons. (-)
            </th>

            {{-- Horas pagadas --}}
            <th style="background-color: #003366; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">
                Pag. ($)
            </th>
        </tr>
    </thead>

    {{-- =========================================================
         CUERPO DE LA TABLA
    ========================================================== --}}
    <tbody>

        {{-- Variables acumuladoras --}}
        @php 
            $tAcum = 0; 
            $tCons = 0; 
            $tPag = 0; 
        @endphp

        {{-- Recorrido de registros --}}
        @foreach($todosLosRegistros as $item)

            @php

                // Validar si el registro es movimiento compensatorio
                $esMov = isset($item->horas_acumuladas);

                // Horas acumuladas
                $hA = $esMov ? ($item->horas_acumuladas ?? 0) : 0;

                // Horas pagadas
                $hP = $esMov ? ($item->horas_pagadas ?? 0) : 0;

                // Horas consumidas
                $hC = !$esMov 
                    ? (($item->horas > 0) ? $item->horas : (($item->dias ?? 0) * 8)) 
                    : 0;

                // Acumuladores generales
                $tAcum += $hA; 
                $tCons += $hC; 
                $tPag += $hP;
                
                // Definición de fecha
                $fecha = $esMov 
                    ? ($item->fecha ?? $item->created_at) 
                    : ($item->fecha_inicio ?? $item->created_at);

                // Definición de descripción
                $desc = $esMov 
                    ? ($item->descripcion ?? 'ACTIVIDAD') 
                    : ($item->tipo ?? 'SOLICITUD');
            @endphp

            {{-- =========================================================
                 FILA DE REGISTRO
            ========================================================== --}}
            <tr>

                {{-- Fecha --}}
                <td style="background-color: #ffffff; border: 1px solid #cccccc; text-align: center;">
                    {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                </td>

                {{-- Descripción --}}
                <td style="background-color: #ffffff; border: 1px solid #cccccc;">
                    {{ strtoupper($desc) }}
                </td>

                {{-- Acumulado --}}
                <td style="background-color: #ffffff; border: 1px solid #cccccc; text-align: center;">
                    {{ $hA > 0 ? $hA : '' }}
                </td>

                {{-- Consumido --}}
                <td style="background-color: #ffffff; border: 1px solid #cccccc; text-align: center;">
                    {{ $hC > 0 ? $hC : '' }}
                </td>

                {{-- Pagado --}}
                <td style="background-color: #ffffff; border: 1px solid #cccccc; text-align: center;">
                    {{ $hP > 0 ? $hP : '' }}
                </td>
            </tr>

        @endforeach
    </tbody>

    {{-- =========================================================
         SECCIÓN DE TOTALES
    ========================================================== --}}
    <tr>
        <td colspan="5" style="background-color: #ffffff;"></td>
    </tr>

    {{-- Total acumulado --}}
    <tr>
        <td colspan="3" style="background-color: #ffffff;"></td>

        <td style="background-color: #ffffff;">
            <b>TOTAL ACUMULADO:</b>
        </td>

        <td style="text-align: right; background-color: #ffffff; border: 1px solid #cccccc;">
            {{ number_format($tAcum, 2) }}
        </td>
    </tr>

    {{-- Total consumido --}}
    <tr>
        <td colspan="3" style="background-color: #ffffff;"></td>

        <td style="background-color: #ffffff;">
            <b>TOTAL CONSUMIDO:</b>
        </td>

        <td style="color: #ff0000; text-align: right; background-color: #ffffff; border: 1px solid #cccccc;">
            {{ number_format($tCons, 2) }}
        </td>
    </tr>

    {{-- Total pagado --}}
    <tr>
        <td colspan="3" style="background-color: #ffffff;"></td>

        <td style="background-color: #ffffff;">
            <b>TOTAL PAGADO:</b>
        </td>

        <td style="color: #0000ff; text-align: right; background-color: #ffffff; border: 1px solid #cccccc;">
            {{ number_format($tPag, 2) }}
        </td>
    </tr>

    {{-- Saldo disponible --}}
    <tr>
        <td colspan="3" style="background-color: #ffffff;"></td>

        <td style="background-color: #f0f0f0; border: 1px solid #cccccc;">
            <b>SALDO DISPONIBLE:</b>
        </td>

        <td style="background-color: #f0f0f0; font-weight: bold; text-align: right; border: 1px solid #cccccc;">
            {{ number_format($tAcum - $tCons - $tPag, 2) }}
        </td>
    </tr>

    {{-- =========================================================
         ESPACIOS PARA FIRMA
    ========================================================== --}}
    @for($i=0; $i<8; $i++)
        <tr>
            <td colspan="5" style="background-color: #ffffff;"></td>
        </tr>
    @endfor

    {{-- Línea de firma --}}
    <tr>
        <td colspan="5" style="text-align: center; font-weight: bold; background-color: #ffffff;">
            __________________________________
        </td>
    </tr>

    {{-- Responsable --}}
    <tr>
        <td colspan="5" style="text-align: center; background-color: #ffffff;">
            Gestión de Talento Humano
        </td>
    </tr>

</table>