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

    // Método principal del módulo de reportes
    public function index() {

        // Retorna la vista principal de informes
        return view('informes.index');
    }


    // Vista para reportes por departamento
    public function departamento() {

        // Obtiene todos los departamentos
        $departamentos = Departamento::all();

        // Obtiene el año actual
        $anioActual = date('Y');

        // Genera un rango de años
        // Desde el próximo año hasta 5 años atrás
        $anios = range($anioActual + 1, $anioActual - 5); 
        
        // Retorna la vista con los datos
        return view('informes.desempeno_depto', compact('departamentos', 'anios'));
    }


    // Método para generar reporte PDF por departamento
    public function generarPdf(Request $request) {

        // Captura datos enviados desde el formulario
        $depto_id = $request->departamento_id;
        $anio = $request->anio;
        $periodo = $request->periodo;
        $mes = $request->mes;

        // Busca el departamento seleccionado
        $departamento = Departamento::find($depto_id);

        // Construcción de la consulta
        $query = DB::table('asignacion_evaluaciones as ae')

            // Relación con empleados
            ->join('empleados as e', 'ae.empleado_id', '=', 'e.id') 

            // Relación opcional con proyectos
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')

            // Campos seleccionados
            ->select(

                // Si existe proyecto usa su nombre,
                // si no, usa el tipo de evaluación
                DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"), 

                // Obtiene la fecha más reciente
                DB::raw("MAX(ae.created_at) as fecha"), 

                // Promedio de puntuaciones
                DB::raw("AVG(ae.puntuacion_total) as resultado")
            )

            // Filtra por departamento
            ->where('e.departamento_id', $depto_id) 

            // Filtra por año
            ->whereYear('ae.created_at', $anio)

            // Agrupa por actividad
            ->groupBy('actividad');

        // Validación para reportes mensuales
        if ($periodo == 'mensual' && $mes) {

            // Filtra por mes
            $query->whereMonth('ae.created_at', $mes);

            // Texto descriptivo del periodo
            $periodo_texto = "Mensual (" . $mes . ")";

        } else {

            // Si no es mensual será acumulado anual
            $periodo_texto = "Anual Acumulado";
        }

        // Ejecuta la consulta
        $datos = $query->get();

        // Calcula promedio general del departamento
        $promedio_depto = $datos->avg('resultado');

        // Genera el PDF usando una vista Blade
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
        
        // Descarga automática del PDF
        return $pdf->download("Reporte_Desempeño_{$departamento->nombre}.pdf");
    }


    // Método para generar Excel por departamento
    public function generarExcel(Request $request) {

        // Captura datos enviados desde el formulario
        $depto_id = $request->departamento_id;
        $anio = $request->anio;
        $periodo = $request->periodo;
        $mes = $request->mes;

        // Busca el departamento seleccionado
        $departamento = Departamento::find($depto_id);

        // Nombre dinámico del archivo Excel
        $nombreArchivo = "Desempeño_" .
            str_replace(' ', '_', $departamento->nombre) .
            "_{$anio}.xlsx";

        // Genera y descarga el archivo Excel
        return Excel::download(
            new ExportarDesempenoDepto($depto_id, $anio, $periodo, $mes),
            $nombreArchivo
        );
    }


    // ==============================
    // REPORTES INDIVIDUALES
    // ==============================


    // Vista principal de reportes individuales
    public function individual() {

        // Obtiene empleados ordenados alfabéticamente
        $empleados = Empleado::orderBy('nombre', 'asc')
                             ->orderBy('apellido', 'asc')
                             ->get();

        // Obtiene año actual
        $anioActual = date('Y');

        // Genera rango de años
        $anios = range($anioActual, $anioActual - 5);
        
        // Retorna vista individual
        return view('informes.individual', compact('empleados', 'anios'));
    }


    // Genera PDF individual de un empleado
    public function generarIndividualPdf(Request $request) {

        // Busca empleado seleccionado
        $empleado = Empleado::findOrFail($request->empleado_id);
        
        // Consulta de evaluaciones individuales
        $query = DB::table('asignacion_evaluaciones as ae')

            // Relación opcional con proyectos
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')

            // Campos seleccionados
            ->select(

                // Nombre del proyecto o tipo
                DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"),

                // Fecha de evaluación
                'ae.created_at as fecha',

                // Resultado de evaluación
                'ae.puntuacion_total as resultado'
            )

            // Filtra por empleado
            ->where('ae.empleado_id', $request->empleado_id)

            // Filtra por año
            ->whereYear('ae.created_at', $request->anio);

        // Si el periodo es mensual
        if ($request->periodo == 'mensual' && $request->mes) {

            // Filtra por mes
            $query->whereMonth('ae.created_at', $request->mes);
        }

        // Ejecuta consulta
        $datos = $query->get();

        // Genera PDF individual
        $pdf = Pdf::loadView('informes.pdf_individual', [

            // Datos de evaluaciones
            'datos' => $datos,

            // Información del empleado
            'empleado' => $empleado,

            // Año seleccionado
            'anio' => $request->anio
        ]);

        // Descarga automática del PDF
        return $pdf->download("Evaluacion_{$empleado->nombre_completo}.pdf");
    }


    // Método placeholder para informe de permisos
    public function permisos() {

        // Mensaje temporal
        return "Próximamente: Informe de Permisos";
    }


    // Método placeholder para informe compensatorio
    public function compensatorio() {

        // Mensaje temporal
        return "Próximamente: Informe de Compensatorio";
    }
}