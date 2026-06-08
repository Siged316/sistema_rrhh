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
use Illuminate\Support\Facades\Log;

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
   
       // 1. Ver qué campos llegan
       $campos = $request->all();

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

       // ===============================
      // GRÁFICA 3D DINÁMICA - IHCI
     // ===============================

      $width = 900;
      $height = 450;
      $image = imagecreatetruecolor($width, $height);

      // Definición de colores
      $bg = imagecolorallocate($image, 240, 240, 242); 
      $text_main = imagecolorallocate($image, 33, 37, 41);
      $grid_color = imagecolorallocate($image, 215, 215, 218);
      $accent_red = imagecolorallocate($image, 201, 28, 48);

      imagefilledrectangle($image, 0, 0, $width, $height, $bg);

      // Fuente (Asegúrate de que esta ruta sea accesible por PHP)
      $fontPath = 'C:/Windows/Fonts/arial.ttf';

      // Título
      $titulo = "RESULTADOS DE GESTIÓN: PROYECTOS / ACTIVIDADES";
      $fontSize = 12;
      $bbox = imagettfbbox($fontSize, 0, $fontPath, $titulo);
      $tituloWidth = $bbox[2] - $bbox[0];
      $xTitulo = ($width - $tituloWidth) / 2;
      imagettftext($image, $fontSize, 0, $xTitulo, 45, $text_main, $fontPath, $titulo);
      

      // Grid y límites
      $left = 70; $bottom = 380; $top = 80; $right = 850;
      $depth = 12;

      for ($i = 0; $i <= 5; $i++) {
          $y = $bottom - ($i * (($bottom - $top) / 5));
          imageline($image, $left, $y, $right, $y, $grid_color);
          imagettftext($image, 10, 0, 25, $y + 5, $text_main, $fontPath, ($i * 20) . '%');
        }

        // --- ETIQUETAS DE EJES (FUERA DEL BUCLE) ---
        imagettftext($image, 12, 90, 20, ($bottom + $top) / 2 + 50, $text_main, $fontPath, "Porcentaje (%)");

        $labelX = "Proyectos / Actividades";
        $bboxX = imagettfbbox(12, 0, $fontPath, $labelX);
        $xPos = ($right + $left - ($bboxX[2] - $bboxX[0])) / 2;
        imagettftext($image, 12, 0, $xPos, $bottom + 50, $text_main, $fontPath, $labelX);

        // Paleta de colores
        $paleta = [
          ['f' => [55, 98, 148], 't' => [80, 130, 190], 's' => [40, 70, 110]], // Azul
          ['f' => [201, 28, 48], 't' => [230, 60, 80], 's' => [150, 10, 20]], // Rojo
          ['f' => [34, 139, 34], 't' => [60, 179, 113], 's' => [20, 100, 20]], // Verde
          ['f' => [255, 140, 0], 't' => [255, 170, 40], 's' => [200, 100, 0]]  // Naranja
        ];

        // Cálculo de espaciado
        $total = count($datos);
        $spacing = ($right - $left) / ($total + 1);
        $barWidth = min(50, $spacing * 0.5); 
        $x = $left + ($spacing / 2);

        // Bucle principal
       foreach ($datos as $index => $d) {
          $valor = min(100, round($d->resultado, 2));
          $barHeight = ($valor == 0) ? 0 : max(15, ($valor / 100) * ($bottom - $top));
           $y1 = $bottom - $barHeight;

           $col = $paleta[$index % count($paleta)];
           $bar_color = imagecolorallocate($image, $col['f'][0], $col['f'][1], $col['f'][2]);
           $bar_top   = imagecolorallocate($image, $col['t'][0], $col['t'][1], $col['t'][2]);
           $bar_side  = imagecolorallocate($image, $col['s'][0], $col['s'][1], $col['s'][2]);

           // Dibujar 3D
            if ($valor > 0) {
              imagefilledpolygon($image, [$x + $barWidth, $y1, $x + $barWidth + $depth, $y1 - $depth, $x + $barWidth + $depth, $bottom - $depth, $x + $barWidth, $bottom], 4, $bar_side);
              imagefilledpolygon($image, [$x, $y1, $x + $depth, $y1 - $depth, $x + $barWidth + $depth, $y1 - $depth, $x + $barWidth, $y1], 4, $bar_top);
              imagefilledrectangle($image, $x, $y1, $x + $barWidth, $bottom, $bar_color);
            }

          // Porcentaje sobre barra
           $valText = $valor . '%';
           $textY = ($valor > 0) ? ($y1 - $depth - 5) : ($bottom - 20);
           imagettftext($image, 10, 0, $x + ($barWidth/2) - 15, $textY, $text_main, $fontPath, $valText);

          // Etiqueta de actividad (abajo)
          $texto = strlen($d->actividad) > 10 ? substr($d->actividad, 0, 8) . '...' : $d->actividad;
          imagettftext($image, 10, 0, $x, $bottom + 25, $text_main, $fontPath, $texto);

         $x += $spacing;
        }

       // Salida

      ob_start();
      imagepng($image);
      $imageData = ob_get_clean();
      $graficaBase64 = 'data:image/png;base64,' . base64_encode($imageData);
      imagedestroy($image);
      $pdf = Pdf::loadView('informes.pdf_desempeno', compact('datos', 'departamento', 'anio', 'periodo_texto', 'promedio_depto', 'firma',   'graficaBase64'));
      return $pdf->stream("Reporte_Desempeño_{$departamento->nombre}.pdf");
    }

    public function descargarFuente() {
    $url = 'https://github.com/google/fonts/raw/main/apache/roboto/Roboto-Bold.ttf';
    $path = public_path('fonts/Roboto-Bold.ttf');
    
    // Descarga el contenido del archivo
    $fileContent = file_get_contents($url);
    
    // Guarda el archivo
    if (file_put_contents($path, $fileContent)) {
        return "¡Fuente descargada exitosamente en: " . $path;
    } else {
        return "Error al descargar la fuente. Verifica permisos de escritura.";
    }
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

   public function dataGraficaDepto(Request $request)
   {
    $depto_ids = $request->departamento_ids ?? [];
    $anios = $request->anios ?? [];
    $mes = $request->mes ?? 'all';

    $nombres = Departamento::whereIn('id', $depto_ids)->pluck('nombre', 'id');

    $query = DB::table('asignacion_evaluaciones as ae')
        ->join('empleados as e', 'ae.empleado_id', '=', 'e.id')
        ->select('e.departamento_id', DB::raw("YEAR(ae.created_at) as anio"), DB::raw("MONTH(ae.created_at) as mes"), DB::raw("AVG(ae.puntuacion_total) as promedio"))
        ->whereIn('e.departamento_id', $depto_ids)
        ->whereIn(DB::raw("YEAR(ae.created_at)"), $anios);

    if ($mes != 'all') $query->whereMonth('ae.created_at', $mes);

    $results = $query->groupBy('e.departamento_id', 'anio', 'mes')->get();

    $datasets = [];
    foreach ($depto_ids as $dId) {
        $nombreDepto = $nombres[$dId] ?? 'Depto '.$dId;
        $data = [];
        if ($mes == 'all') {
            for ($m = 1; $m <= 12; $m++) {
                $row = $results->where('departamento_id', $dId)->where('mes', $m)->first();
                $data[] = $row ? round($row->promedio, 2) : 0;
            }
        } else {
            foreach ($anios as $a) {
                $row = $results->where('departamento_id', $dId)->where('anio', (int)$a)->first();
                $data[] = $row ? round($row->promedio, 2) : 0;
            }
        }
        $datasets[] = ['label' => $nombreDepto, 'data' => $data];
    }

    return response()->json([
        'labels' => ($mes == 'all') ? ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'] : $anios,
        'datasets' => $datasets
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

    public function dataGraficaIndividual(Request $request)
    {
    $mes = $request->mes;
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    $series = [];
    $aniosSolicitados = $request->anios ?? [$request->anio];

    foreach ($aniosSolicitados as $anio) {
        $query = DB::table('asignacion_evaluaciones')
            ->select(DB::raw("MONTH(created_at) as mes"), DB::raw("AVG(puntuacion_total) as val"))
            ->where('empleado_id', $request->empleado_id)
            ->whereYear('created_at', $anio);

        if ($mes && $mes !== 'todo') {
            $query->whereMonth('created_at', $mes);
        }

        $datos = $query->groupBy('mes')->pluck('val', 'mes');

        // Construir el array de series asegurando que siempre haya 12 meses o el filtrado
        foreach ($meses as $num => $nombre) {
            if ($mes && $mes !== 'todo' && (int)$mes !== $num) continue;
            $series[$anio][] = round($datos[$num] ?? 0, 2);
        }
    }

    // Generar labels filtrados si es necesario
    $labels = ($mes && $mes !== 'todo') ? [$meses[(int)$mes]] : array_values($meses);

    return response()->json([
        'labels' => $labels,
        'series' => $series
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

   public function dataGraficaPermisos(Request $request) 
   {
    $empleado = Empleado::findOrFail($request->empleado_id);
    $nombreCompleto = $empleado->nombre . ' ' . $empleado->apellido;

    $query = DB::table('solicitudes')
        ->where('nombre', $nombreCompleto)
        ->where('estado', 'aprobado')
        ->whereYear('fecha_inicio', $request->anio);

    $tipoRequest = strtolower($request->tipo_solicitud);

    if ($tipoRequest === 'vacaciones') {
        $query->whereRaw('UPPER(tipo) LIKE ?', ['%VACACIONES%']);
    } elseif ($tipoRequest === 'permiso') {
        $query->whereRaw('UPPER(tipo) NOT LIKE ?', ['%VACACIONES%'])
              ->whereRaw('UPPER(tipo) NOT LIKE ?', ['%COMPENSATORIO%']);
    } else {
        $query->whereRaw('UPPER(tipo) NOT LIKE ?', ['%COMPENSATORIO%']);
    }

    if ($request->filled('mes')) {
        $query->whereMonth('fecha_inicio', $request->mes);
    }

    $agruparPorMes = ($tipoRequest === 'vacaciones' && !$request->filled('mes'));

    if ($agruparPorMes) {
        // Obtenemos los datos reales existentes
        $datosReales = $query->select(
            DB::raw("MONTH(fecha_inicio) as mes_num"),
            DB::raw('SUM(dias) as total_dias'),
            DB::raw('SUM(horas) as total_horas')
        )
        ->groupBy('mes_num')
        ->get()
        ->keyBy('mes_num');

        // Creamos la estructura fija de 12 meses
        $mesesNombres = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 
                         7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
        
        $datos = collect();
        foreach ($mesesNombres as $num => $nombre) {
            $fila = $datosReales->get($num);
            $datos->push((object)[
                'label_agrupacion' => $nombre,
                'total_dias' => $fila ? $fila->total_dias : 0,
                'total_horas' => $fila ? $fila->total_horas : 0
            ]);
        }
    } else {
        $datos = $query->select(
            'tipo as label_agrupacion',
            DB::raw('SUM(dias) as total_dias'),
            DB::raw('SUM(horas) as total_horas')
        )
        ->groupBy('tipo')
        ->get();
    }

    $granTotalHoras = $datos->sum(fn($row) => ($row->total_dias * 8) + $row->total_horas);

    $dataFinal = $datos->map(function($row) use ($granTotalHoras) {
        $horas = ($row->total_dias * 8) + $row->total_horas;
        $porcentaje = $granTotalHoras > 0 ? round(($horas / $granTotalHoras) * 100, 1) : 0;
        
        $textoVisual = ($row->total_dias > 0 || $row->total_horas > 0) 
                       ? (($row->total_dias > 0 ? $row->total_dias . ' días ' : '') . 
                          ($row->total_horas > 0 ? $row->total_horas . ' hrs' : '')) 
                       : '0 hrs';

        return [
            'label' => $row->label_agrupacion,
            'valor' => $horas,
            'etiqueta' => trim($textoVisual) . ($horas > 0 ? " ($porcentaje%)" : "")
        ];
    });

    return response()->json([
        'labels'    => $dataFinal->pluck('label'),
        'valores'   => $dataFinal->pluck('valor'),
        'etiquetas' => $dataFinal->pluck('etiqueta'),
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