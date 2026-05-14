<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\HoraExtra;
use App\Models\Solicitud;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ExportarDesempenoDepto;
use App\Exports\IndividualExport;
use App\Exports\CompensatorioExport;
use Maatwebsite\Excel\Facades\Excel;

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

    public function generarPdf(Request $request) {
        $depto_id = $request->departamento_id;
        $anio = $request->anio;
        $periodo = $request->periodo;
        $mes = $request->mes;
        $departamento = Departamento::find($depto_id);

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

        $pdf = Pdf::loadView('informes.pdf_desempeno', compact('datos', 'departamento', 'anio', 'periodo_texto', 'promedio_depto'));
        return $pdf->download("Reporte_Desempeño_{$departamento->nombre}.pdf");
    }

    public function generarExcel(Request $request) {
        $depto_id = $request->departamento_id;
        $anio = $request->anio;
        $departamento = Departamento::find($depto_id);
        $nombreArchivo = "Desempeño_" . str_replace(' ', '_', $departamento->nombre) . "_{$anio}.xlsx";

        return Excel::download(new ExportarDesempenoDepto($depto_id, $anio, $request->periodo, $request->mes), $nombreArchivo);
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

    // EXCLUSIONES CRÍTICAS:
    // No queremos ni Compensatorios ni Vacaciones en este reporte de "Permisos"
    $query->where('tipo', 'NOT LIKE', '%TIEMPO COMPENSATORIO%')
          ->where('tipo', 'NOT LIKE', '%VACACIONES%');

    if ($request->periodo === 'mensual' && $request->filled('mes')) {
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

    // Exclusiones obligatorias para este reporte específico
    $exclusiones = ['VACACIONES', 'TIEMPO COMPENSATORIO', 'COMPENSATORIO'];
    foreach ($exclusiones as $excluir) {
        $query->where('tipo', 'NOT LIKE', '%' . $excluir . '%');
    }

    $solicitudes = $query->orderBy('fecha_inicio', 'asc')->get();

    // --- CÁLCULO DE SUMA TOTAL ---
    $sumaDias = 0;
    $sumaHoras = 0;

    foreach ($solicitudes as $s) {
        // Suma simple de las columnas de la base de datos
        $sumaDias += $s->dias ?? 0;
        $sumaHoras += $s->horas ?? 0;
    }

    return Pdf::loadView('informes.pdf_permisos', [
        'empleado'    => $empleado,
        'solicitudes' => $solicitudes,
        'anio'        => $request->anio,
        'mes'         => $request->filled('mes') ? $this->obtenerNombreMes($request->mes) : "Anual Acumulado",
        'total_dias'  => $sumaDias,
        'total_horas' => $sumaHoras
    ])->setPaper('letter', 'portrait')->stream("Historial_Permisos.pdf");
}
}