<?php

namespace App\Http\Controllers;            // Namespace donde se encuentra el controlador

use Illuminate\Http\Request;               // Importación de Request para manejar peticiones HTTP y formularios
use App\Models\Formulario;                 // Modelo de formularios
use App\Models\Empleado;                   // Modelo de empleado
use App\Models\Proyecto;                   // Modelo de proyecto
use App\Models\user;                       // Modelo de usuarios del sistema
use App\Notifications\EvaluacionAsignada;  // Modelo de evaluación 
use Illuminate\Support\Facades\DB;         // Facade para realizar consultas SQL con Query Builder
use Illuminate\Support\Facades\Validator;  // Facade para validaciones manuales

class FormularioController extends Controller
{
    // Mostrar la lista de preguntas
    public function index()
    {
     $proyectos = Proyecto::all();

     $formularios = Formulario::all();
     $soloEmpleados = Empleado::where('estado', 'activo')->orderBy('nombre', 'asc')->get();
    
     // Obtenemos los departamentos reales de tu tabla
     $departamentos = \DB::table('departamentos')
        ->whereNotNull('jefe_empleado_id')
        ->select('id', 'nombre')
        ->get();

     // Obtenemos los jefes vinculados a esos departamentos
     $todosLosJefes = Empleado::join('departamentos', 'empleados.id', '=', 'departamentos.jefe_empleado_id')
        ->where('empleados.estado', 'activo')
        ->select(
            'empleados.id', 
            'empleados.nombre', 
            'empleados.apellido', 
            'empleados.cargo', 
            'departamentos.id as departamento_id' // Esto permite el filtro JS
        )
        ->get();

         $proyectos = Proyecto::all();

         return view('formulario.index', compact('formularios', 'soloEmpleados', 'todosLosJefes', 'departamentos',  'proyectos'));
    }

    // Guardar la pregunta nueva
    // --- FUNCIÓN STORE (Cuando creas el nombre) ---
    public function store(Request $request)
    {
     // Limpieza de puntos y espacios
     $nombreLimpio = trim($request->nombre, " .");
     $request->merge(['nombre' => $nombreLimpio]);

     // Validación manual para responder vía AJAX
     $validator = Validator::make($request->all(), [
         'nombre' => 'required|string|max:255|unique:evaluacion_formularios,nombre',
         'proyecto_id' => 'required|exists:proyectos,id',
        ], [
         'nombre.unique' => 'Ya existe un formulario con este nombre (incluyendo variaciones con puntos).'
       ]);

       if ($validator->fails()) {
         return response()->json(['errors' => $validator->errors()], 422);
        }

       DB::beginTransaction();
        try {
          $nuevoFormulario = Formulario::create([
              'nombre' => $request->nombre,
              'label_5' => 'Superior (5)',
              'label_4' => 'Expectativas (4)',
              'label_3' => 'Cumple (3)',
              'label_2' => 'Debe Mejorar (2)',
              'label_1' => 'Insatisfactorio (1)',
              'activo' => true
            ]);

           $proyecto = Proyecto::findOrFail($request->proyecto_id);
           $proyecto->update(['formulario_id' => $nuevoFormulario->id]);

           DB::commit();

           // Al crear el nuevo formulario, establecemos la sesión en true
           session(['esNuevo' => true]);

           // Enviamos la URL de redirección solo si todo salió bien
           return response()->json([
              'success' => true,
             'redirect' => route('formulario.show', $nuevoFormulario->id)
            ]);

        } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['error' => 'Error técnico: ' . $e->getMessage()], 500);
        }
    }

    //Guarda la asignación del formulario
    public function asignarStore(Request $request)
    {
       $request->validate([
        'formulario_id' => 'required',
        'empleado_id'   => 'required|array',
        'tipo'          => 'required',
        'peso_jefe'     => 'nullable|array',
        'proyecto_id'   => 'nullable' // Validamos con el nombre correcto
       ]);

       try {
        DB::beginTransaction();
        $creados = 0;
        $omitidos = 0;

        // Definimos la variable única para todo el proceso
        // Si no viene en el request, la rescatamos de la tabla proyectos
        $proyecto_id = $request->proyecto_id ?? DB::table('proyectos')
            ->where('formulario_id', $request->formulario_id)
            ->value('id');

        foreach ($request->empleado_id as $emp_id) {
            
            // --- AUTOEVALUACIÓN ---
            if ($request->tipo === 'Autoevaluacion') {
                $existe = DB::table('asignacion_evaluaciones')
                            ->where('formulario_id', $request->formulario_id)
                            ->where('empleado_id', $emp_id)
                            ->where('evaluador_id', $emp_id)
                            ->where('tipo', 'Autoevaluacion')
                            ->exists();

                if (!$existe) {
                    $this->registrarAsignacion(
                      $request->formulario_id, 
                      $emp_id, 
                      $emp_id, 
                      'Autoevaluacion', 
                       0, 
                       $proyecto_id // Variable unificada
                    );
                   
                    $usuario = User::where('empleado_id', $emp_id)->first();
                    if ($usuario) {
                        $usuario->notify(new EvaluacionAsignada('Autoevaluación', $request->formulario_id));
                    }
                    $creados++;
                } else {
                    $omitidos++;
                }
            } 
            
            // --- EVALUACIÓN DE JEFE ---
            elseif ($request->tipo === 'Evaluacion Jefe') {
                if ($request->has('evaluador_id') && is_array($request->evaluador_id)) {
                    foreach ($request->evaluador_id as $jefe_id) {
                        $existeJefe = DB::table('asignacion_evaluaciones')
                                        ->where('formulario_id', $request->formulario_id)
                                        ->where('empleado_id', $emp_id)
                                        ->where('evaluador_id', $jefe_id)
                                        ->where('tipo', 'Evaluacion Jefe')
                                        ->exists();

                        if (!$existeJefe) {
                            $peso = $request->peso_jefe[$jefe_id] ?? 0;

                            $this->registrarAsignacion(
                               $request->formulario_id,
                                $emp_id,
                                $jefe_id,
                               'Evaluacion Jefe',
                               $peso,
                               $proyecto_id // Variable unificada
                            );
                         
                            $jefe = User::where('empleado_id', $jefe_id)->first();
                            if ($jefe) {
                               $jefe->notify(new EvaluacionAsignada('Evaluación de Jefe', $request->formulario_id));
                            }
                            $creados++;
                        } else {
                            $omitidos++;
                        }
                    }
                }
            }
        }

        DB::commit();

        if ($creados > 0 && $omitidos === 0) {
            return back()->with('success', "Se crearon $creados nuevas asignaciones vinculadas al proyecto.");
        } 
        elseif ($creados > 0 && $omitidos > 0) {
            return back()->with('warning', "Se crearon $creados asignaciones, pero se omitieron $omitidos duplicadas.");
        } 
        else {
            return back()->with('info', "No hubo cambios. Las asignaciones ya existen.");
        }

       } catch (\Exception $e) {
          DB::rollBack();
          return back()->with('error', 'Error técnico: ' . $e->getMessage());
       }
    }

   // Función privada para insertar en la BD
   private function registrarAsignacion($form_id, $emp_id, $eval_id, $tipo,  $peso = 0, $proyecto_id = null)
   {
     DB::table('asignacion_evaluaciones')->insert([
         'formulario_id' => $form_id,
         'empleado_id'   => $emp_id,
         'evaluador_id'  => $eval_id,
         'proyecto_id'   => $proyecto_id,
         'tipo'          => $tipo,
         'peso'          => $peso,
         'estado'        => 'Pendiente',
         'created_at'    => now(),
         'updated_at'    => now()
        ]);
    }

    // Función auxiliar para evitar repetir código
    private function crearAsignacion($form_id, $emp_id, $eval_id, $tipo)
    {
    DB::table('asignacion_evaluaciones')->insert([
        'formulario_id' => $form_id,
        'empleado_id'   => $emp_id,
        'evaluador_id'  => $eval_id,
        'tipo'          => $tipo,
        'estado'        => 'Pendiente',
        'created_at'    => now(),
        'updated_at'    => now()
    ]);
    }

    // Esta función es para ver las preguntas dentro de ese formulario
    public function show($id) {
     // 1. Buscamos el formulario y sus preguntas (tu lógica original)
     $formulario = Formulario::with('preguntas')->findOrFail($id);

     // 2. Lógica para el botón: 
     // Si venimos del 'store', la sesión tendrá 'esNuevo'. 
     // Si entramos normal para editar, no tendrá nada y será 'false'.
     $esNuevo = session()->pull('esNuevo', false);

      // 3. Enviamos AMBAS variables a la vista
      return view('formulario.show', compact('formulario', 'esNuevo'));
    }

    // Actualiza Nombre y Labels de las columnas
    public function update(Request $request, $id)
    {
     $formulario = Formulario::findOrFail($id);
     $formulario->update($request->all()); 
     return back()->with('success', 'Títulos actualizados');
    }

   // Actualiza un criterio/pregunta individual
   public function actualizarPregunta(Request $request, $id)
   {
    // Asegúrate de importar el modelo Pregunta arriba o usar la ruta completa
    $pregunta = \App\Models\FormularioPregunta::findOrFail($id);
    
    $pregunta->update([
        'pregunta' => $request->pregunta,
        'categoria' => $request->categoria
    ]);
    
    return back()->with('success', 'Criterio actualizado correctamente.');
   }

  // Método para agregar una nueva pregunta al formulario
   public function agregarPregunta(Request $request, $id)
   {
    // 1. Validar que no haya duplicados en el array enviado
    $preguntas = $request->preguntas;
    
    // Convertimos a minúsculas y quitamos espacios para comparar bien
    $preguntasNormalizadas = array_map(function($p) { return trim(strtolower($p)); }, $preguntas);

    if (count($preguntasNormalizadas) !== count(array_unique($preguntasNormalizadas))) {
        return back()->with('error', '¡Error! Has incluido preguntas duplicadas en la lista.');
    }

    // 2. Validar contra la Base de Datos (Seguridad extra)
    foreach ($preguntas as $texto) {
        $existeEnBD = \App\Models\FormularioPregunta::where('formulario_id', $id)
            ->where('pregunta', $texto)
            ->exists();

        if ($existeEnBD) {
            return back()->with('error', "La pregunta '$texto' ya existe en este formulario.");
        }
    }

    // 3. Guardar
    $categorias = $request->categorias ?? [];
    foreach ($preguntas as $index => $texto) {
        \App\Models\FormularioPregunta::create([
            'formulario_id' => $id,
            'pregunta'      => $texto,
            'categoria'     => $categorias[$index] ?? 'General'
        ]);
    }

    return back()->with('success', 'Preguntas añadidas correctamente.');
   }


    // Método para eliminar una pregunta existente
    public function eliminarPregunta($id)
    {
     // Busca la pregunta por ID
     // Si no existe, Laravel mostrará error 404 automáticamente
      $p = FormularioPregunta::findOrFail($id);

     // Elimina la pregunta de la base de datos
     $p->delete();

     // Regresa a la página anterior con mensaje de confirmación
     return back()->with('success', 'Pregunta eliminada.');
    }

    
   //Borrar las notificaciones
   public function destroy($id)
   {
    DB::beginTransaction();
    try {
        $formulario = Formulario::findOrFail($id);

        // 1. Borrar las notificaciones de la base de datos asociadas a este formulario
        // Esto evita que el usuario haga clic en una notificación de un formulario que ya no existe.
        DB::table('notifications')
            ->where('type', 'App\Notifications\EvaluacionAsignada')
            ->where('data->formulario_id', $id)
            ->delete();

        // 2. Eliminar el formulario (esto debería eliminar preguntas en cascada si lo tienes configurado)
        $formulario->delete();

        DB::commit();
        return redirect()->route('formulario.index')->with('success', 'Formulario y notificaciones eliminados.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al eliminar: ' . $e->getMessage());
    }
   }
}