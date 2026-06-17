<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Individual de Desempeño</title>

    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #003366;
            padding-bottom: 10px;
        }

        .titulo {
            font-size: 18px;
            font-weight: bold;
            color: #003366;
            text-transform: uppercase;
        }

        .info-reporte {
            margin-bottom: 20px;
            width: 100%;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tabla th {
            background-color: #003366;
            color: white;
            padding: 10px 8px;
            text-align: left;
        }

        .tabla td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .total-row {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 13px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
        }

        .firmas {
            margin-top: 60px;
            width: 100%;
            text-align: center;
        }

        .espacio-firma {
            display: inline-block;
            width: 220px;
            border-top: 1px solid #000;
            margin: 0 40px;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    {{-- ENCABEZADO --}}
    <table style="width: 100%; border-bottom: 2px solid #003366; padding-bottom: 10px; margin-bottom: 20px;">
        <tr>

            {{-- Logo --}}
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

                <div class="titulo">
                    Instituto Hondureño de Cultura Interamericana
                </div>

                <div style="font-size: 14px; margin-top: 5px;">
                    Reporte Individual de Evaluación de Desempeño
                </div>

            </td>

            {{-- Espacio derecho --}}
            <td style="width: 80px;"></td>

        </tr>
    </table>


    {{-- INFORMACIÓN DEL REPORTE --}}
    <table class="info-reporte">

        <tr>
            <td>
                <strong>Colaborador:</strong>
                {{ $empleado->nombre }} {{ $empleado->apellido }}
            </td>

            <td align="right">
                <strong>Año:</strong>
                {{ $anio }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Departamento:</strong>
                {{ $empleado->departamento->nombre ?? 'N/A' }}
            </td>

            <td align="right">
                <strong>Fecha de Impresión:</strong>
                {{ date('d/m/Y') }}
            </td>
        </tr>

    </table>


    {{-- TABLA DE DATOS --}}
    <table class="tabla">
        <thead>
           <tr>
             <th width="25%">Actividad / Proyecto</th>
             <th width="15%">Formulario</th>
             <th width="15%">Tipo</th>
             <th width="20%">Departamento</th>
             <th width="15%">Fecha</th>
             <th width="10%" style="text-align: center;">Resultado</th>
           </tr>
       </thead>
       <tbody>
         @forelse($datos as $dato)
          <tr>
             <td>{{ $dato->actividad }}</td>
              <td>{{ $dato->nombre_formulario ?? 'N/A' }}</td>
              <td>{{ $dato->tipo }}</td>
              <td>{{ $dato->depto_evaluador }}</td>
              <td>{{ \Carbon\Carbon::parse($dato->fecha)->format('d/m/Y') }}</td>
              <td align="center">
              <strong>{{ number_format($dato->resultado, 2) }}%</strong>
              </td>
          </tr>
         @empty
           <tr>
             <td colspan="6" align="center">No hay registros de evaluaciones completadas.</td>
          </tr>
          @endforelse
       </tbody>
       <tfoot>
          <tr class="total-row">
             <td colspan="5" align="right"><strong>RENDIMIENTO GLOBAL:</strong></td>
             <td align="center" style="color:#003366;">
                 <strong>{{ number_format($promedio_global ?? 0, 2) }}%</strong>
             </td>
          </tr>
      </tfoot>
   </table>
    
    <!-- SECCIÓN DE GRÁFICA -->
    @if(!empty($graficaBase64))

       <div style="margin-top:30px; text-align:center;">
          <h3 style="color:#003366;">
              Análisis Gráfico Individual
          </h3>

           <img
            src="{{ $graficaBase64 }}"
            style="width:700px; height:auto;"
            >
       </div>

    @endif

    <br><br><br>
    {{-- =========================================================
         SECCIÓN DE FIRMA
    ========================================================== --}}
    <table style="width: 100%; margin-top: 50px; border-collapse: collapse;">
         <tr>

        <td style="text-align: center;">

            {{-- Contenedor de firma --}}
            <div style="display: inline-block; width: 300px;">

                {{-- Área de imagen de firma --}}
                <div style="height: 100px; margin-bottom: 5px; position: relative;">

                    @php

                        /*
                        |--------------------------------------------------------------------------
                        | Inicializar variable de firma
                        |--------------------------------------------------------------------------
                        */
                        $base64Image = null;

                        /*
                        |--------------------------------------------------------------------------
                        | Validar si existe firma activa
                        |--------------------------------------------------------------------------
                        */
                        if (isset($firma) && $firma->imagen_path) {

                            // Obtiene los datos de la imagen
                            $imageData = $firma->imagen_path;
                            
                            /*
                            |--------------------------------------------------------------------------
                            | Si la imagen viene como recurso/stream
                            |--------------------------------------------------------------------------
                            */
                            if (is_resource($imageData)) {

                                // Convierte stream a contenido binario
                                $imageData = stream_get_contents($imageData);
                            }
                            
                            /*
                            |--------------------------------------------------------------------------
                            | Convierte la imagen en Base64
                            |--------------------------------------------------------------------------
                            */
                            $base64Image = 'data:image/png;base64,' . base64_encode($imageData);
                        }
                    @endphp

                    {{-- =========================================================
                         SI EXISTE FIRMA
                    ========================================================== --}}
                    @if($base64Image)

                        {{-- Imagen de firma --}}
                        <img 
                            src="{{ $base64Image }}" 
                            style="max-height: 100px; max-width: 250px;"
                        >

                    {{-- =========================================================
                         SI NO EXISTE FIRMA
                    ========================================================== --}}
                    @else

                        {{-- Espacio vacío para firma --}}
                        <div style="padding-top: 40px; color: #ccc; font-size: 8pt; border: 1px dashed #ddd;">

                            ESPACIO PARA FIRMA <br> 
                            (No se encontró registro activo)
                        </div>

                    @endif
                </div>
                
                {{-- =========================================================
                     LÍNEA Y TEXTO DE RESPONSABLE
                ========================================================== --}}
                <div style="border-top: 1.5px solid #000; padding-top: 5px;">

                    {{-- Nombre del área --}}
                    <strong style="font-size: 9pt; text-transform: uppercase; display: block;">
                        Gerencia de Talento Humano
                    </strong>

                    {{-- Institución --}}
                    <span style="font-size: 8pt; color: #444;">
                        GTH
                    </span>
                </div>
            </div>
        </td>
          </tr>
    </table>


    {{-- FOOTER --}}
    <div class="footer">
        Documento de carácter institucional - Generado por Sistema RRHH IHCI
            {{-- NOTA --}}
       <div style="margin-top: 20px; font-style: italic; color: #555;">

        * Este reporte refleja el desempeño individual del colaborador,
        considerando las evaluaciones registradas durante el período seleccionado.

       </div>
    </div>

</body>
</html>