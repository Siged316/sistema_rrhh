<?php

// Define el espacio de nombres del controlador dentro de la aplicación
namespace App\Http\Controllers;

use App\Models\HoraExtra;                 // Modelo que gestiona los registros de horas extra (FT-GTH-002)
use App\Models\SaldoTiempoCompensatorio; // Modelo que maneja el saldo consolidado de tiempo compensatorio del empleado
use App\Models\TiempoCompensatorio;     // Modelo que almacena el historial de movimientos de tiempo compensatorio
use App\Models\Empleado;               // Modelo del empleado (datos personales y laborales)
use Illuminate\Http\Request;          // Clase para manejar las solicitudes HTTP (Request)
use Illuminate\Support\Facades\DB;    // Facade para ejecutar transacciones y consultas directas a la base de datos
use Illuminate\Support\Facades\Auth;  // Facade para obtener el usuario autenticado y controlar permisos
use Illuminate\Support\Facades\Mail;  // Importa el *facade* Mail de Laravel, que permite enviar correos fácilmente
use App\Mail\HorasExtraMail;          // Importa la clase Mailable HorasExtraMail que definiste en App\Mail
                                      // Esta clase contiene la plantilla, asunto y datos del correo que se va a enviar


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
     * Registra una nueva solicitud de horas extras (Formato FT-GTH-002)
     */
   public function store(Request $request)
   {
     // 1. Limpiamos el nombre que viene del Forms (quitar espacios extras)
     $nombreDesdeForms = trim($request->input('nombre'));

     // --- PRUEBA DE DEBUG (Descomenta la línea de abajo si quieres ver qué llega de Forms) ---
     // dd($nombreDesdeForms); 

       // 2. Buscamos al empleado con una comparación insensible a mayúsculas/minúsculas
       $empleado = \App\Models\Empleado::where(function($query) use ($nombreDesdeForms) {
         $query->where(\DB::raw("LOWER(CONCAT(nombre, ' ', apellido))"), 'LIKE', '%' . strtolower($nombreDesdeForms) . '%')
              ->orWhere(\DB::raw("LOWER(nombre)"), 'LIKE', '%' . strtolower($nombreDesdeForms) . '%');
       })->first();

       // 3. Asignamos el ID si lo encontramos
       $empleadoId = $empleado ? $empleado->id : null;

       // 4. Si encontramos el ID, lo inyectamos al request para la validación
       if ($empleadoId) {
          $request->merge(['empleado_id' => $empleadoId]);
        }

       // 5. Validación: Si el ID sigue siendo NULL, aquí se detendrá y te dirá que es requerido
       $request->validate([
         'empleado_id'       => 'required|exists:empleados,id',
         'lugar'             => 'required|string|max:255',
         'solicitado_a'      => 'required|string|max:255',
         'cargo_solicitante' => 'required|string|max:255',
         'tipo_pago'         => 'required|in:pago,acumular',
         'fecha'             => 'required|array|min:1',
         'horas_trabajadas.*'=> 'required|numeric|min:0.1',
        ], [
         'empleado_id.required' => "No pudimos vincular el nombre '$nombreDesdeForms' con ningún empleado en el sistema."
        ]);

       try {
         DB::beginTransaction();

         $empleado = \App\Models\Empleado::with('departamento')->findOrFail($request->empleado_id);
         $totalHorasCalculadas = array_sum($request->horas_trabajadas);
         $horasParaAcumular = ($request->tipo_pago === 'acumular') ? $totalHorasCalculadas : 0;

         // 6. Crear CABECERA
         $horaExtra = HoraExtra::create([
             'empleado_id'        => $empleado->id,
             'nombre'             => $nombreDesdeForms,
             'lugar'              => $request->lugar,
             'solicitado_a'       => $request->solicitado_a,
             'cargo_solicitante'  => $request->cargo_solicitante,
             'horas_trabajadas'   => $totalHorasCalculadas,
             'horas_acumuladas'   => $horasParaAcumular,
             'observaciones_jefe' => $request->observaciones ?? null,
             'codigo_formato'     => 'FT-GTH-002',
             'estado'             => 'pendiente',
            ]);

           // 7. Crear DETALLE
           foreach ($request->fecha as $i => $fecha) {
                HoraExtraDetalle::create([
                 'hora_extra_id'   => $horaExtra->id,
                 'fecha'           => $fecha,
                 'hora_inicio'     => $request->hora_inicio[$i],
                 'hora_fin'        => $request->hora_fin[$i],
                 'horas_trabajadas'=> $request->horas_trabajadas[$i],
                 'actividad'       => $request->actividad[$i],
                 'empleado_id'     => $empleado->id,
                 'departamento'    => $empleado->departamento->nombre ?? 'N/A', 
                 'lugar'           => $request->lugar,
                ]);
            }

           DB::commit();
          return redirect()->back()->with('success', 'Solicitud registrada correctamente para ' . $empleado->nombre);

        } catch (\Exception $e) {
         DB::rollBack();
         return redirect()->back()->with('error', 'Error crítico: ' . $e->getMessage());
        }
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

    // Obrener el jefe
    public function obtenerJefeId($configPaso, $solicitud, $indice)
    {
      if (!$configPaso) return null;
         $nombrePaso = strtoupper($configPaso->nombre_paso);

           // 1 y 2. (Manten igual la lógica de JEFE INMEDIATO y ACTIVIDAD que ya te funciona)
          if ($indice === 0 || str_contains($nombrePaso, 'INMEDIATO')) {
              $empleado = DB::table('empleados')->where('id', $solicitud->empleado_id)->first();
               if ($empleado && $empleado->departamento_id) {
                  $depto = DB::table('departamentos')->where('id', $empleado->departamento_id)->first();
                  return $depto ? $depto->jefe_empleado_id : null;
                }
            } 

           if (str_contains($nombrePaso, 'ACTIVIDAD')) {
              $deptoDestino = DB::table('departamentos')
               ->where('nombre', 'LIKE', '%' . trim($solicitud->departamento) . '%')
               ->first();
              return $deptoDestino ? $deptoDestino->jefe_empleado_id : null;
            } 

           // 3. DIRECCIÓN: Buscamos al usuario que TIENE el rol, no al que está LOGUEADO
           if (str_contains($nombrePaso, 'DIRECCION') || str_contains($nombrePaso, 'EJECUTIVA')) {
              // Buscamos el primer usuario que tenga el rol de Dirección o Director
              $director = \App\Models\User::all()->filter(function($u) {
                  return $u->hasRole('Dirección') || $u->hasRole('Director');
                })->first();

               return $director ? $director->empleado_id : null;
            }

           // 4. GTH
          if (str_contains($nombrePaso, 'TALENTO') || str_contains($nombrePaso, 'GTH')) {
              $gth = \App\Models\User::all()->filter(function($u) {
                  return $u->hasRole('GTH');
                })->first();

                return $gth ? $gth->empleado_id : null;
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

    // Getion de firmas 
   public function gestion()
  {
    $user = auth()->user();
    $empleadoLogueado = $user->empleado;

    // 1. Cargamos las solicitudes con sus relaciones
    $query = HoraExtra::with(['empleado.departamento', 'detalles']);

    // 2. LÓGICA DE ACCESO TOTAL (Dirección, GTH, Admin)
    $tieneAccesoTotal = false;

    if ($user->rol) {
      // Convertimos el nombre a Mayúsculas y quitamos tildes para comparar
      $nombreRol = strtoupper(str_replace(['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'], 
      ['A', 'E', 'I', 'O', 'U', 'A', 'E', 'I', 'O', 'U'], 
        $user->rol->nombre));

      if ($user->hasRole('Administrador') || 
        $user->hasRole('GTH') || 
        str_contains($nombreRol, 'DIRECCION')) {
          $tieneAccesoTotal = true;
        }
    }

    // 3. APLICAR FILTROS
    if (!$tieneAccesoTotal) {
        $query->where(function($q) use ($empleadoLogueado) {
            // El empleado común solo ve las suyas
            $q->where('empleado_id', $empleadoLogueado->id);

            // Si es jefe, también ve las de su departamento
            $deptoDondeEsJefe = \DB::table('departamentos')
                ->where('jefe_empleado_id', $empleadoLogueado->id)
                ->first();

            if ($deptoDondeEsJefe) {
                $q->orWhereHas('empleado', function($subQuery) use ($deptoDondeEsJefe) {
                    $subQuery->where('departamento_id', $deptoDondeEsJefe->id);
                });
            }
        });
    }
 
   // 4. Obtener resultados con ORDEN JERÁRQUICO
    $solicitudes = $query->orderBy('paso_actual', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->get();
    
    // Pasos para las "bolitas" de la vista
    $pasosConfigurados = \DB::table('flujo_firmas_config')
                            ->where('activo', 1)
                            ->orderBy('id', 'asc')
                            ->get();

    return view('horas_extras.gestion', compact('solicitudes', 'pasosConfigurados'));
  }
}