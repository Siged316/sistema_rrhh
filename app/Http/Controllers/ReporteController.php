<?php


namespace App\Http\Controllers;                       // Namespace donde se encuentra el controlador

use Illuminate\Support\Facades\DB;                    // Facade para realizar consultas SQL con Query Builder
use Illuminate\Http\Request;                         // Clase Request para manejar formularios y peticiones HTTP
use App\Models\Departamento;                        // Modelo de departamentos
use App\Models\Empleado;                           // Modelo de empleados
use Barryvdh\DomPDF\Facade\Pdf;                   // Librería DomPDF para generar archivos PDF
use App\Exports\ExportarDesempenoDepto;          // Clase personalizada para exportar desempeño por departamento en Excel
use App\Exports\ExportarDesempenoIndividual;    // Clase personalizada para exportar desempeño individual en Excel
use Maatwebsite\Excel\Facades\Excel;           // Librería Excel para generar archivos .xlsx


// Controlador encargado de gestionar los reportes del sistema
class ReporteController extends Controller
{
    public function index() {
        return view('informes.index');
    }

    public function departamento() {
        $departamentos = Departamento::all();
        $anioActual = date('Y');
        // Rango ajustado para el sistema IHCI
        $anios = range($anioActual + 1, $anioActual - 5); 
        
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
        $periodo = $request->periodo;
        $mes = $request->mes;

        $departamento = Departamento::find($depto_id);
        $nombreArchivo = "Desempeño_" . str_replace(' ', '_', $departamento->nombre) . "_{$anio}.xlsx";

        return Excel::download(new ExportarDesempenoDepto($depto_id, $anio, $periodo, $mes), $nombreArchivo);
    }

// Método para validar si existen datos antes de descargar
public function validarDatos(Request $request) {
    $query = DB::table('asignacion_evaluaciones as ae')
        ->join('empleados as e', 'ae.empleado_id', '=', 'e.id')
        ->whereYear('ae.created_at', $request->anio);

    // Si la petición viene del informe INDIVIDUAL
    if ($request->filled('empleado_id')) {
        $query->where('ae.empleado_id', $request->empleado_id);
    } 
    // Si la petición viene del informe por DEPARTAMENTO
    elseif ($request->filled('departamento_id')) {
        $query->where('e.departamento_id', $request->departamento_id);
    }

    // Filtro de mes (común para ambos)
    if ($request->periodo === 'mensual' && $request->filled('mes')) {
        $query->whereMonth('ae.created_at', $request->mes);
    }

    $total = $query->count();

    // Cambiamos 'count' por 'existe' para que el JS sea más claro, 
    // pero mantenemos compatibilidad con lo que ya tienes.
    return response()->json(['count' => $total, 'existe' => $total > 0]);
}

    // --- MÉTODOS INDIVIDUALES ACTUALIZADOS ---

  public function individual() {
    // Ordenamos directamente por la columna 'nombre' que sí existe en tu tabla
    $empleados = Empleado::orderBy('nombre', 'asc')
                         ->orderBy('apellido', 'asc')
                         ->get();

    $anioActual = date('Y');
    // Generamos el rango de años para el selector
    $anios = range($anioActual, $anioActual - 5);
    
    return view('informes.individual', compact('empleados', 'anios'));
}

    public function generarIndividualPdf(Request $request) {
        $empleado = Empleado::findOrFail($request->empleado_id);
        
        $query = DB::table('asignacion_evaluaciones as ae')
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')
            ->select(
                DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"),
                'ae.created_at as fecha',
                'ae.puntuacion_total as resultado'
            )
            ->where('ae.empleado_id', $request->empleado_id)
            ->whereYear('ae.created_at', $request->anio);

        if ($request->periodo == 'mensual' && $request->mes) {
            $query->whereMonth('ae.created_at', $request->mes);
        }

        $datos = $query->get();
        $pdf = Pdf::loadView('informes.pdf_individual', [
            'datos' => $datos,
            'empleado' => $empleado,
            'anio' => $request->anio
        ]);

        return $pdf->download("Evaluacion_{$empleado->nombre_completo}.pdf");
    }

    public function permisos() {
        return "Próximamente: Informe de Permisos";
    }

    public function compensatorio() {
        return "Próximamente: Informe de Compensatorio";
    }
}