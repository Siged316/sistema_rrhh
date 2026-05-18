<?php

namespace App\Http\Controllers; // Namespace donde se encuentra el controlador dentro de la aplicación

use Illuminate\Support\Facades\DB; // Facade para realizar consultas directas a la base de datos
use Illuminate\Http\Request; // Clase para manejar peticiones HTTP y formularios

use App\Models\Departamento; // Modelo de departamentos
use App\Models\Empleado; // Modelo de empleados
use App\Models\HoraExtra; // Modelo de horas extras
use App\Models\Solicitud; // Modelo de solicitudes/permisos

use Barryvdh\DomPDF\Facade\Pdf; // Librería para generar archivos PDF

use App\Exports\ExportarDesempenoDepto; // Exportación Excel de desempeño por departamento
use App\Exports\IndividualExport; // Exportación Excel de reportes individuales
use App\Exports\CompensatorioExport; // Exportación Excel de compensatorios
use App\Exports\PermisosExport; // Exportación Excel de permisos y vacaciones

use Maatwebsite\Excel\Facades\Excel; // Facade para generar y descargar archivos Excel

class ReporteController extends Controller
{
    public function index() {
        return view('informes.index');
    }

    public function departamento() {
        $departamentos = Departamento::all();
        $anios = DB::table('asignacion_evaluaciones')
                ->selectRaw('YEAR(created_at) as anio')
                ->distinct()
                ->orderBy('anio', 'desc')
                ->pluck('anio');
        
        return view('informes.desempeno_depto', compact('departamentos', 'anios'));
    }

    // --- SECCIÓN Desempeño por depto. ---
  public function generarPdf(Request $request) {
    $depto_id = $request->departamento_id;
    $anio = $request->anio;
    $periodo = $request->periodo;
    $mes = $request->mes;
    $departamento = Departamento::find($depto_id);

    // --- NUEVO: Obtener la firma activa ---
    $firma = DB::table('firmas')->where('activo', 1)->first();

    $query = DB::table('asignacion_evaluaciones as ae')
        ->join('empleados as e', 'ae.empleado_id', '=', 'e.id') 
        ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')
        ->select(
            DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"), 
            DB::raw("MAX(ae.created_at) as fecha"), 
            DB::raw("AVG(ae.puntuacion_total) as resultado")
        )
        ->where('e.departamento_id', $depto_id) 
        ->whereYear('ae.created_at', $anio)
        ->groupBy('actividad');

    if ($periodo == 'mensual' && $mes) {
        $query->whereMonth('ae.created_at', $mes);
        $periodo_texto = "Mensual (" . $mes . ")";
    } else {
        $periodo_texto = "Anual Acumulado";
    }

    $datos = $query->get();
    $promedio_depto = $datos->avg('resultado');

    // --- AGREGAR 'firma' al compact ---
    $pdf = Pdf::loadView('informes.pdf_desempeno', compact('datos', 'departamento', 'anio', 'periodo_texto', 'promedio_depto', 'firma'));
    return $pdf->stream("Reporte_Desempeño_{$departamento->nombre}.pdf");
  }

   public function generarExcel(Request $request) {
    $depto_id = $request->departamento_id;
    $anio = $request->anio;
    $departamento = Departamento::find($depto_id);
    
    // Buscar la firma activa
    $firma = DB::table('firmas')->where('activo', 1)->first();

    $nombreArchivo = "Desempeño_" . str_replace(' ', '_', $departamento->nombre) . "_{$anio}.xlsx";

    // Pasar la firma como quinto parámetro
    return Excel::download(
        new ExportarDesempenoDepto($depto_id, $anio, $request->periodo, $request->mes, $firma), 
        $nombreArchivo
    );
   }

    public function validarDatos(Request $request) {
        $query = DB::table('asignacion_evaluaciones as ae')
            ->join('empleados as e', 'ae.empleado_id', '=', 'e.id')
            ->whereYear('ae.created_at', $request->anio);

        if ($request->filled('empleado_id')) {
            $query->where('ae.empleado_id', $request->empleado_id);
        } elseif ($request->filled('departamento_id')) {
            $query->where('e.departamento_id', $request->departamento_id);
        }

        if ($request->periodo === 'mensual' && $request->filled('mes')) {
            $query->whereMonth('ae.created_at', $request->mes);
        }

        $total = $query->count();
        return response()->json(['count' => $total, 'existe' => $total > 0]);
    }

    // --- SECCIÓN Desempeño Individual por Depto. ---
    public function individual() {
        $empleados = Empleado::orderBy('nombre', 'asc')->get();
        $departamentos = Departamento::orderBy('nombre', 'asc')->get(); 
        $anios = DB::table('asignacion_evaluaciones')
                ->selectRaw('YEAR(created_at) as anio')
                ->distinct()
                ->orderBy('anio', 'desc')
                ->pluck('anio');

        return view('informes.individual', compact('empleados', 'departamentos', 'anios'));
    }

    public function generarIndividualPdf(Request $request) {
        $empleado = Empleado::findOrFail($request->empleado_id);
        $query = DB::table('asignacion_evaluaciones as ae')
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')
            ->select(DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"), 'ae.created_at as fecha', 'ae.puntuacion_total as resultado')
            ->where('ae.empleado_id', $request->empleado_id)
            ->whereYear('ae.created_at', $request->anio);

        if ($request->periodo == 'mensual' && $request->mes) {
            $query->whereMonth('ae.created_at', $request->mes);
        }
 
        $firma = DB::table('firmas')->where('activo', 1)->first();

        $datos = $query->get();
        $promedio_global = $datos->avg('resultado') ?? 0;

        $pdf = Pdf::loadView('informes.pdf_individual', ['datos' => $datos, 'empleado' => $empleado, 'anio' => $request->anio, 'promedio_global' => $promedio_global, 'firma' => $firma]);
        return $pdf->stream("Evaluacion_{$empleado->nombre}_{$empleado->apellido}.pdf");
    }

    public function generarIndividualExcel(Request $request) {
    $empleado = Empleado::findOrFail($request->empleado_id);
    
    $query = DB::table('asignacion_evaluaciones as ae')
        ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')
        ->select(DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"), 'ae.created_at as fecha', 'ae.puntuacion_total as resultado')
        ->where('ae.empleado_id', $request->empleado_id)
        ->whereYear('ae.created_at', $request->anio);

    $periodo_texto = ($request->periodo == 'mensual' && $request->mes) ? "Mensual (" . $request->mes . ")" : "Anual Acumulado";
    
    if ($request->periodo == 'mensual' && $request->mes) {
        $query->whereMonth('ae.created_at', $request->mes);
    }

    $datos = $query->get();

    // 1. Buscamos la firma activa
    $firma = DB::table('firmas')->where('activo', 1)->first();

    // 2. Pasamos los 6 argumentos: agregamos $firma al final
    return Excel::download(
        new IndividualExport(
            $empleado, 
            $datos, 
            $periodo_texto, 
            $request->anio, 
            $datos->avg('resultado') ?? 0, 
            $firma 
        ), 
        "Reporte_Individual_{$empleado->apellido}.xlsx"
    );
    }

    private function obtenerNombreMes($mes) {
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        return $meses[$mes] ?? '';
    }

    // --- SECCIÓN COMPENSATORIO ---

    public function compensatorio() {
        $departamentos = Departamento::all();
        $empleados = Empleado::all(); 
        $anios = DB::table('horas_extras')->selectRaw('YEAR(created_at) as anio')
            ->union(DB::table('solicitudes')->selectRaw('YEAR(fecha_inicio) as anio'))
            ->distinct()->orderBy('anio', 'desc')->pluck('anio');

        return view('informes.compensatorio', compact('departamentos', 'empleados', 'anios'));
    }

    public function validarCompensatorio(Request $request) {
        $query = HoraExtra::where('empleado_id', $request->empleado_id)->where('estado', 'aprobado');
        $request->periodo === 'anual' ? $query->whereYear('created_at', $request->anio) : $query->whereYear('created_at', $request->anio)->whereMonth('created_at', $request->mes);
        return response()->json(['count' => $query->count()]);
    }

    public function pdfCompensatorio(Request $request) {
        $empleado = Empleado::with(['departamento'])->findOrFail($request->empleado_id);
        $movimientos = HoraExtra::where('empleado_id', $empleado->id)->where('estado', 'aprobado')->whereYear('created_at', $request->anio)
            ->when($request->mes, fn($q) => $q->whereMonth('created_at', $request->mes))->get();

        $nombreExacto = $empleado->nombre . ' ' . $empleado->apellido;
        $solicitudes = Solicitud::where('nombre', $nombreExacto)->where('estado', 'aprobado')->where('tipo', 'A cuenta de tiempo compensatorio')
            ->whereYear('fecha_inicio', $request->anio)->when($request->mes, fn($q) => $q->whereMonth('fecha_inicio', $request->mes))->get();

        $todosLosRegistros = $movimientos->concat($solicitudes)->sortBy(fn($item) => $item->fecha ?? $item->fecha_inicio);
        return Pdf::loadView('informes.pdf_compensatorio', ['empleado' => $empleado, 'anio' => $request->anio, 'todosLosRegistros' => $todosLosRegistros])
            ->setPaper('letter', 'portrait')->stream("Reporte_Compensatorio.pdf");
    }

    public function excelCompensatorio(Request $request) {
        $empleado = Empleado::with(['departamento'])->findOrFail($request->empleado_id);
        $firmaData = DB::table('firmas')->where('empleado_id', $request->empleado_id)->where('activo', 1)->value('imagen_path');
        
        // Reutilizamos la lógica de filtrado... (simplificado para brevedad)
        $nombreExacto = $empleado->nombre . ' ' . $empleado->apellido;
        $movimientos = HoraExtra::where('empleado_id', $empleado->id)->where('estado', 'aprobado')->whereYear('created_at', $request->anio)->get();
        $solicitudes = Solicitud::where('nombre', $nombreExacto)->where('estado', 'aprobado')->where('tipo', 'A cuenta de tiempo compensatorio')->get();
        
        $firma = DB::table('firmas')->where('activo', 1)->first();

        $data = ['empleado' => $empleado, 'anio' => $request->anio, 'todosLosRegistros' => $movimientos->concat($solicitudes), 'firma' => $firma];
        return Excel::download(new CompensatorioExport($data), "Reporte_Compensatorio_{$empleado->apellido}.xlsx");
    }

    // --- SECCIÓN PERMISOS Y VACACIONES ---

    public function permisos() {
        $departamentos = Departamento::all();
        $empleados = Empleado::orderBy('nombre', 'asc')->get();
        $anios = DB::table('solicitudes')->where('tipo', '!=', 'A cuenta de tiempo compensatorio')
            ->selectRaw('YEAR(fecha_inicio) as anio')->distinct()->orderBy('anio', 'desc')->pluck('anio');

        if ($anios->isEmpty()) { $anios = collect([date('Y')]); }
        return view('informes.permisos', compact('departamentos', 'empleados', 'anios'));
    }

    public function validarPermisos(Request $request) {
        $empleado = Empleado::findOrFail($request->empleado_id);
        $nombreCompleto = $empleado->nombre . ' ' . $empleado->apellido;

        $query = Solicitud::where('nombre', $nombreCompleto)
            ->where('estado', 'aprobado')
            ->whereYear('fecha_inicio', $request->anio);

        $tipo = strtolower($request->tipo_solicitud);

        if ($tipo === 'vacaciones') {
            $query->whereRaw('UPPER(tipo) LIKE ?', ['%VACACIONES%']);
        } else {
            $query->whereRaw('UPPER(tipo) NOT LIKE ?', ['%VACACIONES%'])
                  ->whereRaw('UPPER(tipo) NOT LIKE ?', ['%COMPENSATORIO%']);
        }

        if ($request->filled('mes')) {
            $query->whereMonth('fecha_inicio', $request->mes);
        }

        return response()->json(['count' => $query->count()]);
    }

    public function generarPermisosPdf(Request $request) {
     $empleado = Empleado::findOrFail($request->empleado_id);
     $nombreCompleto = $empleado->nombre . ' ' . $empleado->apellido;

     $query = Solicitud::where('nombre', $nombreCompleto)
        ->where('estado', 'aprobado')
        ->whereYear('fecha_inicio', $request->anio);

     // --- LÓGICA DINÁMICA DE FILTRADO ---
     $tipoRequest = strtolower($request->tipo_solicitud);

     if (str_contains($tipoRequest, 'vacaciones')) {
         // Si se pide vacaciones, buscamos coincidencias
         $query->whereRaw('UPPER(tipo) LIKE ?', ['%VACACIONES%']);
         $tituloReporte = "HISTORIAL DE VACACIONES";
       } else {
         // Si son permisos, excluimos vacaciones y compensatorios
         $query->whereRaw('UPPER(tipo) NOT LIKE ?', ['%VACACIONES%'])
              ->whereRaw('UPPER(tipo) NOT LIKE ?', ['%COMPENSATORIO%']);
         $tituloReporte = "HISTORIAL DE PERMISOS";
        }

      // Filtro de mes si aplica
      if ($request->filled('mes')) {
          $query->whereMonth('fecha_inicio', $request->mes);
        }

       $solicitudes = $query->orderBy('fecha_inicio', 'asc')->get();

       // --- CÁLCULO DE TOTALES (Simplificado con sum) ---
       $totalDias = $solicitudes->sum('dias');
       $totalHoras = $solicitudes->sum('horas');

      // Generar el PDF
      return Pdf::loadView('informes.pdf_permisos', [
          'empleado'    => $empleado,
          'solicitudes' => $solicitudes,
          'anio'        => $request->anio,
           'titulo'      => $tituloReporte, // Pasa el título dinámico a la vista
           'mes'         => $request->filled('mes') ? $this->obtenerNombreMes($request->mes) : "Anual Acumulado",
          'total_dias'  => $totalDias,
         'total_horas' => $totalHoras
         ])->setPaper('letter', 'portrait')
          ->stream("{$tituloReporte}_{$empleado->apellido}.pdf");
    }

    public function exportarPermisosExcel(Request $request) {
        $empleado = Empleado::findOrFail($request->empleado_id);
        $nombreCompleto = $empleado->nombre . ' ' . $empleado->apellido;
        
        $query = Solicitud::where('nombre', $nombreCompleto)
            ->where('estado', 'aprobado')
            ->whereYear('fecha_inicio', $request->anio);

        $tipo = strtolower($request->tipo_solicitud);

        if ($tipo === 'vacaciones') {
            $query->whereRaw('UPPER(tipo) LIKE ?', ['%VACACIONES%']);
            $tituloReporte = "Historial de Vacaciones";
        } else {
            $query->whereRaw('UPPER(tipo) NOT LIKE ?', ['%VACACIONES%'])
                  ->whereRaw('UPPER(tipo) NOT LIKE ?', ['%COMPENSATORIO%']);
            $tituloReporte = "Historial de Permisos";
        }

        if ($request->filled('mes')) {
            $query->whereMonth('fecha_inicio', $request->mes);
        }

        $solicitudes = $query->orderBy('fecha_inicio', 'asc')->get();
        $firma = DB::table('firmas')->where('activo', 1)->first();

        $data = [
            'empleado'    => $empleado,
            'solicitudes' => $solicitudes,
            'anio'        => $request->anio,
            'titulo'      => $tituloReporte,
            'mes'         => $request->filled('mes') ? $this->obtenerNombreMes($request->mes) : 'Anual Acumulado',
            'total_dias'  => $solicitudes->sum('dias'),
            'total_horas' => $solicitudes->sum('horas'),
            'firmaBlob'   => $firma ? $firma->imagen_path : null
        ];

        $nombreArchivo = ($tipo === 'vacaciones') ? "Vacaciones" : "Permisos";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PermisosExport($data), 
            "{$nombreArchivo}_{$empleado->apellido}.xlsx"
        );
    }

    // --- SECCIÓN GRÁFICAS ---

    public function indexGraficas()
    {
    // Aquí puedes obtener los datos necesarios para las gráficas
    // Por ahora, solo retornamos la vista en la nueva carpeta
    return view('informes.graficas.index');
    }

    // ==========================================
   //  GRÁFICA DEPTO
  // ==========================================
    public function graficaDepto() {
    $departamentos = Departamento::all();
    
    // Obtenemos los años de las evaluaciones para el filtro
    $anios = DB::table('asignacion_evaluaciones')
            ->selectRaw('YEAR(created_at) as anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

    return view('informes.graficas.depto', compact('departamentos', 'anios'));
    }

    public function dataGraficaDepto(Request $request) {
     $depto_ids = $request->departamento_ids;
     $anio = $request->anio;
     $mes = $request->mes;

     // Obtenemos los departamentos para asegurar que salgan todos (incluso con 0)
     $departamentos = Departamento::whereIn('id', $depto_ids)->get();

     // Consulta de promedios
     $query = DB::table('asignacion_evaluaciones as ae')
         ->join('empleados as e', 'ae.empleado_id', '=', 'e.id')
         ->select('e.departamento_id', DB::raw("AVG(ae.puntuacion_total) as promedio"))
          ->whereIn('e.departamento_id', $depto_ids)
         ->whereYear('ae.created_at', $anio); // Filtro de año obligatorio

      // Filtro de mes solo si se recibe el parámetro
      if ($request->filled('mes')) {
          $query->whereMonth('ae.created_at', $mes);
       }

      $promedios = $query->groupBy('e.departamento_id')
                       ->get()
                       ->keyBy('departamento_id');

      // Mapeo final
      $dataFinal = $departamentos->map(function($depto) use ($promedios) {
          return [
             'nombre' => $depto->nombre,
              'valor' => isset($promedios[$depto->id]) ? round($promedios[$depto->id]->promedio, 2) : 0
            ];
       });

       return response()->json([
         'labels' => $dataFinal->pluck('nombre'),
         'valores' => $dataFinal->pluck('valor'),
       ]);
    }

    // ==========================================
   //  GRÁFICA INDIVIDUAL
  // ==========================================
   public function graficaIndividual() {
    $departamentos = Departamento::orderBy('nombre', 'asc')->get();
    $anios = DB::table('asignacion_evaluaciones')
            ->selectRaw('YEAR(created_at) as anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

    return view('informes.graficas.individual', compact('departamentos', 'anios'));
   }

   // Nueva función para filtrar empleados por depto (AJAX)
   public function getEmpleadosPorDepto($depto_id) {
    // Traemos todos los campos para evitar errores de nombres
    $empleados = DB::table('empleados')
                ->where('departamento_id', $depto_id)
                ->orderBy('nombre', 'asc')
                ->get();
    
    return response()->json($empleados);
   }

   public function dataGraficaIndividual(Request $request) {
    $query = DB::table('asignacion_evaluaciones as ae')
        ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')
        ->select(
            DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"), 
            'ae.puntuacion_total as resultado'
        )
        ->where('ae.empleado_id', $request->empleado_id)
        ->whereYear('ae.created_at', $request->anio);

    if ($request->filled('mes')) {
        $query->whereMonth('ae.created_at', $request->mes);
    }

    $datos = $query->get();

    return response()->json([
        'labels' => $datos->pluck('actividad'),
        'valores' => $datos->pluck('resultado'),
    ]);
  }

   // ==========================================
  //  GRÁFICA DE PERMISOS
  // ==========================================
  public function graficaPermisos() {
    $departamentos = Departamento::all();
    $empleados = Empleado::orderBy('nombre', 'asc')->get();
    
    // Obtenemos los años directamente de la tabla solicitudes (excluyendo compensatorios si deseas)
    $anios = DB::table('solicitudes')
            ->where('tipo', '!=', 'A cuenta de tiempo compensatorio')
            ->selectRaw('YEAR(fecha_inicio) as anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

    if ($anios->isEmpty()) { 
        $anios = collect([date('Y')]); 
    }

    return view('informes.graficas.permisos', compact('departamentos', 'empleados', 'anios'));
  }

   public function dataGraficaPermisos(Request $request) {
        $empleado = Empleado::findOrFail($request->empleado_id);
        $nombreCompleto = $empleado->nombre . ' ' . $empleado->apellido;

        // Consulta base para el empleado y año seleccionado
        $query = DB::table('solicitudes')
            ->where('nombre', $nombreCompleto)
            ->where('estado', 'aprobado')
            ->whereYear('fecha_inicio', $request->anio);

        // Convertimos a minúsculas para evaluar de forma segura
        $tipoRequest = strtolower($request->tipo_solicitud);

        if ($tipoRequest === 'vacaciones') {
            $query->whereRaw('UPPER(tipo) LIKE ?', ['%VACACIONES%']);
        } 
        elseif ($tipoRequest === 'permiso') {
            $query->whereRaw('UPPER(tipo) NOT LIKE ?', ['%VACACIONES%'])
                  ->whereRaw('UPPER(tipo) NOT LIKE ?', ['%COMPENSATORIO%']);
        } 
        else {
            $query->whereRaw('UPPER(tipo) NOT LIKE ?', ['%COMPENSATORIO%']);
        }

        if ($request->filled('mes')) {
            $query->whereMonth('fecha_inicio', $request->mes);
        }

        // Obtenemos la suma bruta de días y horas por cada tipo
        $datos = $query->select(
                            'tipo', 
                            DB::raw('SUM(dias) as total_dias'),
                            DB::raw('SUM(horas) as total_horas')
                        )
                      ->groupBy('tipo')
                      ->get();

        // 1. PRIMER PASO: Calculamos el gran total de horas equivalentes de todas las solicitudes combinadas
        $granTotalHoras = $datos->sum(function($row) {
            return ($row->total_dias * 8) + $row->total_horas;
        });

        // 2. SEGUNDO PASO: Estructuramos los datos calculando el porcentaje individual
        $dataFinal = $datos->map(function($row) use ($granTotalHoras) {
            
            // Convertimos los días a horas (1 día = 8 horas laborales) y sumamos las horas sueltas
            $horasTotalesEquivalentes = ($row->total_dias * 8) + $row->total_horas;

            // Cálculo matemático del porcentaje (Evitamos división por cero)
            $porcentaje = $granTotalHoras > 0 ? round(($horasTotalesEquivalentes / $granTotalHoras) * 100, 1) : 0;

            // Armamos el texto combinando el tiempo real y su porcentaje equivalente
            if ($row->total_dias > 0) {
                $textoVisual = $row->total_dias . ($row->total_dias == 1 ? ' día' : ' días');
                if ($row->total_horas > 0) {
                    $textoVisual .= ' y ' . $row->total_horas . ' hrs';
                }
            } else {
                $textoVisual = $row->total_horas . ' horas';
            }
            
            // Le pegamos el porcentaje al final del texto para que se vea claro en la barra
            $textoFinalConPorcentaje = $textoVisual . " (" . $porcentaje . "%)";
            
            return [
                'tipo'            => $row->tipo,
                'horas_graficar'  => $horasTotalesEquivalentes,
                'texto_pantalla'  => $textoFinalConPorcentaje
            ];
        })->sortByDesc('horas_graficar');

        return response()->json([
            'labels'    => $dataFinal->pluck('tipo'),
            'valores'   => $dataFinal->pluck('horas_graficar'),
            'etiquetas' => $dataFinal->pluck('texto_pantalla'),
        ]);
    }
    

  // ==========================================
  //  GRÁFICA DE TIEMPO COMPENSATORIO
  // ==========================================
    public function graficaCompensatorio() { 
        $departamentos = Departamento::orderBy('nombre', 'asc')->get();
        
        // Copiado exacto de tu consulta nativa de años
        $anios = DB::table('horas_extras')->selectRaw('YEAR(created_at) as anio')
            ->union(DB::table('solicitudes')->selectRaw('YEAR(fecha_inicio) as anio'))
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        if ($anios->isEmpty()) { 
            $anios = collect([date('Y')]); 
        }

        return view('informes.graficas.compensatorio', compact('departamentos', 'anios')); 
    }

   public function dataGraficaCompensatorio(Request $request) {
        // Buscamos el empleado de forma segura
        $empleado = DB::table('empleados')->where('id', $request->empleado_id)->first();
        
        if (!$empleado) {
            return response()->json(['ganadas' => 0, 'usadas' => 0]);
        }

        $nombreExacto = $empleado->nombre . ' ' . $empleado->apellido;

        // 1. HORAS GANADAS: Consulta a la tabla 'horas_extras'
        $queryGanadas = DB::table('horas_extras')
            ->where('empleado_id', $empleado->id)
            ->where('estado', 'aprobado')
            ->whereYear('created_at', $request->anio);

        // 2. HORAS USADAS: Consulta a la tabla 'solicitudes'
        $queryUsadas = DB::table('solicitudes')
            ->where('nombre', $nombreExacto)
            ->where('estado', 'aprobado')
            ->where('tipo', 'A cuenta de tiempo compensatorio')
            ->whereYear('fecha_inicio', $request->anio);

        // Filtro de mes opcional
        if ($request->periodo === 'mensual' && $request->filled('mes')) {
            $queryGanadas->whereMonth('created_at', $request->mes);
            $queryUsadas->whereMonth('fecha_inicio', $request->mes);
        }

        // SUMA DE HORAS GANADAS: Apuntando a tu columna exacta 'horas_acumuladas'
        $totalGanadas = $queryGanadas->sum('horas_acumuladas') ?? 0; 
        
        // SUMA DE HORAS USADAS: Traemos las solicitudes y convertimos (días * 8) + horas sueltas
        $solicitudes = $queryUsadas->select('dias', 'horas')->get();
        $totalUsadas = $solicitudes->sum(function($s) {
            $dias = $s->dias ?? 0;
            $horas = $s->horas ?? 0;
            return ($dias * 8) + $horas;
        });

        // Retornamos los datos listos para las barras de Chart.js
        return response()->json([
            'ganadas' => round($totalGanadas, 2),
            'usadas'  => round($totalUsadas, 2)
        ]);
    }
   
}