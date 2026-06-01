<?php

/* =========================
   NAMESPACE Y USES
========================= */

namespace App\Http\Controllers; // Define el namespace del controlador

use App\Models\Solicitud;               // Modelo de solicitudes
use App\Models\Empleado;                // Modelo de empleados
use App\Models\PoliticaVacaciones;      // Modelo de políticas de vacaciones
use App\Models\TiempoCompensatorio;     // Modelo para registrar movimientos de tiempo compensatorio
use App\Models\SaldoTiempoCompensatorio; // Modelo para saldo de tiempo compensatorio
use Illuminate\Support\Facades\Mail;      // Envío de correos
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
    $rol = trim($user->rol->nombre);
    $empleadoId = $user->empleado->id ?? null;
    $miDepto = $user->empleado->departamento->nombre ?? null;
    $userEmail = $user->email;

    $query = Solicitud::with('empleado');

    // --- 1. FILTROS DE SEGURIDAD/VISIBILIDAD ---
    if ($rol === 'Administrador' || $rol === 'GTH') {
        // Ven todo
    } elseif ($rol === 'Jefe Inmediato') {
        $query->where('departamento', $miDepto);
    } else {
        $nombreUsuario = $user->empleado ? ($user->empleado->nombre . ' ' . $user->empleado->apellido) : null;
        $query->where(function($q) use ($nombreUsuario, $userEmail) {
            if (!empty(trim($nombreUsuario))) {
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
        ", [$empleadoId, $rol])
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
   * MÉTODO: store
   * ---------------------------------
   * Procesa y guarda una firma como imagen binaria.
   * Se utiliza cuando el usuario sube su firma.
   */
   public function show($id)
    {
      $solicitud = Solicitud::with('empleado', 'aprobaciones.firma')->findOrFail($id);
      $empleado = $solicitud->empleado;
      $fechaIngreso = \Carbon\Carbon::parse($empleado->fecha_ingreso);
      $ahora = now();
      $aniosCumplidos = floor($fechaIngreso->diffInYears($ahora));

       /* ==========================
          1. DERECHO GANADO
        ==========================*/
       $totalDerechoHistorico = 0;
       $tipoContrato = strtolower($empleado->tipo_contrato);

        if ($tipoContrato === 'permanente') {
          // AJUSTE: Sumamos los años cumplidos + el año que está cursando
          // Si tiene 0 años cumplidos, calcula el Año 1.
          // Si tiene 1 año cumplido, suma Año 1 + Año 2.
          $ciclosACalcular = $aniosCumplidos + 1; 

          for ($i = 1; $i <= $ciclosACalcular; $i++) {
              $anioBusqueda = ($i > 4) ? 4 : $i; 

              $politica = \App\Models\PoliticaVacaciones::whereRaw('LOWER(tipo_contrato) = ?', ['permanente'])
                ->where('anio_antiguedad', $anioBusqueda)
                ->first();

              if ($politica) {
                  $totalDerechoHistorico += $politica->dias_anuales;
                }
            }
        } else {
          $politica = \App\Models\PoliticaVacaciones::whereRaw('LOWER(tipo_contrato) = ?', [$tipoContrato])
            ->first();
           $totalDerechoHistorico = $politica ? $politica->dias_anuales : ($empleado->dias_vacaciones_anuales ?? 0);
        }

       /* ==========================
         2. DIAS CONSUMIDOS
        ==========================*/
       $diasConsumidosOficial = \DB::table('vacaciones')
        ->where('empleado_id', $empleado->id)
        ->where('estado', 'aprobado')
        ->sum('dias_aprobados') ?? 0;

        /* ==========================
       3. LÓGICA DE SALDOS
        ==========================*/
       $esVacaciones = str_contains(strtolower($solicitud->tipo), 'vacaciones');

        if ($solicitud->estado === 'aprobado' && $esVacaciones) {
         $saldoActual = $totalDerechoHistorico - ($diasConsumidosOficial - $solicitud->dias);
         $nuevoSaldo  = $totalDerechoHistorico - $diasConsumidosOficial;
        } else {
          $saldoActual = $totalDerechoHistorico - $diasConsumidosOficial;
          $nuevoSaldo  = $esVacaciones ? ($saldoActual - $solicitud->dias) : $saldoActual;
        }

       /* ==========================
         4. TIEMPO EXACTO
       ==========================*/
       $antiguedad = $fechaIngreso->diff($ahora);
       $tiempoExacto = '';
       if ($antiguedad->y > 0) {
          $tiempoExacto .= $antiguedad->y . ($antiguedad->y == 1 ? ' año' : ' años');
       }
       if ($antiguedad->m > 0) {
          if ($antiguedad->y > 0) $tiempoExacto .= ' y ';
             $tiempoExacto .= $antiguedad->m . ($antiguedad->m == 1 ? ' mes' : ' meses');
            }

           if ($antiguedad->y == 0 && $antiguedad->m == 0) {
             $tiempoExacto = 'Menos de un mes';
            }

            if(request()->ajax()) {
               return view('solicitudes.show', compact(
                 'solicitud', 'empleado', 'saldoActual', 'nuevoSaldo', 
                 'tiempoExacto', 'totalDerechoHistorico', 'esVacaciones'
                ));
            }
          return view('solicitudes.index');
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
 
        $rolUsuario = ($user->rol) ? $user->rol->nombre : 'SIN ROL';
        $deptoUser = $empleado ? $empleado->departamento_id : null;

        // Leemos los campos normales del formulario POST
        $estado = $request->input('estado', 'aprobado');
        $observacionesRecibidas = $request->input('observaciones', '');

        $esJefeDepto = \App\Models\Departamento::where('id', $deptoUser)
            ->where('jefe_empleado_id', $empleado->id)
            ->exists();

        /* ======================================
           ACCION: APROBADO
        ====================================== */
        if ($estado == 'aprobado') {

            $firmaActiva = \App\Models\Firma::where('empleado_id', $empleado->id)
                                ->where('activo', 1)
                                ->first();
            if (!$firmaActiva) {
                return redirect()->back()->with('error', 'No tienes firma activa.');
            }

            $rol_aprobacion = null;
            $paso = null;

            if ($esJefeDepto) {
                if ($solicitud->aprobaciones()->where('rol_nombre','Jefe Inmediato')->exists()) {
                    return redirect()->back()->with('error', 'La solicitud ya tiene la firma del Jefe Inmediato.');
                }
                $rol_aprobacion = 'Jefe Inmediato';
                $paso = 1;

            } elseif (strtolower($rolUsuario) == 'gth') {
                if (!$solicitud->aprobaciones()->where('rol_nombre','Jefe Inmediato')->exists()) {
                    return redirect()->back()->with('error', 'La solicitud requiere la firma del Jefe Inmediato antes que RRHH.');
                }
              
                if ($solicitud->aprobaciones()->where('rol_nombre','GTH')->exists()) {
                    return redirect()->back()->with('error', 'La solicitud ya tiene la firma de GTH.');
                }
                $rol_aprobacion = 'GTH';
                $paso = 2;

            } else {
                return redirect()->back()->with('error', 'No tienes permiso para firmar esta solicitud.');
            }

            $solicitud->aprobaciones()->create([
                'user_id'    => $user->id,
                'firma_id'   => $firmaActiva->id,
                'rol_nombre' => $rol_aprobacion,
                'paso_orden' => $paso,
            ]);

            if ($solicitud->aprobaciones()->whereIn('rol_nombre',['Jefe Inmediato','GTH'])->count() == 2) {
                $solicitud->estado = 'aprobado';
            } else {
                $solicitud->estado = 'en proceso';
            }

            $solicitud->save();

            return redirect()->back()->with('success', 'Firma aplicada correctamente.');
        }

        /* ======================================
           ACCION: RECHAZADO
        ====================================== */
        elseif ($estado == 'rechazado') {

            if ($esJefeDepto || strtolower($rolUsuario) == 'gth') {
                $solicitud->estado = 'rechazado';
                $solicitud->observaciones = $observacionesRecibidas;
                $solicitud->aprobaciones()->delete();
                $solicitud->save();
                
                return redirect()->back()->with('success', 'Solicitud rechazada con éxito.');
            }

            return redirect()->back()->with('error', 'No tienes autoridad para rechazar.');
        }

        return redirect()->back()->with('error', 'Acción inválida.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error interno: '.$e->getMessage());
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
          $empleadoId = $user->empleado->id;

          // Obtener departamento de la solicitud
          $departamento = \App\Models\Departamento::where('nombre', $solicitud->departamento)->first();
          if (!$departamento) {
             return response()->json(['success' => false, 'message' => 'Departamento no encontrado.'], 404);
            }

           // Verificar si el usuario es el jefe de ese departamento
           if ($departamento->jefe_empleado_id == $empleadoId) {
              $rolUsuario = 'Jefe Inmediato';
              $estado = 'en proceso';
            $paso = 1;
            $nombreParaRegistro = 'JEFE INMEDIATO';
            } 
               // Verificar si es GTH
           elseif ($user->rol->nombre == 'GTH') {
               // Validar que el jefe ya firmó
                $jefeFirmo = \App\Models\SolicitudAprobacion::where('solicitud_id', $id)
                ->where('paso_orden', 1)
                ->exists();

                if (!$jefeFirmo) {
                    return response()->json(['success' => false, 'message' => 'El jefe debe firmar primero.'], 403);
                }

                $rolUsuario = 'GTH';
                $estado = 'aprobado';
                $paso = 2;
                $nombreParaRegistro = 'GTH';
            } else {
              return response()->json(['success' => false, 'message' => 'Rol no autorizado para firmar esta solicitud.'], 403);
           }

           // Buscar la firma activa del usuario
          $firma = \App\Models\Firma::where('empleado_id', $empleadoId)
            ->where('activo', 1)
            ->first();

           if (!$firma) {
             return response()->json(['success' => false, 'message' => 'No tienes una firma activa.'], 422);
            }

          // Guardar la firma en solicitud_aprobaciones
            \App\Models\SolicitudAprobacion::updateOrCreate(
            [
                'solicitud_id' => $id,
                'paso_orden'   => $paso
            ],
            [
                'user_id' => $user->id,
                'firma_id' => $firma->id,
                'rol_nombre' => $nombreParaRegistro,
                'fecha_aprobacion' => now()
            ]
            );

           // Cambiar el estado de la solicitud
           $solicitud->estado = $estado;
           $solicitud->save();
  
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
          return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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

