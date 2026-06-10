<?php

// Define el espacio de nombres del controlador dentro de la aplicación
namespace App\Http\Controllers;
use Illuminate\Support\Str;              // centraliza toda la lógica de manipulación de texto en un solo lugar
use App\Models\Solicitud;               // Modelo de solicitudes
use App\Models\HoraExtra;                 // Modelo que gestiona los registros de horas extra (FT-GTH-002)
use App\Models\HoraExtraDetalle;                 // Modelo que gestiona los registros de horas extra (FT-GTH-002)
use App\Models\SaldoTiempoCompensatorio; // Modelo que maneja el saldo consolidado de tiempo compensatorio del empleado
use App\Models\TiempoCompensatorio;     // Modelo que almacena el historial de movimientos de tiempo compensatorio
use App\Models\Empleado;               // Modelo del empleado (datos personales y laborales)
use Illuminate\Http\Request;          // Clase para manejar las solicitudes HTTP (Request)
use Illuminate\Support\Facades\DB;    // Facade para ejecutar transacciones y consultas directas a la base de datos
use Illuminate\Support\Facades\Auth;  // Facade para obtener el usuario autenticado y controlar permisos
use Illuminate\Support\Facades\Mail;
use App\Mail\HorasExtraMail;
use App\Mail\HorasCargadasMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Notifications\NuevaHoraExtra;
use App\Services\RolService;
use App\Models\User;


class HoraExtraController extends Controller
{
    

    /**
     * Muestra la bandeja de entrada para jefes/administración
     * Solo registros con estado 'pendiente'
     */
    public function pendientes()
    {
        // Traemos las horas extras con la relación del empleado para ver su nombre
        $pendientes = HoraExtra::with('empleado')
            ->where('estado', 'pendiente')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('horas_extras.pendientes', compact('pendientes'));
    }

   
/**
 * Recupera la firma del jefe desde la tabla 'firmas' (LONGBLOB)
 * para mostrarla en el modal antes de guardar.
 */
public function getFirmaJefe()
{
    // Obtenemos el ID del empleado logueado (que es el jefe en este contexto)
    $empleadoId = auth()->user()->empleado->id;
    
    $firma = DB::table('firmas')
                ->where('empleado_id', $empleadoId)
                ->where('activo', 1)
                ->first();

    if ($firma && $firma->imagen_path) {
        return response()->json([
            'success' => true,
            // Importante: imagen_path es el binario directo de la DB
            'firma' => 'data:image/png;base64,' . base64_encode($firma->imagen_path)
        ]);
    }

    return response()->json([
        'success' => false, 
        'message' => 'No tienes una firma registrada en el sistema.'
    ]);
}

/**
 * Guarda la solicitud y genera el PDF con la firma recuperada de la BD.
 */
public function store(Request $request)
{
    $request->validate([
        'empleado_id' => 'required|exists:empleados,id',
        'fecha'       => 'required|array',
        'hora_inicio' => 'required|array',
        'hora_fin'    => 'required|array',
        'actividad'   => 'required|array',
    ]);

    try {
        DB::beginTransaction();

        // 1. Datos del empleado que solicita
        $empleadoSolicitante = \App\Models\Empleado::findOrFail($request->empleado_id);
        $nombreCompleto = $empleadoSolicitante->nombre . ' ' . $empleadoSolicitante->apellido;

        // 2. Calcular total de horas
        $totalMinutos = 0;
        foreach ($request->horas_trabajadas as $tiempo) {
            if (str_contains($tiempo, ':')) {
                [$h, $m] = explode(':', $tiempo);
                $totalMinutos += ($h * 60) + $m;
            }
        }
        $totalDecimal = round($totalMinutos / 60, 2);

        // 3. Crear cabecera de Hora Extra
        $horaExtra = \App\Models\HoraExtra::create([
            'empleado_id'        => $request->empleado_id,
            'nombre'             => $nombreCompleto, 
            'lugar'              => $request->lugar,
            'departamento'       => $request->departamento,
            'horas_acumuladas'   => $totalDecimal,
            'observaciones_jefe' => $request->observaciones_jefe,
            'codigo_formato'     => 'FT-GTH-002',
            'estado'             => ($request->accion === 'guardar_y_firmar') ? 'proceso' : 'pendiente',
            'paso_actual'        => ($request->accion === 'guardar_y_firmar') ? 1 : 0
        ]);

        // 4. Guardar detalle de actividades (las 5 columnas)
        $detalleData = ['hora_extra_id' => $horaExtra->id];
        for ($i = 0; $i < 5; $i++) {
            $n = $i + 1;
            $hIni = $request->hora_inicio[$i] ?? null;
            $hFin = $request->hora_fin[$i] ?? null;

            $detalleData["fecha{$n}"]        = $request->fecha[$i] ?? null;
            $detalleData["hora_inicio{$n}"]  = $hIni ? date('h:i', strtotime($hIni)) : null;
            $detalleData["hora_fin{$n}"]     = $hFin ? date('h:i', strtotime($hFin)) : null;
            $detalleData["actividad{$n}"]    = $request->actividad[$i] ?? null;
            
            if ($hIni && $hFin) {
                $detalleData["periodo_inicio{$n}"] = date('A', strtotime($hIni));
                $detalleData["periodo_fin{$n}"]    = date('A', strtotime($hFin));
            }
        }
        \App\Models\HoraExtraDetalle::create($detalleData);

        // 5. Recuperar la firma de la BD para el PDF
        $firmaJefeRaw = null;
        if ($request->accion === 'guardar_y_firmar') {
            $jefeId = auth()->user()->empleado->id;
            $firmaRecord = DB::table('firmas')
                             ->where('empleado_id', $jefeId)
                             ->where('activo', 1)
                             ->first();
            
            if ($firmaRecord) {
                $firmaJefeRaw = $firmaRecord->imagen_path;
            }
        }

        // 6. Generar PDF con la firma binaria
        $pdf = Pdf::loadView('pdf.formato_horas_extra', [
            'solicitud'  => $horaExtra,
            'detalle'    => \App\Models\HoraExtraDetalle::find($detalleData['hora_extra_id']),
            'firma_jefe' => $firmaJefeRaw // Pasamos el LONGBLOB directamente
        ]);

        $pdfContent = $pdf->output();

        // 7. Enviar Correo
        if ($empleadoSolicitante->email) {
            Mail::to($empleadoSolicitante->email)->send(
                new \App\Mail\HorasCargadasMail($horaExtra, $pdfContent, [])
            );
        }

        DB::commit();

        try {
            $this->notificarSiguienteResponsable($horaExtra);
        } catch (\Exception $e) {
            \Log::error("La solicitud se guardó, pero falló la notificación: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Solicitud registrada y firmada correctamente.');

        

    } catch (\Exception $e) {
        DB::rollBack();
        return "Error crítico: " . $e->getMessage();
    }
}

 
/**
 * Procesa el total de horas para la BD
 */
private function sumarHorasReloj($arreglo) {
    $minutosTotales = 0;
    foreach($arreglo as $tiempo) {
        if(str_contains($tiempo, ':')) {
            [$h, $m] = explode(':', $tiempo);
            $minutosTotales += ($h * 60) + $m;
        }
    }
    return round($minutosTotales / 60, 2);
}

   /**
    * Mapea las filas a las columnas fijas fecha1...fecha5
    */
    private function guardarDetalleFijo($id, $request) {
      $data = ['hora_extra_id' => $id];
      for ($i = 0; $i < 5; $i++) {
         $n = $i + 1;
         $data["fecha{$n}"]        = $request->fecha[$i] ?? null;
         $data["hora_inicio{$n}"]  = $request->hora_inicio[$i] ?? null;
         $data["hora_fin{$n}"]     = $request->hora_fin[$i] ?? null;
         $data["actividad{$n}"]    = $request->actividad[$i] ?? null;
         // Agrega periodos si tu tabla los requiere obligatoriamente
        }
      \App\Models\HoraExtraDetalle::create($data);
    }

    /**
     * Procesa la validación (Aprobación/Rechazo) desde la bandeja de pendientes
     */
    public function validar(Request $request, $id)
    {
     // 1. Obtener datos iniciales
     $solicitud = DB::table('horas_extras')->where('id', $id)->first();
     $userLogueado = auth()->user();
     $empLog = $userLogueado->empleado;

     // 2. Cargar configuración de flujos
     $pasosFlujo = DB::table('flujo_firmas_config')->where('activo', 1)->orderBy('orden', 'asc')->get()->values();
     $idx = (int)$solicitud->paso_actual; 
     $configPasoActual = $pasosFlujo[$idx] ?? null;

      // 3. VALIDACIÓN DE TURNO (Solo se hace al principio)
      $autorizadoId = $this->obtenerJefeId($configPasoActual, $solicitud, $idx);
      if (!$empLog || $empLog->id != $autorizadoId) {
          return back()->with('error', 'No es tu turno de firmar.');
        }

       if ($request->accion == 'aprobado') {
          $dataUpdate = [];
        
          // --- LIMPIEZA DE NOMBRE PARA LÓGICA DE NEGOCIO ---
          $nombrePasoRaw = strtoupper($configPasoActual->nombre_paso ?? '');
          $nombrePasoLimpio = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $nombrePasoRaw);

           // Lógica de Dirección
           if (str_contains($nombrePasoLimpio, 'DIRECCION') || str_contains($nombrePasoLimpio, 'EJECUTIVA')) {
              // 1. Obtener valores numéricos
              $horasPagadas = $request->filled('horas_pagadas') ? (float)$request->horas_pagadas : 0;
              $totalSolicitud = $request->filled('total_calculado_vista') ? (float)$request->total_calculado_vista : 0;
               $resultadoAcumular = max(0, $totalSolicitud - $horasPagadas);

              // 2. DEFINIR LA VARIABLE (Esto es lo que faltaba)
             // Buscamos 'observaciones_jefe' en el formulario, si está vacío ponemos un texto por defecto
             // Lógica de Observación: Solo se marca si hay pago
              $observacionManual = $request->input('observaciones_jefe');
    
                if ($horasPagadas > 0) {
                  // Formateamos las horas para que se vean como 3:00 en el texto
                    $horasFormateadas = str_replace('.', ':', number_format($horasPagadas, 2));
                   $observacion = "Se autoriza el pago de " . $horasFormateadas . " horas. " . $observacionManual;
                } else {
                  $observacion = $observacionManual; // Si es 0, queda solo lo que escribió el jefe (o vacío)
                }

               // 3. ACTUALIZACIÓN EN BASE DE DATOS
              \DB::table('horas_extras')
              ->where('id', $solicitud->id)
              ->update([
                 'horas_pagadas'      => $horasPagadas,
                 'horas_acumuladas'   => $resultadoAcumular,
                  'observaciones_jefe' => $observacion,  
                  'updated_at'         => now()
                ]);

               // Actualizamos el objeto en memoria
              $solicitud->horas_pagadas = $horasPagadas;
              $solicitud->horas_acumuladas = $resultadoAcumular;
              $solicitud->observaciones_jefe = $observacion;
            }

           // Lógica de GTH
           if (str_contains($nombrePasoLimpio, 'GESTION') || str_contains($nombrePasoLimpio, 'TALENTO')) {
              $dataUpdate['aprobado_por'] = $userLogueado->id;
              $dataUpdate['fecha_aprobacion'] = now(); // Marca el cierre final del proceso
            }

           // 4. CALCULAR AVANCE
           $nuevoPaso = $idx;
           for ($i = 0; $i < 5; $i++) {
              $nuevoPaso++;
              $configSig = $pasosFlujo[$nuevoPaso] ?? null;
              if ($configSig && $this->obtenerJefeId($configSig, $solicitud, $nuevoPaso) == $empLog->id) {
                  continue;
                } else { break; }
            }

            $esFinal = ($nuevoPaso >= $pasosFlujo->count());

            // 5. ACTUALIZAR BASE DE DATOS
            DB::table('horas_extras')->where('id', $id)->update(array_merge($dataUpdate, [
              'paso_actual' => $nuevoPaso,
              'estado' => $esFinal ? 'aprobado' : 'proceso',
               'updated_at' => now()
            ]));

            // 6. LÓGICA DE CORREO (Solo si es el paso final)
            if ($esFinal) {
                try {
                  $soliMail = \App\Models\HoraExtra::with(['empleado', 'detalles'])->find($id);
                 $pasosConfigurados = DB::table('flujo_firmas_config')->where('activo', 1)->orderBy('id', 'asc')->get();

                 // Si no es el final, notificamos al siguiente

                   $solicitudActualizada = \App\Models\HoraExtra::find($id);
                   $this->notificarSiguienteResponsable($solicitudActualizada);

                   $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('horas_extras.formato_pdf', [
                      'solicitud' => $soliMail,
                      'pasosConfigurados' => $pasosConfigurados,
                      'controller' => $this 
                    ])->setPaper('letter', 'portrait');

                    $pdfContent = $pdf->output();
                    $correoDestino = $soliMail->empleado->user->email ?? $soliMail->empleado->email;

                    if ($correoDestino) {
                       \Mail::to($correoDestino)->send(new \App\Mail\HorasExtraMail($soliMail, $pdfContent, $pasosConfigurados));
                    }

                    $this->actualizarSaldo($soliMail);

                    return back()->with('success', 'Solicitud finalizada y correo enviado al empleado.');
                } catch (\Exception $e) {
                   // Si el correo falla, igual notificamos que la firma se guardó
                   return back()->with('success', 'Firma guardada, pero hubo un detalle con el correo: ' . $e->getMessage());
                }
            }

            return back()->with('success', 'Firma registrada correctamente.');
        }
    }
    
    // ESTA ES LA FUNCIÓN ÚNICA QUE RESUME TODO
    // Cambia private por public
    public function obtenerJefeId($configPaso, $solicitud, $indice)
    {
       if (!$configPaso) return null;

          $nombrePaso = strtoupper(trim($configPaso->nombre_paso));

          // 1. JEFE INMEDIATO
    if ($indice === 0 || str_contains($nombrePaso, 'INMEDIATO')) {

        $empleado = DB::table('empleados')
            ->where('id', $solicitud->empleado_id)
            ->first();

        return $empleado->departamento_id
            ? DB::table('departamentos')->where('id', $empleado->departamento_id)->value('jefe_empleado_id')
            : null;
    }

    // 2. ACTIVIDAD
    if (str_contains($nombrePaso, 'ACTIVIDAD')) {

        return DB::table('departamentos')
            ->where('nombre', 'LIKE', '%' . trim($solicitud->departamento) . '%')
            ->value('jefe_empleado_id');
    }

    // 3. DIRECCIÓN (CORRECTO CON LARAVEL MODEL)
    if (str_contains($nombrePaso, 'DIRECCION') || str_contains($nombrePaso, 'EJECUTIVA')) {

        $director = User::whereHas('rol', function ($q) {
                $q->whereIn('nombre', [
                    'Dirección',
                    'Direccion',
                    'Dirección Ejecutiva',
                    'Director'
                ]);
            })
            ->with('empleado')
            ->first();

        return $director?->empleado?->id;
    }

    // 4. GTH
    if (str_contains($nombrePaso, 'TALENTO') || str_contains($nombrePaso, 'GTH')) {

        $gth = User::whereHas('rol', function ($q) {
                $q->where('nombre', 'GTH');
            })
            ->with('empleado')
            ->first();

        return $gth?->empleado?->id;
    }

    return null;
}



   // Función auxiliar para no repetir código del saldo
    private function actualizarSaldo($registro) {
      $horas = (float) $registro->horas_trabajadas;
      TiempoCompensatorio::create([
         'empleado_id' => $registro->empleado_id,
         'tipo_movimiento' => 'entrada',
         'horas' => $horas,
         'descripcion' => 'Horas Extras Aprobadas',
         'autorizado_por' => Auth::id(),
         'fecha_movimiento' => now(),
        ]);

        $saldo = SaldoTiempoCompensatorio::firstOrCreate(['empleado_id' => $registro->empleado_id]);
       $totalAprobado = HoraExtra::where('empleado_id', $registro->empleado_id)->where('estado', 'aprobado')->sum('horas_acumuladas');
       $saldo->horas_acumuladas = $totalAprobado;
       $saldo->horas_disponibles = $totalAprobado - $saldo->horas_usadas;
       $saldo->save();
    }

    // Gestión de las horas
   public function gestion(Request $request)
   {
      $user = auth()->user();
      $empleadoLogueado = $user->empleado;

      // 1. ROLES Y PERMISOS
      $rol = trim($user->rol->nombre ?? '');

      $rolNormalizado = Str::of($rol)
      ->ascii()
      ->lower()
      ->squish()
      ->toString();

       $esAdmin = $rolNormalizado === 'administrador' || $user->hasRole('Administrador');

       $esGTH = in_array($rol, ['GTH', 'Gestión de Talento Humano']);

       $esDireccion = in_array($rolNormalizado, ['direccion', 'direccion ejecutiva'], true)
       || $user->hasRole('Dirección')
       || $user->hasRole('Direccion')
       || $user->hasRole('Dirección Ejecutiva')
       || $user->hasRole('Direccion Ejecutiva');

       $esAdminOGTH = $esAdmin || $esGTH || $esDireccion;

       $departamentoQueDirige = \App\Models\Departamento::where(
          'jefe_empleado_id',
           $empleadoLogueado->id
        )->first();

       $esJefe = !is_null($departamentoQueDirige);

       // 2. DETERMINAR ALCANCE DE DEPARTAMENTOS
       $queryDepto = \App\Models\Departamento::with([
         'empleados' => function ($q) {
              $q->orderBy('nombre');
            }
        ]);

      if ($esAdminOGTH) {
          $departamentos = $queryDepto->orderBy('nombre')->get();
        } elseif ($esJefe) {
            $departamentos = $queryDepto
           ->where('id', $departamentoQueDirige->id)
           ->get();
        } else {
          $departamentos = collect();
        }

       $departamentos = $departamentos->map(function ($depto) {
         $depto->setRelation('empleados', $depto->empleados->values());
         return $depto;
       });

       // 3. FILTRO DE BÚSQUEDA
       $empleadoId = $request->input('empleado_id');
       $esBusquedaActiva = !empty($empleadoId);

       // 4. EMPLEADO A CONSULTAR
       $empleadoAConsultar = null;

       if ($esBusquedaActiva) {

          if ($esAdminOGTH) {
               $empleadoAConsultar = \App\Models\Empleado::find($empleadoId);

            } elseif ($esJefe) {

              $esDeSuEquipo = \App\Models\Empleado::where('id', $empleadoId)
              ->where('departamento_id', $departamentoQueDirige->id)
              ->exists();

              if ($esDeSuEquipo) {
                  $empleadoAConsultar = \App\Models\Empleado::find($empleadoId);
                }
            }
        }

       // 5. CÁLCULOS DE SALDOS
       $totalAcumuladas = 0;
        $totalPagadas = 0;
       $totalConsumidas = 0;
       $totalPendientesSolicitud = 0;

        $historialAcumuladas = collect();
        $historialPagadas = collect();
        $historialConsumidas = collect();
        $solicitudesPendientes = collect();

        if ($empleadoAConsultar) {

           $correoEmpleado = $empleadoAConsultar->correo ?? $empleadoAConsultar->email;

           $totalAcumuladas = \App\Models\HoraExtra::where('empleado_id', $empleadoAConsultar->id)
           ->where('estado', 'aprobado')
           ->sum('horas_acumuladas');

          $totalPagadas = \App\Models\HoraExtra::where('empleado_id', $empleadoAConsultar->id)
          ->where('estado', 'aprobado')
          ->sum('horas_pagadas');

           $solicitudesAprobadas = \App\Models\Solicitud::where('correo', $correoEmpleado)
           ->where('tipo', 'A CUENTA DE TIEMPO COMPENSATORIO')
           ->where('estado', 'aprobado')
           ->get();

           foreach ($solicitudesAprobadas as $solicitud) {
             $totalConsumidas += ((float) $solicitud->dias * 8.0)
               + (float) $solicitud->horas;
            }

           $solicitudesPendientes = \App\Models\Solicitud::where('correo', $correoEmpleado)
           ->where('tipo', 'A CUENTA DE TIEMPO COMPENSATORIO')
           ->whereIn('estado', ['pendiente', 'en proceso'])
           ->get();

           foreach ($solicitudesPendientes as $solicitud) {
              $totalPendientesSolicitud += ((float) $solicitud->dias * 8.0)
              + (float) $solicitud->horas;
            }

           $historialAcumuladas = \App\Models\HoraExtra::with('detalles')
           ->where('empleado_id', $empleadoAConsultar->id)
           ->where('estado', 'aprobado')
           ->get();

           $historialPagadas = \App\Models\HoraExtra::where('empleado_id', $empleadoAConsultar->id)
           ->where('estado', 'aprobado')
           ->where('horas_pagadas', '>', 0)
           ->get();

           $historialConsumidas = \App\Models\Solicitud::where('correo', $correoEmpleado)
           ->where('tipo', 'A CUENTA DE TIEMPO COMPENSATORIO')
           ->where('estado', 'aprobado')
           ->get();
        }

       $saldoRestante = $totalAcumuladas - $totalConsumidas;

       // 6. TABLA DE REGISTROS
       $queryRegistros = \App\Models\HoraExtra::with(['empleado', 'detalles']);

       if ($request->filled('buscar')) {
          $searchTerm = $request->buscar;

          $queryRegistros->whereHas('empleado', function ($subQ) use ($searchTerm) {
              $subQ->where('nombre', 'LIKE', "%{$searchTerm}%")
              ->orWhere('apellido', 'LIKE', "%{$searchTerm}%");
           });
        }

       if (!$esAdminOGTH) {
           if ($esJefe) {

               $queryRegistros->whereHas(
                  'empleado',
                  function ($q) use ($departamentoQueDirige, $empleadoLogueado) {
                      $q->where('departamento_id', $departamentoQueDirige->id)
                      ->orWhere('id', $empleadoLogueado->id);
                    }
                );

            } else {

              $queryRegistros->where('empleado_id', $empleadoLogueado->id);
            }
        }

       $solicitudes = $queryRegistros
       ->orderByRaw("
          CASE
              WHEN estado = 'pendiente' THEN 1
              WHEN estado = 'proceso' THEN 1
              WHEN estado = 'aprobado' THEN 2
              WHEN estado = 'rechazado' THEN 2
              ELSE 3
            END ASC
       ")
       ->orderBy('created_at', 'desc')
       ->paginate(5)
       ->withQueryString();

       $pasosConfigurados = \DB::table('flujo_firmas_config')
      ->where('activo', 1)
      ->orderBy('id', 'asc')
      ->get();


      return view('horas_extras.gestion', compact(
          'solicitudes','pasosConfigurados', 'totalAcumuladas', 'totalPagadas','totalConsumidas',
          'totalPendientesSolicitud','saldoRestante', 'esAdmin','esGTH','esJefe','esDireccion',
         'departamentos','empleadoAConsultar','esBusquedaActiva','historialAcumuladas','historialConsumidas',
         'historialPagadas', 'solicitudesPendientes'
       ));

    }


    /*
    |--------------------------------------------------------------------------
    | Notificar responsable fijo (usuario ID 1)
    |--------------------------------------------------------------------------
    | Este método envía una notificación de prueba o respaldo al usuario
    | con ID 1. Incluye registros en el log para facilitar la depuración
    | y captura cualquier excepción que ocurra durante el proceso.
    |--------------------------------------------------------------------------
    */
    protected function notificarSiguienteResponsable($horaExtra)
    {
    try {

        // Buscar usuario con ID 1
        $usuario = \App\Models\User::find(1);

        // Validar que exista
        if (!$usuario) {
            \Log::error('NO EXISTE USUARIO 1');
            return;
        }

        // Registrar información antes de enviar la notificación
        \Log::info('ANTES DEL NOTIFY', [
            'user_id' => $usuario->id,
            'hora_extra_id' => $horaExtra->id,
        ]);

        // Enviar notificación
        $usuario->notify(new \App\Notifications\NuevaHoraExtra($horaExtra));

        // Confirmar envío en log
        \Log::info('DESPUES DEL NOTIFY');

    } catch (\Throwable $e) {

        // Registrar cualquier error ocurrido durante el envío
        \Log::error('ERROR EN NOTIFICACION HORAS EXTRA', [
            'mensaje' => $e->getMessage(),
            'archivo' => $e->getFile(),
            'linea' => $e->getLine(),
        ]);
    }
    }

    /*
    |--------------------------------------------------------------------------
    | Notificar jefe del departamento
    |--------------------------------------------------------------------------
    | Obtiene el empleado relacionado con la hora extra, localiza su
    | departamento, identifica al jefe asignado y le envía una
    | notificación al usuario asociado.
    |--------------------------------------------------------------------------
    */
    private function notificarJefeDepartamento($horaExtra)
    {
    // Obtener empleado asociado a la solicitud
    $empleado = $horaExtra->empleado;

    // Validar que exista y tenga departamento asignado
    if (!$empleado || !$empleado->departamento_id) {
        return;
    }

    // Buscar departamento del empleado
    $departamento = \App\Models\Departamento::find($empleado->departamento_id);

    // Validar que exista el departamento y tenga jefe asignado
    if (!$departamento || !$departamento->jefe_id) {
        return;
    }

    // Obtener empleado que actúa como jefe
    $jefeEmpleado = \App\Models\Empleado::find($departamento->jefe_id);

    // Validar que exista y tenga usuario relacionado
    if (!$jefeEmpleado || !$jefeEmpleado->user) {
        return;
    }

    // Enviar notificación al jefe del departamento
    $jefeEmpleado->user->notify(
        new \App\Notifications\NuevaHoraExtra($horaExtra)
    );
    }
}

