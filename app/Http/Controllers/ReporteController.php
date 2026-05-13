<?php


namespace App\Http\Controllers;                       // Namespace donde se encuentra el controlador

use Illuminate\Support\Facades\DB;                    // Facade para realizar consultas SQL con Query Builder
use Illuminate\Http\Request;                         // Clase Request para manejar formularios y peticiones HTTP
use App\Models\Departamento;                        // Modelo de departamentos
use App\Models\Empleado;                           // Modelo de empleados
use App\Models\HoraExtra;
use App\Models\Solicitud;
use Barryvdh\DomPDF\Facade\Pdf;                   // Librería DomPDF para generar archivos PDF
use App\Exports\ExportarDesempenoDepto;          // Clase personalizada para exportar desempeño por departamento en Excel
use App\Exports\IndividualExport;              // Clase personalizada para exportar desempeño individual en Excel
use Maatwebsite\Excel\Facades\Excel;           // Librería Excel para generar archivos .xlsx



// Controlador encargado de gestionar los reportes del sistema

class ReporteController extends Controller
{
    // 🔹 Vista principal de informes
    public function index() {
        // Retorna la vista principal de reportes
        return view('informes.index');
    }

    // 🔹 Vista para reporte por departamento
    public function departamento() {

        // Obtener todos los departamentos
        $departamentos = Departamento::all();

        // Obtener año actual
        $anioActual = date('Y');

        // Generar rango de años para el selector
        // Incluye el año actual y 5 años anteriores
        $anios = range($anioActual + 1, $anioActual - 5); 
        
        // Retornar vista con datos necesarios
        return view('informes.desempeno_depto', compact('departamentos', 'anios'));
    }

    // 🔹 Generar PDF del desempeño por departamento
    public function generarPdf(Request $request) {

        // Obtener parámetros enviados desde el formulario
        $depto_id = $request->departamento_id;
        $anio = $request->anio;
        $periodo = $request->periodo;
        $mes = $request->mes;

        // Buscar departamento seleccionado
        $departamento = Departamento::find($depto_id);

        // Consulta principal de evaluaciones
        $query = DB::table('asignacion_evaluaciones as ae')

            // Relación con empleados
            ->join('empleados as e', 'ae.empleado_id', '=', 'e.id') 

            // Relación con proyectos
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')

            // Campos a seleccionar
            ->select(

                // Nombre del proyecto o tipo de actividad
                DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"), 

                // Fecha más reciente de evaluación
                DB::raw("MAX(ae.created_at) as fecha"), 

                // Promedio de puntuación
                DB::raw("AVG(ae.puntuacion_total) as resultado")
            )

            // Filtrar por departamento
            ->where('e.departamento_id', $depto_id) 

            // Filtrar por año
            ->whereYear('ae.created_at', $anio)

            // Agrupar por actividad
            ->groupBy('actividad');

        // Validar si el reporte es mensual
        if ($periodo == 'mensual' && $mes) {

            // Filtrar por mes
            $query->whereMonth('ae.created_at', $mes);

            // Texto descriptivo del período
            $periodo_texto = "Mensual (" . $mes . ")";

        } else {

            // Texto para reporte anual
            $periodo_texto = "Anual Acumulado";
        }

        // Ejecutar consulta
        $datos = $query->get();

        // Calcular promedio general del departamento
        $promedio_depto = $datos->avg('resultado');

        // Generar PDF usando la vista correspondiente
        $pdf = Pdf::loadView(
            'informes.pdf_desempeno',
            compact(
                'datos',
                'departamento',
                'anio',
                'periodo_texto',
                'promedio_depto'
            )
        );
        
        // Descargar archivo PDF
        return $pdf->download("Reporte_Desempeño_{$departamento->nombre}.pdf");
    }

    // 🔹 Generar reporte Excel por departamento
    public function generarExcel(Request $request) {

        // Obtener datos enviados
        $depto_id = $request->departamento_id;
        $anio = $request->anio;
        $periodo = $request->periodo;
        $mes = $request->mes;

        // Buscar departamento
        $departamento = Departamento::find($depto_id);

        // Construir nombre del archivo
        $nombreArchivo = "Desempeño_" .
            str_replace(' ', '_', $departamento->nombre) .
            "_{$anio}.xlsx";

        // Descargar archivo Excel
        return Excel::download(
            new ExportarDesempenoDepto($depto_id, $anio, $periodo, $mes),
            $nombreArchivo
        );
    }

    // 🔹 Validar si existen datos antes de generar reportes
    public function validarDatos(Request $request) {

        // Consulta base
        $query = DB::table('asignacion_evaluaciones as ae')

            // Relación con empleados
            ->join('empleados as e', 'ae.empleado_id', '=', 'e.id')

            // Filtrar por año
            ->whereYear('ae.created_at', $request->anio);

        // Validación para reporte individual
        if ($request->filled('empleado_id')) {

            // Filtrar por empleado
            $query->where('ae.empleado_id', $request->empleado_id);

        } 
        // Validación para reporte departamental
        elseif ($request->filled('departamento_id')) {

            // Filtrar por departamento
            $query->where('e.departamento_id', $request->departamento_id);
        }

        // Filtro mensual para ambos casos
        if ($request->periodo === 'mensual' && $request->filled('mes')) {

            // Filtrar por mes
            $query->whereMonth('ae.created_at', $request->mes);
        }

        // Contar registros encontrados
        $total = $query->count();

        // Retornar respuesta JSON
        return response()->json([
            'count' => $total,
            'existe' => $total > 0
        ]);
    }

    // 🔹 Vista para reporte individual
   public function individual() {
    $empleados = Empleado::orderBy('nombre', 'asc')->get();
    $departamentos = Departamento::orderBy('nombre', 'asc')->get(); 
    $anioActual = date('Y');
    $anios = range($anioActual, $anioActual - 5);
    
    return view('informes.individual', compact('empleados', 'departamentos', 'anios'));
}

    // 🔹 Generar PDF individual
    public function generarIndividualPdf(Request $request) {

        // Buscar empleado
        $empleado = Empleado::findOrFail($request->empleado_id);
        
        // Consulta principal
        $query = DB::table('asignacion_evaluaciones as ae')

            // Relación con proyectos
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')

            // Campos a mostrar
            ->select(
                DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"),
                'ae.created_at as fecha',
                'ae.puntuacion_total as resultado'
            )

            // Filtrar por empleado
            ->where('ae.empleado_id', $request->empleado_id)

            // Filtrar por año
            ->whereYear('ae.created_at', $request->anio);

        // Filtro mensual
        if ($request->periodo == 'mensual' && $request->mes) {

            // Filtrar por mes
            $query->whereMonth('ae.created_at', $request->mes);
        }

        // Obtener resultados
        $datos = $query->get();

        // Calcular promedio global
        $promedio_global = $datos->avg('resultado') ?? 0;

        // Generar PDF
        $pdf = Pdf::loadView('informes.pdf_individual', [

            // Datos para la vista
            'datos' => $datos,
            'empleado' => $empleado,
            'anio' => $request->anio,
            'promedio_global' => $promedio_global
        ]);

        // Descargar archivo PDF
        return $pdf->download(
            "Evaluacion_{$empleado->nombre}_{$empleado->apellido}.pdf"
        );
    }

public function generarIndividualExcel(Request $request)
{
    // 1. Obtener los datos básicos
    $empleado = Empleado::findOrFail($request->empleado_id);
    $anio = $request->anio;
    $periodo = $request->periodo;
    $mes = $request->mes;

    // 2. Consulta rápida con DB (como la de depto)
    $query = DB::table('asignacion_evaluaciones as ae')
        ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')
        ->select(
            DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"),
            'ae.created_at as fecha',
            'ae.puntuacion_total as resultado'
        )
        ->where('ae.empleado_id', $request->empleado_id)
        ->whereYear('ae.created_at', $anio);

    // 3. Filtro de mes
    if ($periodo == 'mensual' && $mes) {
        $query->whereMonth('ae.created_at', $mes);
        $periodo_texto = "Mensual (" . $mes . ")";
    } else {
        $periodo_texto = "Anual Acumulado";
    }

    $datos = $query->get();
    $promedio_individual = $datos->avg('resultado') ?? 0;

    // 4. Descarga directa
    return Excel::download(
        new IndividualExport($empleado, $datos, $periodo_texto, $anio, $promedio_individual), 
        "Reporte_Individual_{$empleado->apellido}.xlsx"
    );
}

// Función auxiliar para el texto del mes
private function obtenerNombreMes($mes) {
    $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
              '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
    return $meses[$mes] ?? '';
}

    // 🔹 Placeholder reporte de permisos
    public function permisos() {

        // Mensaje temporal
        return "Próximamente: Informe de Permisos";
    }

    // 🔹 Placeholder reporte compensatorio
 public function compensatorio()
{
    $departamentos = Departamento::all();
    // Cambia esto:
    $empleados = Empleado::all(); 
    $anios = range(date('Y') + 1, date('Y') - 5);

    return view('informes.compensatorio', compact('departamentos', 'empleados', 'anios'));
}

public function validarCompensatorio(Request $request) 
{
    try {
        // Forzamos el uso del modelo con su ruta completa para evitar errores de importación
        $query = \App\Models\HoraExtra::where('empleado_id', $request->empleado_id)
                                     ->where('estado', 'aprobado');

        // Intentamos usar 'created_at' que es lo más común en Laravel
        if ($request->periodo === 'anual') {
            $query->whereYear('created_at', $request->anio);
        } else {
            $query->whereYear('created_at', $request->anio)
                  ->whereMonth('created_at', $request->mes);
        }

        return response()->json(['count' => $query->count()]);

    } catch (\Exception $e) {
        // Esto devolverá el error real al navegador para que podamos leerlo
        return response()->json([
            'error' => true,
            'mensaje' => $e->getMessage(),
            'linea' => $e->getLine()
        ], 500);
    }
}

public function pdfCompensatorio(Request $request)
{
    $empleadoId = $request->get('empleado_id');
    $anio = $request->get('anio', date('Y'));
    $mes = $request->get('mes');

    $empleado = Empleado::with(['departamento'])->findOrFail($empleadoId);

    // 1. Traemos Horas Extras (Usa empleado_id)
    $movimientos = HoraExtra::where('empleado_id', $empleado->id)
        ->where('estado', 'aprobado')
        ->whereYear('created_at', $anio)
        ->when($mes, function ($query) use ($mes) {
            return $query->whereMonth('created_at', $mes);
        })
        ->get();

    // 2. Traemos Solicitudes (Usa el nombre exacto para evitar errores)
    // Usamos el nombre tal cual está en la ficha del empleado
    $nombreExacto = $empleado->nombre . ' ' . $empleado->apellido;

    $solicitudesAprobadas = Solicitud::where('nombre', $nombreExacto)
        ->where('estado', 'aprobado')
        ->where('tipo', 'A cuenta de tiempo compensatorio')
        ->whereYear('fecha_inicio', $anio)
        ->when($mes, function ($query) use ($mes) {
            return $query->whereMonth('fecha_inicio', $mes);
        })
        ->get();

    // 3. Unimos ambas colecciones y ordenamos cronológicamente
    $todosLosRegistros = $movimientos->concat($solicitudesAprobadas)->sortBy(function($item) {
        return $item->fecha ?? $item->fecha_inicio;
    });

    $pdf = \PDF::loadView('informes.pdf_compensatorio', [
        'empleado' => $empleado,
        'anio' => $anio,
        'todosLosRegistros' => $todosLosRegistros
    ]);

    return $pdf->setPaper('letter', 'portrait')->stream("Reporte.pdf");
}

public function excelCompensatorio(Request $request)
{
    // Por ahora solo para probar que la ruta existe
    return "Generando Excel para el empleado ID: " . $request->empleado_id;
    
    /* 
       Más adelante aquí usarás Maatwebsite\Excel 
       o una lógica similar para el reporte del IHCI 
    */
}

}