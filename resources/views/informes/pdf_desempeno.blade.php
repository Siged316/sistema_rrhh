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

   {{-- =========================================================
        ENCABEZADO PRINCIPAL DEL REPORTE
   ========================================================== --}}
   <table style="width: 100%; border-bottom: 2px solid #003366; padding-bottom: 10px; margin-bottom: 20px;">
       <tr>

           {{-- =========================================================
                LOGO INSTITUCIONAL IZQUIERDO
           ========================================================== --}}
           <td style="width: 80px; vertical-align: middle;">

             @php
                 /*
                 |--------------------------------------------------------------------------
                 | Obtener logo institucional
                 |--------------------------------------------------------------------------
                 | Se convierte la imagen a Base64 para que DomPDF
                 | pueda renderizarla correctamente dentro del PDF.
                 |--------------------------------------------------------------------------
                 */

                 // Ruta física del logo
                 $path = public_path('images/IHCI.png');

                 // Obtiene la extensión del archivo
                 $type = pathinfo($path, PATHINFO_EXTENSION);

                 // Lee el contenido binario de la imagen
                 $data = file_get_contents($path);

                 // Convierte la imagen a Base64
                 $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
               @endphp

               {{-- Imagen del logo --}}
               <img src="{{ $base64 }}" style="width: 70px; height: auto;">
           </td>

           {{-- =========================================================
                TEXTO CENTRAL DEL ENCABEZADO
           ========================================================== --}}
           <td style="text-align: center; vertical-align: middle;">

             {{-- Nombre institucional --}}
             <div style="font-size: 18px; font-weight: bold; color: #003366; text-transform: uppercase;">
                 Instituto Hondureño de Cultura Interamericana
              </div>

              {{-- Título del reporte --}}
              <div style="font-size: 14px; margin-top: 5px;">
                  Reporte Institucional de Desempeño por Departamento
              </div>
           </td>
        
           {{-- =========================================================
                ESPACIO DERECHO
                (Puede usarse para otro logo o certificación)
           ========================================================== --}}
           <td style="width: 80px;"></td>
       </tr>
   </table>
   
   {{-- =========================================================
        INFORMACIÓN GENERAL DEL REPORTE
   ========================================================== --}}
    <table class="info-reporte">

        {{-- Primera fila --}}
        <tr>

            {{-- Nombre del departamento --}}
            <td>
                <strong>Departamento:</strong> 
                {{ $departamento->nombre }}
            </td>

            {{-- Período evaluado --}}
            <td align="right">
                <strong>Período:</strong> 
                {{ $periodo_texto }}
            </td>
        </tr>

        {{-- Segunda fila --}}
        <tr>

            {{-- Fecha de generación --}}
            <td>
                <strong>Generado el:</strong> 
                {{ date('d/m/Y ') }}
            </td>

            {{-- Año fiscal --}}
            <td align="right">
                <strong>Año Fiscal:</strong> 
                {{ $anio }}
            </td>
        </tr>
    </table>

    {{-- =========================================================
         TABLA PRINCIPAL DE EVALUACIONES
    ========================================================== --}}
    <table class="tabla">

        {{-- Encabezado de tabla --}}
        <thead>
            <tr>

                {{-- Actividad evaluada --}}
                <th width="60%">
                    Actividad / Proyecto Evaluado
                </th>

                {{-- Fecha --}}
                <th width="20%">
                    Fecha de Registro
                </th>

                {{-- Puntuación --}}
                <th width="20%" style="text-align: center;">
                    Puntuación
                </th>
            </tr>
        </thead>

       {{-- =========================================================
            CUERPO DE TABLA
       ========================================================== --}}
       <tbody>

       {{-- 
            Recorre todos los registros de evaluación 
       --}}
       @forelse($datos as $d)

          <tr>

              {{-- Nombre de actividad --}}
              <td>
                  {{ $d->actividad }}
              </td>

              {{-- Fecha formateada --}}
              <td>
                  {{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}
              </td>

              {{-- Resultado porcentual --}}
              <td align="center">
                  <strong>
                      {{ number_format($d->resultado, 2) }}%
                  </strong>
              </td>
          </tr>

        {{-- =========================================================
             SI NO EXISTEN REGISTROS
        ========================================================== --}}
        @empty

          <tr>

              {{-- Mensaje sin datos --}}
              <td colspan="3" align="center">
                  No se encontraron registros de evaluación para este período.
              </td>
           </tr>

        @endforelse
        </tbody>

        {{-- =========================================================
             PIE DE TABLA
        ========================================================== --}}
        <tfoot>

            <tr class="total-row">

                {{-- Texto del promedio --}}
                <td colspan="2" align="right">
                    RENDIMIENTO GLOBAL DEL DEPARTAMENTO:
                </td>

                {{-- Promedio general --}}
                <td align="center" style="color: #003366;">
                    {{ number_format($promedio_depto, 2) }}%
                </td>
            </tr>
        </tfoot>
    </table>

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

    {{-- =========================================================
         PIE DEL DOCUMENTO
    ========================================================== --}}
    <div class="footer">

        {{-- Texto institucional --}}
        Documento de carácter institucional - Generado por Sistema RRHH IHCI

        {{-- Nota aclaratoria --}}
        <div style="margin-top: 20px; font-style: italic; color: #555;">

          * Este reporte consolida todas las evaluaciones 
          (Autoevaluaciones y Evaluaciones de Jefe) 
          completadas en el periodo seleccionado.
        </div>
    </div>

</body>
</html>