<?php

/* =========================
   NAMESPACE Y USES
========================= */

namespace App\Http\Controllers; // Define el namespace del controlador
use Illuminate\Support\Str;              // centraliza toda la lógica de manipulación de texto en un solo lugar
use App\Models\Solicitud;               // Modelo de solicitudes
use App\Models\Empleado;                // Modelo de empleados
use App\Models\Departamento;
use App\Models\Firma;
use App\Models\PoliticaVacaciones;      // Modelo de políticas de vacaciones
use App\Models\TiempoCompensatorio;     // Modelo para registrar movimientos de tiempo compensatorio
use App\Models\SaldoTiempoCompensatorio; // Modelo para saldo de tiempo compensatorio
use Illuminate\Support\Facades\Mail;      // Envío de correos
use App\Mail\SolicitudActualizada;
use App\Mail\CambioTipoSolicitudMail;     // Mailable para notificar cambio de tipo
use Illuminate\Support\Facades\DB;      // Facade para operaciones de base de datos y transacciones
use Illuminate\Http\Request;            // Clase para capturar requests HTTP
use Illuminate\Support\Facades\Auth;    // Facade para autenticación
use Carbon\Carbon;                      // Biblioteca para manejo de fechas

/* =========================
   CLASE CONTROLADOR
========================= */

class SolicitudController extends Controller
{
     /**
   * MÉTODO: index
   * ---------------------------------
   * Lista solicitudes con filtros por rol + orden inteligente.
   */
   public function index(Request $request)
    {
      $user = Auth::user();
      $rol = trim($user->rol->nombre ?? '');
      $rolNormalizado = Str::of($rol)
      ->ascii()
      ->lower()
      ->squish()
      ->toString();
      $rolNormalizado = str_replace(
         ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
         ['a', 'e', 'i', 'o', 'u', 'n'],
         $rolNormalizado
        );

      $empleadoId = $user->empleado->id ?? null;
      $miDepto = $user->empleado->departamento->nombre ?? null;
      $userEmail = $user->email;

      $query = Solicitud::with('empleado');

     

         // Si quieres evitar este error y mejorar el rendimiento, 
       // lo ideal es enviar a la vista el objeto ya relacionado.
      // --- 1. FILTROS DE SEGURIDAD/VISIBILIDAD ---

       if (in_array($rolNormalizado, ['administrador', 'gth', 'direccion'], true)) {
          // Ven todo sin excepciones
        } elseif ($rolNormalizado === 'jefe inmediato') {
          $query->where('departamento', $miDepto);
        } else {
          $nombreUsuario = $user->empleado
          ? ($user->empleado->nombre . ' ' . $user->empleado->apellido)
          : null;

          $query->where(function($q) use ($nombreUsuario, $userEmail) {
              if (!empty(trim($nombreUsuario ?? ''))) {
                  $q->where('solicitudes.nombre', 'LIKE', '%' . trim($nombreUsuario) . '%');
                }

               $q->orWhere('solicitudes.correo', $userEmail);
           });
        }

       // --- 2. FILTROS DEL BUSCADOR Y FECHAS ---
       if ($request->filled('search')) {
          $query->where('solicitudes.nombre', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->filled('mes')) {
           $query->where(function($q) use ($request) {
              $q->whereMonth('solicitudes.fecha_inicio', $request->mes)
              ->orWhereMonth('solicitudes.fecha_fin', $request->mes);
            });
        }

        if ($request->filled('fecha_rango')) {
           $input = trim($request->fecha_rango);
           try {
              if (str_contains($input, ' to ')) {
                  $partes = explode(' to ', $input);
                  $inicio = \Carbon\Carbon::createFromFormat('d/m/Y', trim($partes[0]))->format('Y-m-d');
                  $fin = \Carbon\Carbon::createFromFormat('d/m/Y', trim($partes[1]))->format('Y-m-d');
                  $query->where(function($q) use ($inicio, $fin) {
                       $q->whereBetween('solicitudes.fecha_inicio', [$inicio, $fin])
                      ->orWhereBetween('solicitudes.fecha_fin', [$inicio, $fin]);
                    });
                } else {
                  $fechaUnica = \Carbon\Carbon::createFromFormat('d/m/Y', $input)->format('Y-m-d');
                  $query->where('solicitudes.fecha_inicio', '<=', $fechaUnica)
                      ->where('solicitudes.fecha_fin', '>=', $fechaUnica);
                }
            } catch (\Exception $e) {
              \Log::error("Error en fecha: " . $e->getMessage());
            }
        }

       // --- 3. BLINDAJE DE LA CONSULTA Y PAGINACIÓN ---
       try {
          $solicitudes = $query->leftJoin('departamentos', function($join) {
              $join->on('solicitudes.departamento', '=', \DB::raw('departamentos.nombre COLLATE utf8mb4_unicode_ci'));
           })
          ->select('solicitudes.*')
          ->orderByRaw("
            CASE 
                WHEN (departamentos.jefe_empleado_id = ? AND (SELECT COUNT(*) FROM solicitud_aprobaciones WHERE solicitud_id = solicitudes.id) = 0) THEN 1
                WHEN (? = 'GTH' AND (SELECT COUNT(*) FROM solicitud_aprobaciones WHERE solicitud_id = solicitudes.id AND paso_orden = 1) = 1 
                      AND (SELECT COUNT(*) FROM solicitud_aprobaciones WHERE solicitud_id = solicitudes.id AND paso_orden = 2) = 0) THEN 1
                WHEN (SELECT COUNT(*) FROM solicitud_aprobaciones WHERE solicitud_id = solicitudes.id) = 1 
                      AND solicitudes.estado != 'rechazado' THEN 2
                WHEN (SELECT COUNT(*) FROM solicitud_aprobaciones WHERE solicitud_id = solicitudes.id) >= 2 
                      OR solicitudes.estado = 'aprobado' THEN 3
                ELSE 4
            END ASC
            ", [$empleadoId, $rolNormalizado])
            ->orderBy('solicitudes.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        } catch (\Exception $e) {
           // En caso de error técnico, retornamos un paginador vacío para que el index no falle
          \Log::error("Error en index de solicitudes: " . $e->getMessage());
          $solicitudes = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
          
        }

       return view('solicitudes.index', compact('solicitudes'));
    }

   /**
   * MÉTODO: show
   * ---------------------------------
   * Procesa y guarda una firma como imagen binaria.
   * Se utiliza cuando el usuario sube su firma.
   */
public function show($id)

{

    // 1. Inicialización de variables

    $empleado = null; $saldoActual = 0; $nuevoSaldo = 0; $tiempoExacto = 'N/A';

    $totalDerechoHistorico = 0; $esVacaciones = false;



    try {

        $solicitud = \App\Models\Solicitud::findOrFail($id);

       

        // 2. Búsqueda robusta (la que soluciona tu problema de datos)

        $partes = explode(' ', trim($solicitud->nombre));

        $empleado = \App\Models\Empleado::where('nombre', 'LIKE', '%' . ($partes[0] ?? '') . '%')

                      ->where('apellido', 'LIKE', '%' . end($partes) . '%')

                      ->first();



        if ($empleado) {

            $fechaIngreso = \Carbon\Carbon::parse($empleado->fecha_ingreso);

            $ahora = now();

            $aniosCumplidos = floor($fechaIngreso->diffInYears($ahora));

            $tipoContrato = strtolower($empleado->tipo_contrato ?? '');

            $esVacaciones = str_contains(strtolower($solicitud->tipo), 'vacaciones');



            // 3. Lógica de cálculo original (Ciclos de antigüedad)

            if ($tipoContrato === 'permanente') {

                $ciclosACalcular = $aniosCumplidos + 1;

                for ($i = 1; $i <= $ciclosACalcular; $i++) {

                    $anioBusqueda = ($i > 4) ? 4 : $i;

                    $politica = \App\Models\PoliticaVacaciones::whereRaw('LOWER(tipo_contrato) = ?', ['permanente'])

                                ->where('anio_antiguedad', $anioBusqueda)->first();

                    if ($politica) $totalDerechoHistorico += $politica->dias_anuales;

                }

            } else {

                $politica = \App\Models\PoliticaVacaciones::whereRaw('LOWER(tipo_contrato) = ?', [$tipoContrato])->first();

                $totalDerechoHistorico = $politica ? $politica->dias_anuales : ($empleado->dias_vacaciones_anuales ?? 0);

            }



            // 4. Días consumidos (Usando tu tabla recuperada)

            $diasConsumidosOficial = (int) \DB::table('vacaciones')

                ->where('empleado_id', $empleado->id)

                ->where('estado', 'aprobado')

                ->sum('dias_aprobados');



            // 5. Lógica de saldos

            if ($solicitud->estado === 'aprobado' && $esVacaciones) {

                $saldoActual = $totalDerechoHistorico - ($diasConsumidosOficial - $solicitud->dias);

                $nuevoSaldo  = $totalDerechoHistorico - $diasConsumidosOficial;

            } else {

                $saldoActual = $totalDerechoHistorico - $diasConsumidosOficial;

                $nuevoSaldo  = $esVacaciones ? ($saldoActual - $solicitud->dias) : $saldoActual;

            }



            // 6. Tiempo Exacto

            $antiguedad = $fechaIngreso->diff($ahora);

            $tiempoExacto = ($antiguedad->y > 0 ? $antiguedad->y . ' años ' : '') . ($antiguedad->m > 0 ? $antiguedad->m . ' meses' : '');

            if ($tiempoExacto == '') $tiempoExacto = 'Menos de un mes';

        }



        // 7. Renderizado

      return view('solicitudes.show', compact(

        'solicitud', 'empleado', 'saldoActual', 'nuevoSaldo',

        'tiempoExacto', 'totalDerechoHistorico', 'esVacaciones'

    ));



    } catch (\Exception $e) {

        return response()->json(['html' => '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>']);

    }

} 


    /**
    * MÉTODO: store
    * ---------------------------------
    * Procesa y guarda una firma como imagen binaria.
    * Se utiliza cuando el usuario sube su firma.
    */
    public function store(Request $request)
    {
      $request->validate([
         'empleado_id' => 'required|exists:empleados,id',
         'foto_firma' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Máximo 2MB
        ]);

        $file = $request->file('foto_firma');
    
        // 1. Crear una instancia de imagen desde el archivo subido
       // Esto funciona para JPG y PNG por igual
       $extension = strtolower($file->getClientOriginalExtension());
      if ($extension == 'png') {
         $src = imagecreatefrompng($file->getRealPath());
        } else {
          $src = imagecreatefromjpeg($file->getRealPath());
        }

       // 2. Redimensionar (Opcional pero recomendado para ahorrar espacio en BD)
      // Mantendremos un ancho máximo de 400px para que se vea bien en el PDF
      $width = imagesx($src);
      $height = imagesy($src);
      $newWidth = 400;
      $newHeight = ($height / $width) * $newWidth;
      $tmp = imagecreatetruecolor($newWidth, $newHeight);

       // Mantener transparencia si es PNG
       imagealphablending($tmp, false);
       imagesavealpha($tmp, true);
    
       imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

      // 3. Capturar el contenido en un buffer para guardarlo como binario
      ob_start();
      // Lo guardamos siempre como PNG para mantener la mejor calidad y transparencia
      imagepng($tmp); 
      $binarioFirma = ob_get_clean();

      // 4. Guardar en la base de datos
      \App\Models\Firma::create([
         'empleado_id' => $request->empleado_id,
         'imagen_path' => $binarioFirma, // Guardamos el binario limpio
         'activo' => 1
        ]);

       // Limpiar memoria
       imagedestroy($src);
       imagedestroy($tmp);


      return back()->with('success', 'Firma procesada y guardada correctamente.');
    }

    /**
    * MÉTODO: procesar
    * ---------------------------------
    * Gestiona aprobación o rechazo de solicitudes.
    * Controla flujo: Jefe → GTH.
    */
    public function procesar(Request $request, $id)
    {
    try {
        $solicitud = Solicitud::with('empleado')->findOrFail($id);
        $user = auth()->user();
        $empleado = $user->empleado;

        $rolUsuario = ($user->rol) ? strtolower($user->rol->nombre) : 'sin rol';
        $deptoUser = $empleado ? $empleado->departamento_id : null;

        $estado = $request->input('estado', 'aprobado');
        $observacionesRecibidas = $request->input('observaciones', '');

        $esJefeDepto = \App\Models\Departamento::where('id', $deptoUser)
            ->where('jefe_empleado_id', $empleado->id)
            ->exists();

        if ($estado == 'aprobado') {
            $firmaActiva = \App\Models\Firma::where('empleado_id', $empleado->id)->where('activo', 1)->first();
            
            if (!$firmaActiva) {
                return response()->json(['success' => false, 'message' => 'No tienes firma activa.'], 403);
            }

          if (strtolower($rolUsuario) == 'gth') {
               // Lógica de GTH
              if (!$solicitud->aprobaciones()->where('rol_nombre','Jefe Inmediato')->exists()) {
                   return response()->json(['success' => false, 'message' => 'Falta la firma del Jefe.'], 422);
                }
    
                if ($solicitud->aprobaciones()->where('rol_nombre','GTH')->exists()) {
                    return response()->json(['success' => false, 'message' => 'Ya firmaste como GTH.'], 422);
                }

               $rol_aprobacion = 'GTH';
               $paso = 2;

            } elseif ($esJefeDepto) {
              // Lógica de Jefe
              if ($solicitud->aprobaciones()->where('rol_nombre','Jefe Inmediato')->exists()) {
                   return response()->json(['success' => false, 'message' => 'Ya tiene firma del Jefe.'], 422);
                }
                $rol_aprobacion = 'Jefe Inmediato';
                $paso = 1;

            } else {
               return response()->json(['success' => false, 'message' => 'No tienes permisos.'], 403);
            }

            $solicitud->aprobaciones()->create([
                'user_id'    => $user->id,
                'firma_id'   => $firmaActiva->id,
                'rol_nombre' => $rol_aprobacion,
                'paso_orden' => $paso,
            ]);

            //Asignar el ID del usuario que aprueba (para tener registro del último aprobador)
            $solicitud->aprobado_por = $user->id;

            //Si es el paso 2 (GTH), guardamos la fecha de aprobación final
           if ($paso == 2) {
              $solicitud->fecha_aprobacion = now(); // O date('Y-m-d')
            }

            //Actualizar el estado
            $solicitud->estado = ($solicitud->aprobaciones()->count() >= 2) ? 'aprobado' : 'en proceso';
            $solicitud->save();

            // Si ya está aprobado, enviamos el correo
            // En lugar de buscar al empleado, usa directamente el correo de la solicitud
         if (!empty($solicitud->correo)) {
             \Mail::to($solicitud->correo)
             ->send(new \App\Mail\SolicitudActualizada($solicitud, $solicitud->estado));
           }
            $solicitud = $solicitud->fresh();

            $firmaJefe = $solicitud->aprobaciones()
            ->with('firma')
            ->where('paso_orden', 1)
            ->first();

            $firmaGTH = $solicitud->aprobaciones()
            ->with('firma')
            ->where('paso_orden', 2)
            ->first();

           return response()->json([
               'success' => true,
               'message' => 'Firma aplicada correctamente.',

             'jefe_html' => $firmaJefe
              ? '<img src="data:image/png;base64,'.base64_encode(
                  is_resource($firmaJefe->firma->imagen_path)
                  ? stream_get_contents($firmaJefe->firma->imagen_path)
                  : $firmaJefe->firma->imagen_path
                ).'" style="max-height:70px;">'
                : '<span style="color:#ccc;">PENDIENTE JEFE</span>',

               'gth_html' => $firmaGTH
               ? '<img src="data:image/png;base64,'.base64_encode(
                  is_resource($firmaGTH->firma->imagen_path)
                  ? stream_get_contents($firmaGTH->firma->imagen_path)
                   : $firmaGTH->firma->imagen_path
                ).'" style="max-height:70px;">'
                : '<span style="color:#ccc;">PENDIENTE GTH</span>',

            ]);
        }

        // Si es rechazado
        if ($esJefeDepto || strtolower($rolUsuario) == 'gth') {

           $solicitud->observaciones = $observacionesRecibidas;
           $solicitud->estado = 'rechazado';
           $solicitud->aprobado_por = $user->id;
           $solicitud->save();

           // Enviamos el correo de rechazo
            if (!empty($solicitud->correo)) {
             \Mail::to($solicitud->correo)
             ->send(new \App\Mail\SolicitudActualizada($solicitud, $solicitud->estado));
           }

           return response()->json([
              'success' => true,
              'message' => 'Solicitud rechazada.'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No tienes autoridad.'], 403);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
    }
    }


    /**
    * MÉTODO: accionar
    * ---------------------------------
    * Maneja firmas de forma más directa (flujo simplificado).
    */

    public function accionar(Request $request, $id)
    {
    try {
        $solicitud = \App\Models\Solicitud::findOrFail($id);
        $user = auth()->user();
        $empleado = $user->empleado;

        // 1. Identificación de roles
        $esGth = (strtoupper(trim($user->rol->nombre ?? '')) == 'GTH');
        $departamento = \App\Models\Departamento::where('nombre', $solicitud->departamento)->first();
        $esJefe = ($departamento && $departamento->jefe_empleado_id == $empleado->id);

        // 2. Definición de reglas según el rol
        if ($esGth) {
            // Verificar si el paso 1 (Jefe) ya existe
            if (!$solicitud->aprobaciones()->where('paso_orden', 1)->exists()) {
                return response()->json(['success' => false, 'message' => 'El jefe debe firmar primero.'], 403);
            }
            $paso = 2;
            $nombreParaRegistro = 'GTH';
            $estadoSiguiente = 'aprobado';
        } elseif ($esJefe) {
            $paso = 1;
            $nombreParaRegistro = 'JEFE INMEDIATO';
            $estadoSiguiente = 'en proceso';
        } else {
            return response()->json(['success' => false, 'message' => 'No tienes permisos de firma.'], 403);
        }

        // 3. Verificación de existencia del paso (Evitar firmas duplicadas)
        if ($solicitud->aprobaciones()->where('paso_orden', $paso)->exists()) {
            return response()->json(['success' => false, 'message' => "El paso $paso ya ha sido completado."]);
        }

        // 4. Guardar firma
        $firma = \App\Models\Firma::where('empleado_id', $empleado->id)->where('activo', 1)->first();
        if (!$firma) {
            return response()->json(['success' => false, 'message' => 'Firma no encontrada.'], 422);
        }

        \App\Models\SolicitudAprobacion::create([
            'solicitud_id' => $id,
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'rol_nombre' => $nombreParaRegistro,
            'paso_orden' => $paso,
            'fecha_aprobacion' => now()
        ]);

        $solicitud->estado = $estadoSiguiente;
        $solicitud->save();
        
        
        return response()->json([
            'success' => true, 
            'message' => 'Firma registrada con éxito.',
          'html' => '<span class="text-success font-weight-bold">✓ Firma aplicada correctamente</span>'
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
    }
    }

    /**
    * MÉTODO: actualizar estado
    * ---------------------------------
    * Gestiona aprobación o rechazo de solicitudes.
    * Controla flujo: Jefe → GTH.
    */
    public function actualizarEstado(Request $request, $id)
    {
     $solicitud = Solicitud::findOrFail($id);
     $nuevoEstado = $request->input('estado'); // 'aprobada' o 'rechazada'

      // 1. Actualizar estado
      $solicitud->estado = $nuevoEstado;
       $solicitud->save();

       // 2. Enviar correo al solicitante
    
       Mail::to($solicitud->user->email)->send(new SolicitudActualizada($solicitud, $nuevoEstado));
          return response()->json(['message' => 'Estado actualizado y correo enviado']);
    }



    /**
    * MÉTODO: rectificarTipo
   * ---------------------------------
   * Cambia el tipo de solicitud y ajusta saldos (crítico en RRHH)
   */

    public function rectificarTipo(Request $request, $id)
    {
        $request->validate([
            'nuevo_tipo' => 'required|in:vacaciones,sin_goce,tiempo_compensatorio',
            'motivo' => 'required|string|min:5'
        ]);

        $solicitud = Solicitud::with('empleado')->findOrFail($id);
        $tipoAnterior = $solicitud->tipo;
        $horas = (float) $solicitud->horas;

        DB::transaction(function () use ($solicitud, $tipoAnterior, $horas, $request) {
            if ($tipoAnterior === 'tiempo_compensatorio') {
                TiempoCompensatorio::where('solicitud_id', $solicitud->id)->delete();
                $saldo = SaldoTiempoCompensatorio::where('empleado_id', $solicitud->empleado_id)->first();
                if ($saldo) {
                    $saldo->horas_usadas -= $horas;
                    $totalConsumido = $saldo->horas_usadas + $saldo->horas_pagadas;
                    if ($totalConsumido > $saldo->horas_acumuladas) {
                        $saldo->horas_debe = $totalConsumido - $saldo->horas_acumuladas;
                        $saldo->horas_disponibles = 0;
                    } else {
                        $saldo->horas_debe = 0;
                        $saldo->horas_disponibles = $saldo->horas_acumuladas - $totalConsumido;
                    }
                    $saldo->save();
                }
            }
            $solicitud->tipo = $request->nuevo_tipo;
            $solicitud->detalles .= "\n[RECTIFICACIÓN]: " . $request->motivo;
            $solicitud->save();
        });

        if ($solicitud->empleado && $solicitud->empleado->email) {
            Mail::to($solicitud->empleado->email)
                ->send(new CambioTipoSolicitudMail($solicitud, $tipoAnterior, $request->motivo));
        }

        return back()->with('success', 'Cambios aplicados correctamente.');
    }
    
 
    /**
    * MÉTODO: update
    * ---------------------------------
    * Actualizar datos.
    */

    public function update(Request $request, $id)
    {
        $solicitud = Solicitud::findOrFail($id);
        if ($solicitud->estado !== 'pendiente') {
            return back()->with('error', 'No se puede editar una solicitud ya procesada.');
        }

        $request->validate([
            'tipo' => 'required|in:vacaciones,tiempo_compensatorio,sin_goce',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'motivo' => 'required|string|min:5',
        ]);

        $solicitud->update([
            'tipo' => $request->tipo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'detalles' => $request->motivo,
        ]);

        return back()->with('success', 'Solicitud actualizada.');
    }

    /**
    * MÉTODO: updateDetalles
    * ---------------------------------
    * Guarda los detalles editables (contenteditable).
    */
    public function updateDetalles(Request $request, $id)
    {
        try {
            $solicitud = Solicitud::findOrFail($id);
            $solicitud->detalles = $request->input('detalles');
            $solicitud->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Esto nos dirá qué falló si hay un error de base de datos
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
    * MÉTODO: calculoPermanente
    * ---------------------------------
    * Calcula:
    * - Derecho histórico
    * - Días gozados
    * - Saldo actual
    */
    public function calculoPermanente($empleadoId)
    {
        $empleado = Empleado::findOrFail($empleadoId);
        $fechaIngreso = Carbon::parse($empleado->fecha_ingreso);
        $tipoContrato = strtolower($empleado->tipo_contrato);
        $aniosCumplidos = floor($fechaIngreso->diffInYears(now()));

        $totalDerechoHistorico = 0;
        if ($tipoContrato === 'permanente') {
            for ($i = 1; $i <= $aniosCumplidos; $i++) {
                $anioBusqueda = ($i > 4) ? 4 : $i;
                $politica = PoliticaVacaciones::where('tipo_contrato', 'permanente')
                    ->where('anio_antiguedad', $anioBusqueda)->first();
                if ($politica) $totalDerechoHistorico += $politica->dias_anuales;
            }
        } else {
            $politica = PoliticaVacaciones::where('tipo_contrato', $empleado->tipo_contrato)->first();
            $totalDerechoHistorico = $politica ? $politica->dias_anuales : ($empleado->dias_vacaciones_anuales ?? 10);
        }

        $solicitudes = Solicitud::where('empleado_id', $empleado->id)
            ->where('tipo', 'vacaciones')
            ->where('estado', 'aprobado')
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        $totalGozados = $solicitudes->sum('dias');
        $saldo = $totalDerechoHistorico - $totalGozados;

        return view('solicitudes.calculo_modal', compact(
            'empleado', 'totalDerechoHistorico', 'totalGozados', 'saldo', 'solicitudes', 'aniosCumplidos'
        ));
    }
} // <--- LLAVE FINAL ÚNICA

