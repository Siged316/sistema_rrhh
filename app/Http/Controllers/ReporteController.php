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
    return $pdf->download("Reporte_Desempeño_{$departamento->nombre}.pdf");
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

        $datos = $query->get();
        $promedio_global = $datos->avg('resultado') ?? 0;

        $pdf = Pdf::loadView('informes.pdf_individual', ['datos' => $datos, 'empleado' => $empleado, 'anio' => $request->anio, 'promedio_global' => $promedio_global]);
        return $pdf->download("Evaluacion_{$empleado->nombre}_{$empleado->apellido}.pdf");
    }

    public function generarIndividualExcel(Request $request) {
        $empleado = Empleado::findOrFail($request->empleado_id);
        $query = DB::table('asignacion_evaluaciones as ae')
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')
            ->select(DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"), 'ae.created_at as fecha', 'ae.puntuacion_total as resultado')
            ->where('ae.empleado_id', $request->empleado_id)
            ->whereYear('ae.created_at', $request->anio);

        $periodo_texto = ($request->periodo == 'mensual' && $request->mes) ? "Mensual (" . $request->mes . ")" : "Anual Acumulado";
        if ($request->periodo == 'mensual' && $request->mes) $query->whereMonth('ae.created_at', $request->mes);

        $datos = $query->get();
        return Excel::download(new IndividualExport($empleado, $datos, $periodo_texto, $request->anio, $datos->avg('resultado') ?? 0), "Reporte_Individual_{$empleado->apellido}.xlsx");
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
        
        $data = ['empleado' => $empleado, 'anio' => $request->anio, 'todosLosRegistros' => $movimientos->concat($solicitudes), 'firmaBlob' => $firmaData];
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

}