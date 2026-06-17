<?php


namespace App\Http\Controllers;

// Modelos usados en el controlador
use App\Models\Evaluacion;
use App\Models\EvalDetalle;
use App\Models\Criterio;
use App\Models\Empleado; 
use App\Models\Departamento;
use App\Models\Formulario;
use App\Models\Proyecto;

// Clases base de Laravel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Controlador principal de evaluaciones
class EvaluacionController extends Controller
{
    // 🔹 Vista principal del módulo de evaluaciones
    public function index()
    {
        // Obtener todos los proyectos registrados
        $proyectos = Proyecto::all();

        // Obtener departamentos con sus empleados relacionados
        $departamentos = Departamento::with('empleados')
                                     ->orderBy('nombre', 'asc')
                                     ->get();

        // Obtener todos los empleados del sistema
        $empleados = Empleado::all();

        // Obtener ID del empleado asociado al usuario autenticado
        $usuarioId = auth()->user()->empleado_id;

        // Consultar evaluaciones pendientes asignadas al evaluador logueado
        $evaluaciones = DB::table('asignacion_evaluaciones as a')
            ->join('evaluacion_formularios as f', 'a.formulario_id', '=', 'f.id')
            ->join('empleados as e', 'a.empleado_id', '=', 'e.id')
            ->select('a.*', 'f.nombre as nombre_formulario', DB::raw("CONCAT(e.nombre, ' ', e.apellido) as nombre_colaborador"))
            ->where('a.evaluador_id', $usuarioId)
            ->where('a.estado', '=', 'Pendiente')
            ->get();

        // Retornar vista principal con todos los datos
        return view('evaluaciones.index', compact(
            'departamentos',
            'empleados',
            'evaluaciones',
            'proyectos'
        ));
    }

    // 🔹 Comparación de evaluaciones por empleado
    public function comparar(Request $request, $id)
    {
        // Tipo de cálculo (simple o ponderado)
        $tipo = $request->get('tipo', 'simple');

        // Obtener empleado seleccionado
        $empleado = Empleado::findOrFail($id);

        // Filtros opcionales
        $proyectoId = $request->get('proyecto_id');
        $periodo = $request->get('periodo');

        // Consulta base de evaluaciones del empleado
        $evaluaciones = DB::table('asignacion_evaluaciones as a')
            ->join('evaluacion_formularios as f', 'a.formulario_id', '=', 'f.id')
            ->select('a.*', 'f.nombre as nombre_formulario')
            ->where('a.empleado_id', $id)
            ->whereIn('a.estado', ['Completada', 'Firmada']);

        // Filtrar por proyecto si existe
        if ($proyectoId) {
            $evaluaciones->where('proyecto_id', $proyectoId);
        }

        // Ejecutar consulta
        $evaluaciones = $evaluaciones->get();

        // Si no hay datos, retornar vista vacía
        if ($evaluaciones->isEmpty()) {
            return view('evaluaciones.comparativa_tabla', [
                'empleado' => $empleado,
                'historial' => [],
                'labelsGrafica' => [],
                'valoresGrafica' => [],
                'promedioGeneral' => 0
            ]);
        }

        // Agrupar evaluaciones por período
        $agrupado = [];

        foreach ($evaluaciones as $ev) {

            // Agrupación mensual
            if ($periodo == 'mensual') {
                $fecha = date('Y-m', strtotime($ev->created_at));
            }
            // Agrupación trimestral
            elseif ($periodo == 'trimestral') {
                $mes = date('m', strtotime($ev->created_at));
                $anio = date('Y', strtotime($ev->created_at));

                if ($mes <= 3) $fecha = $anio . '-T1';
                elseif ($mes <= 6) $fecha = $anio . '-T2';
                elseif ($mes <= 9) $fecha = $anio . '-T3';
                else $fecha = $anio . '-T4';
            }
            // Agrupación anual
            elseif ($periodo == 'anual') {
                $fecha = date('Y', strtotime($ev->created_at));
            }
            // Fecha exacta
            else {
                $fecha = date('Y-m-d', strtotime($ev->created_at));
            }

            // Inicializar estructura si no existe
            if (!isset($agrupado[$fecha])) {
                $agrupado[$fecha] = [
                    'suma' => 0,
                    'conteo' => 0,
                    'items' => []
                ];
            }

            // Acumular datos
            $agrupado[$fecha]['suma'] += $ev->puntuacion_total;
            $agrupado[$fecha]['conteo'] += 1;
            $agrupado[$fecha]['items'][] = $ev;
        }

        $historialProcesado = [];

        // Procesar agrupaciones
        foreach ($agrupado as $fecha => $datos) {

            // Cálculo ponderado
            if ($tipo === 'ponderado') {

                $suma = 0;
                $totalPeso = 0;

                foreach ($datos['items'] as $ev) {
                    $peso = $ev->peso ?? 0;
                    $suma += $ev->puntuacion_total * $peso;
                    $totalPeso += $peso;
                }

                $valor = $totalPeso > 0 ? $suma / $totalPeso : 0;

            } 
            // Promedio simple
            else {
                $valor = array_sum(array_column($datos['items'], 'puntuacion_total'))
                        / count($datos['items']);
            }

            // Estructura final
            $historialProcesado[] = (object)[
                'fecha_formateada' => $fecha,
                'puntuacion_consolidada' => round($valor, 2),
                'jefes_count' => $datos['conteo'],
                'raw_date' => $fecha,
                'nombre_formulario' => $datos['items'][0]->nombre_formulario ?? 'Evaluación'
            ];
        }

        // Ordenar por fecha
        usort($historialProcesado, fn($a, $b) =>
            strcmp($a->raw_date, $b->raw_date)
        );

        // Datos para gráfica
        $labelsGrafica = array_map(fn($i) => $i->fecha_formateada, $historialProcesado);
        $valoresGrafica = array_map(fn($i) => $i->puntuacion_consolidada, $historialProcesado);

        // Retornar vista
        return view('evaluaciones.comparativa_tabla', [
            'empleado' => $empleado,
            'historial' => array_reverse($historialProcesado),
            'labelsGrafica' => $labelsGrafica,
            'valoresGrafica' => $valoresGrafica,
            'promedioGeneral' => array_sum($valoresGrafica) / max(count($valoresGrafica), 1)
        ]);
    }

    // 🔹 Mis asignaciones del jefe
    public function misAsignaciones()
    {
        // Evaluaciones asignadas al jefe logueado
        $asignaciones = \App\Models\AsignacionEvaluador::where('jefe_id', auth()->id())
            ->with('empleado')
            ->get();

        return view('validaciones.mis_tareas', compact('asignaciones'));
    }

    // 🔹 Mostrar formulario de evaluación
    public function contestar($asignacion_id)
    {
        // Buscar asignación
        $asignacion = DB::table('asignacion_evaluaciones')
            ->where('id', $asignacion_id)
            ->first();

        // Validar existencia
        if (!$asignacion) {
            return redirect()->route('home')->with('error', 'La evaluación solicitada no existe.');
        }

        // Validar si ya fue completada
        if ($asignacion->estado === 'Completada') {
            return redirect()->route('home')->with('info', 'Esta evaluación ya fue completada anteriormente.');
        }

        // Cargar formulario con preguntas
        $formulario = Formulario::with('preguntas')->findOrFail($asignacion->formulario_id);

        return view('evaluacion.llenar', compact('asignacion', 'formulario'));
    }

    // 🔹 Guardar evaluación respondida
    public function guardar(Request $request)
    {
        // Validación básica
        $request->validate([
            'asignacion_id' => 'required|exists:asignacion_evaluaciones,id',
            'respuestas' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            // Obtener asignación
            $asignacion = DB::table('asignacion_evaluaciones')
                ->where('id', $request->asignacion_id)
                ->first();

            // Guardar respuestas
            foreach ($request->respuestas as $pregunta_id => $valor) {
                DB::table('evaluacion_respuestas')->insert([
                    'asignacion_id' => $request->asignacion_id,
                    'pregunta_id'   => $pregunta_id,
                    'valor'         => $valor,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            // Calcular promedio
            $promedio = collect($request->respuestas)->avg();

            // Actualizar estado
            DB::table('asignacion_evaluaciones')
                ->where('id', $request->asignacion_id)
                ->update([
                    'estado' => 'Completada',
                    'puntuacion_total' => $promedio,
                    'updated_at' => now()
                ]);

            // Eliminar notificación asociada
            DB::table('notifications')
                ->where('notifiable_id', auth()->id())
                ->where('type', 'App\Notifications\EvaluacionAsignada')
                ->where('data->formulario_id', $asignacion->formulario_id)
                ->delete();

            DB::commit();

            return redirect()->route('evaluaciones.index')
                ->with('success', 'La evaluación se ha guardado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al guardar: ' . $e->getMessage());
        }
    }

    // 🔹 Cargar formulario con joins completos
    public function llenarFormulario($id)
    {
        $asignacion = DB::table('asignacion_evaluaciones')
            ->join('empleados as evaluado', 'asignacion_evaluaciones.empleado_id', '=', 'evaluado.id')
            ->leftJoin('departamentos', 'evaluado.departamento_id', '=', 'departamentos.id')
            ->leftJoin('empleados as evaluador', 'asignacion_evaluaciones.evaluador_id', '=', 'evaluador.id')
            ->select(
                'asignacion_evaluaciones.*',
                DB::raw("CONCAT(evaluado.nombre, ' ', evaluado.apellido) as nombre_completo_evaluado"),
                'evaluado.cargo',
                'departamentos.nombre as nombre_departamento',
                DB::raw("CONCAT(evaluador.nombre, ' ', evaluador.apellido) as nombre_completo_evaluador")
            )
            ->where('asignacion_evaluaciones.id', $id)
            ->first();

        if (!$asignacion) {
            return back()->with('error', 'Asignación no encontrada.');
        }

        $formulario = DB::table('evaluacion_formularios')
            ->where('id', $asignacion->formulario_id)
            ->first();

        $preguntas = DB::table('formulario_preguntas')
            ->where('formulario_id', $asignacion->formulario_id)
            ->get();

        return view('evaluaciones.llenar', compact('asignacion', 'formulario', 'preguntas'));
    }

    // Ver Historial de formularios llenados por proyecto.

    public function historialProyecto($proyectoId)
    {
    $historial = DB::table('asignacion_evaluaciones as a')
        ->join('empleados as e', 'a.empleado_id', '=', 'e.id')
        ->join('evaluacion_formularios as f', 'a.formulario_id', '=', 'f.id')
        ->leftJoin('departamentos as d', 'e.departamento_id', '=', 'd.id')
        ->select(
            'a.id',
            'a.formulario_id',
            'a.empleado_id',
            'f.nombre as formulario',
            DB::raw("CONCAT(e.nombre,' ',e.apellido) as colaborador"),
            'a.estado',
            'a.puntuacion_total',
            'a.created_at'
        )
        ->where('a.proyecto_id', $proyectoId)
        ->orderByDesc('a.created_at')
        ->get();

    return view('evaluaciones.historial_proyecto', compact('historial'));
    }

    // Ver el icono del ojito de los formularios llenados por proyecto.

    public function detalle($id)
    {
   $asignacion = DB::table('asignacion_evaluaciones as a')
    ->join('empleados as evaluado', 'a.empleado_id', '=', 'evaluado.id')
    ->join('empleados as evaluador', 'a.evaluador_id', '=', 'evaluador.id')
    ->join('evaluacion_formularios as f', 'a.formulario_id', '=', 'f.id')
    ->leftJoin('departamentos as d', 'evaluado.departamento_id', '=', 'd.id')
    ->select(
        'a.*',
        'a.estado',
        // evaluado
        DB::raw("CONCAT(evaluado.nombre,' ',evaluado.apellido) as nombre_completo_evaluado"),
        'evaluado.cargo as cargo',
        'd.nombre as nombre_departamento',

        // evaluador
        DB::raw("CONCAT(evaluador.nombre,' ',evaluador.apellido) as nombre_completo_evaluador"),

        // formulario (ESTO ES LO QUE TE FALTA)
        'f.nombre as formulario'
    )
    ->where('a.id', $id)
    ->first();

    if (!$asignacion) {
        abort(404, 'Evaluación no encontrada');
    }

    $preguntas = DB::table('formulario_preguntas')
        ->where('formulario_id', $asignacion->formulario_id)
        ->get();

    $respuestas = DB::table('evaluacion_respuestas')
        ->where('asignacion_id', $id)
        ->pluck('valor', 'pregunta_id');

    return view('evaluaciones.detalle', compact(
        'asignacion',
        'preguntas',
        'respuestas'
    ));
    }
}