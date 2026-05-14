<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de Permisos - {{ $empleado->nombre }} {{ $empleado->apellido }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 11px; margin: 10px; }
        .header-table { width: 100%; border-bottom: 2px solid #003366; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { width: 70px; height: auto; }
        .titulo-principal { font-size: 16px; font-weight: bold; color: #003366; text-transform: uppercase; text-align: center; }
        .subtitulo { font-size: 13px; text-align: center; margin-top: 5px; color: #444; }
        
        .info-empleado { width: 100%; margin-bottom: 20px; background-color: #f9f9f9; padding: 10px; border-radius: 5px; }
        .info-empleado td { padding: 3px 0; }
        
        .tabla { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla th { background-color: #003366; color: white; padding: 8px; text-align: left; text-transform: uppercase; font-size: 10px; }
        .tabla td { border: 1px solid #ddd; padding: 7px; vertical-align: middle; }
        .tabla tr:nth-child(even) { background-color: #f2f2f2; }
        
        .badge { padding: 3px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-aprobado { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #777; border-top: 1px solid #eee; padding-top: 5px; }
        .firma-container { margin-top: 50px; width: 100%; }
        .linea-firma { width: 200px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>

    {{-- Encabezado Institucional --}}
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                @php
                    $path = public_path('images/IHCI.png');
                    $base64 = '';
                    if (file_exists($path)) {
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = file_get_contents($path);
                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    }
                @endphp
                @if($base64)
                    <img src="{{ $base64 }}" class="logo">
                @endif
            </td>
            <td>
                <div class="titulo-principal">Instituto Hondureño de Cultura Interamericana</div>
                <div class="subtitulo">Reporte de Permisos</div>
            </td>
            <td style="width: 80px; text-align: right;">
                <span style="font-size: 9px;">RRHH-FORM-04</span>
            </td>
        </tr>
    </table>

    {{-- Información del Colaborador --}}
    <table class="info-empleado">
        <tr>
            <td width="15%"><strong>Colaborador:</strong></td>
            <td width="45%">{{ $empleado->nombre }} {{ $empleado->apellido }}</td>
            <td width="15%"><strong>Año Fiscal:</strong></td>
            <td width="25%">{{ $anio }}</td>
        </tr>
        <tr>
            <td><strong>Departamento:</strong></td>
            <td>{{ $empleado->departamento->nombre ?? 'N/A' }}</td>
            <td><strong>Periodo:</strong></td>
            <td>{{ $mes ?? 'Anual Acumulado' }}</td>
        </tr>
        <tr>
            <td><strong>Fecha Emisión:</strong></td>
            <td>{{ date('d/m/Y') }}</td>
            <td><strong>Estado:</strong></td>
            <td><span class="badge badge-aprobado">Aprobados</span></td>
        </tr>
    </table>

    {{-- Tabla de Registros --}}
    <table class="tabla">
        <thead>
            <tr>
                <th width="25%">Tipo de Permiso</th>
                <th width="15%">Fecha Inicio</th>
                <th width="15%">Fecha Fin</th>
                <th width="10%" style="text-align: center;">Días</th>
                <th width="10%" style="text-align: center;">Horas</th>
                <th width="25%">Observaciones / Motivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($solicitudes as $solicitud)
            <tr>
                <td>{{ $solicitud->tipo }}</td>
                <td>{{ $solicitud->fecha_inicio }}</td>
                <td>{{ $solicitud->fecha_fin }}</td>
                <td style="text-align: center;">{{ $solicitud->dias }}</td>
                <td style="text-align: center;">{{ $solicitud->horas }}</td>
                <td>{{ $solicitud->motivo }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="3" style="text-align: right;">TOTALES:</td>
                <td style="text-align: center;">{{ $total_dias }}</td>
                <td style="text-align: center;">{{ $total_horas }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- SECCIÓN DE FIRMA AUTOGENERADA -->
    <table style="width: 100%; margin-top: 50px; border-collapse: collapse;">
        <tr>
            <td style="text-align: center;">
                <div style="display: inline-block; width: 300px;">
                    <div style="height: 100px; margin-bottom: 5px; position: relative;">
                        @php
                            // Obtenemos la firma activa para el rol de GTH
                            $firma = \DB::table('firmas')
                                ->where('activo', 1)
                                ->first();

                            $base64Image = null;

                            if ($firma && $firma->imagen_path) {
                                $imageData = $firma->imagen_path;
                                
                                // Manejo de BLOB según driver de BD
                                if (is_resource($imageData)) {
                                    $imageData = stream_get_contents($imageData);
                                }
                                
                                $base64Image = 'data:image/png;base64,' . base64_encode($imageData);
                            }
                        @endphp

                        @if($base64Image)
                            <img src="{{ $base64Image }}" style="max-height: 100px; max-width: 250px;">
                        @else
                            <div style="padding-top: 40px; color: #ccc; font-size: 8pt; border: 1px dashed #ddd;">
                                ESPACIO PARA FIRMA <br> (No se encontró registro activo)
                            </div>
                        @endif
                    </div>
                    
                    <!-- Línea y Nombre del Puesto -->
                    <div style="border-top: 1.5px solid #000; padding-top: 5px;">
                        <strong style="font-size: 9pt; text-transform: uppercase; display: block;">
                            Gestión de Talento Humano
                        </strong>
                        <span style="font-size: 8pt; color: #444;">
                            IHCI
                        </span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- NOTA LEGAL AL PIE DE LA PÁGINA -->
    <div style="position: absolute; bottom: 10px; width: 100%; text-align: center;">
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 5px;">
        <p style="font-size: 7pt; color: #999; margin: 0;">
            * Este reporte contiene información de solicitudes aprobadas y firmadas digitalmente según los registros de la tabla de firmas del sistema_rrhh.
        </p>
        <p style="font-size: 7pt; color: #999; margin: 0;">
            * Este reporte excluye registros de vacaciones y tiempo compensatorio, centrándose únicamente en permisos institucionales, médicos y especiales.    
       </p>
   </div>

</body>
</html>