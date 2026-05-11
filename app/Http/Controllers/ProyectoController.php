<?php

namespace App\Http\Controllers;                    // Namespace donde se encuentra el controlador

use App\Models\Proyecto;                           // Modelo de proyectos
use App\Models\Departamento;                       // Modelo de departamentos
use App\Models\Tarea;                              // Modelo de tareas
use App\Mail\TareaCorregidaMail;                   // Clase Mail para enviar correo cuando una tarea es corregida
use App\Mail\TareaAprobadaMail;                    // Clase Mail para enviar correo cuando una tarea es aprobada
use App\Notifications\TareaCorregidaNotification;  // Notificación para informar que una tarea fue corregida
use App\Models\Empleado;                           // Modelo de empleados
use Illuminate\Http\Request;                       // Clase Request para manejar formularios y peticiones HTTP
use Illuminate\Support\Facades\Auth;               // Facade para acceder al usuario autenticado
use Illuminate\Support\Facades\DB;                 // Facade para realizar consultas SQL
use Illuminate\Support\Facades\Storage;            // Facade para manejo de archivos y almacenamiento

class ProyectoController extends Controller
{
    /**
     * Normaliza el rol incluso cuando viene como relación/modelo JSON.
     */
    private function rolActual($user = null): string
    {
        $user = $user ?: Auth::user();
        $rolRaw = $user->rol ?? '';

        if (is_object($rolRaw)) {
            $rolRaw = $rolRaw->slug
                ?? $rolRaw->nombre
                ?? $rolRaw->name
                ?? $rolRaw->descripcion
                ?? '';
        } elseif (is_array($rolRaw)) {
            $rolRaw = $rolRaw['slug']
                ?? $rolRaw['nombre']
                ?? $rolRaw['name']
                ?? $rolRaw['descripcion']
                ?? '';
        }

        return strtolower(trim((string) $rolRaw));
    }

    /**
     * Index con selector de proyecto + vista tipo diagrama de asignaciones.
     */
 public function index(Request $request)
{
    $user = Auth::user();
    $rol = $this->rolActual($user); 

    // Query base de proyectos
    $query = Proyecto::with(['usuario', 'designados'])->orderByDesc('created_at');

    // Filtro por roles (Admin/Jefe ven todo, otros solo lo propio)
    if (!str_contains($rol, 'admin') && !str_contains($rol, 'jefe')) {
        $query->where(function ($q) use ($user) {
            $q->where('empleado_id', $user->id)
                ->orWhereHas('designados', function ($d) use ($user) {
                    $d->where('user_id', $user->id);
                });
        });
    }

    $proyectos = $query->get();

    $proyectos = $query->paginate(5)->appends($request->all());

    // Capturamos el ID de la URL
    $proyectoSeleccionadoId = $request->query('proyecto_id');
    
    // Si no hay ID en la URL, el proyecto es null (esto limpia al recargar si navegas desde el menú)
    $proyectoSeleccionado = $proyectoSeleccionadoId ? $proyectos->firstWhere('id', $proyectoSeleccionadoId) : null;

    $diagramaAsignaciones = collect();

    if ($proyectoSeleccionado) {
        $diagramaAsignaciones = $proyectoSeleccionado->designados->map(function ($u) use ($proyectoSeleccionado) {
            // Buscamos tareas usando la columna REAL: asignado_user_id
            $tareasAsignadas = \App\Models\Tarea::where('proyecto_id', $proyectoSeleccionado->id)
                                ->where('asignado_user_id', $u->id)
                                ->get();

            return [
                'usuario' => $u->usuario,
                'es_encargado' => (int) $u->pivot->es_encargado === 1,
                'tareas' => $tareasAsignadas,
                'conteo' => $tareasAsignadas->count()
            ];
        });
    }

    return view('proyectos.index', [
        'proyectos' => $proyectos,
        'proyectoSeleccionado' => $proyectoSeleccionado,
        'diagramaAsignaciones' => $diagramaAsignaciones,
        'rol' => $rol,
        'departamentos' => Departamento::with('empleados')->orderBy('nombre')->get(), // Por si lo usas en el modal
    ]);
}

    public function store(Request $request)
    {
        // 1. Validación estricta incluyendo el array de tareas
        $request->validate([
            'nombre' => 'required|string|max:100',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'designados' => 'nullable|array',
            'tareas' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            // 2. Crear el registro principal del Proyecto
            $proyecto = Proyecto::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'empleado_id' => Auth::id(),
                'estado' => 'Pendiente',
                'progreso' => 0,
                'validado_jefe' => 0,
            ]);

            // 1. Obtener IDs marcados en los checkboxes
        $designadosIds = $request->input('designados', []);

        // 2. Extraer IDs de las tareas (por si asignaste a alguien de otro depto sin marcar el check)
        $tareas = $request->input('tareas', []);
        $idsDesdeTareas = collect($tareas)->pluck('asignado_user_id')->filter()->toArray();

        // 3. Fusionar ambos y eliminar duplicados para tener la lista real del equipo
        $equipoFinal = array_unique(array_merge($designadosIds, $idsDesdeTareas));

        // 4. Guardar en 'proyecto_designados' (image_26ba82.png)
        foreach ($equipoFinal as $userId) {
            $esEncargado = (int)$userId === (int)Auth::id() ? 1 : 0;
            $proyecto->designados()->attach($userId, ['es_encargado' => $esEncargado]);
        }

        // --- GUARDAR EN TABLA TAREAS (image_26bac2.png) ---
        if (!empty($tareas)) {
            foreach ($tareas as $tareaData) {
                if (!empty($tareaData['titulo'])) {
                    $proyecto->tareas()->create([
                        'asignado_user_id' => $tareaData['asignado_user_id'],
                        'titulo'         => $tareaData['titulo'],
                        'fecha_inicio'   => $tareaData['fecha_inicio'],
                        'fecha_fin'      => $tareaData['fecha_fin'],
                        'peso'           => $tareaData['peso'] ?? 10,
                        'completada'     => 0,
                    ]);
                }
            }
        }

            DB::commit();
           return redirect()->route('proyectos.index', ['proyecto_id' => $proyecto->id])
                     ->with('success', 'Proyecto institucional creado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

  // Para cargar las tareas en el modal

public function getTareas($id)
{
    $proyecto = Proyecto::with('tareas.responsable')->findOrFail($id);

    $tareas = $proyecto->tareas->map(function ($t) {
        return [
            'id' => $t->id,
            'titulo' => $t->titulo,
            'estado' => $t->estado,
            'peso' => $t->peso,
            'observaciones_empleado' => $t->observaciones_empleado,
            'asignado_user_id' => $t->asignado_user_id,
            'archivo_evidencia' => $t->archivo_evidencia,
            'archivo_url' => (!empty($t->archivo_evidencia) && Storage::disk('public')->exists($t->archivo_evidencia))
                ? Storage::disk('public')->url($t->archivo_evidencia)
                : null,
            'responsable' => $t->responsable ? [
    'id' => $t->responsable->id,
    // Accedemos a la relación 'empleado' del usuario
    'name' => ($t->responsable->empleado) 
                ? $t->responsable->empleado->nombre . ' ' . $t->responsable->empleado->apellido 
                : $t->responsable->usuario, // Si no tiene empleado, muestra el login
] : null,
        ];
    });

    return response()->json(['tareas' => $tareas]);
}

public function guardarDocumento(Request $request)
{
    // 1. Validar el archivo
    $request->validate([
        'archivo' => 'required|mimes:pdf,doc,docx,jpg,png|max:5120', // máx 5MB
        'tarea_id' => 'required|exists:tareas,id'
    ]);

    if ($request->hasFile('archivo')) {
        // 2. Obtener el archivo
        $archivo = $request->file('archivo');
        
        // 3. Generar un nombre único o usar el original
        $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
        
        // 4. Guardar en storage/app/public/documentos
        // Esto creará la carpeta "documentos" si no existe
        $ruta = $archivo->storeAs('documentos', $nombreArchivo, 'public');

        // 5. Guardar la ruta en la base de datos (opcional)
        // $tarea = Tarea::find($request->tarea_id);
        // $tarea->documento_path = $ruta;
        // $tarea->save();

        return response()->json([
            'success' => true,
            'mensaje' => 'Archivo guardado en public/storage/documentos',
            'url' => asset('storage/' . $ruta)
        ]);
    }

    return response()->json(['success' => false, 'mensaje' => 'No se subió ningún archivo'], 400);
}

public function enviarRevision(Request $request)
{
    $tarea = Tarea::findOrFail($request->id);

    if ($request->hasFile('archivo')) {
        $path = $request->file('archivo')->store('documentos', 'public');
        $tarea->archivo_evidencia = $path;
    }

    $tarea->observaciones_empleado = $request->observaciones;
    
    // DEBE ser exactamente 'En Revision' (con espacio, E y R mayúsculas)
    $tarea->estado = 'En Revision'; 
    
    $tarea->save();

    return response()->json([
        'success' => true,
        'proyecto_id' => $tarea->proyecto_id
    ]);
}

// Para actualizar el estado y recalcular el progreso
public function completarTarea(Request $request)
{
    $tarea = Tarea::findOrFail($request->id);
    
    // Guardamos el estado y lo que el empleado escribió
    $tarea->completada = $request->completada;
    $tarea->observaciones_empleado = $request->observaciones; 
    
    // Si la marca como completada, registramos la fecha actual como 'fecha_entrega'
    if($request->completada == 1) {
        $tarea->updated_at = now(); 
    }
    
    $tarea->save();

    return response()->json([
        'success' => true,
        'proyecto_id' => $tarea->proyecto_id,
        'nuevo_progreso' => $tarea->proyecto->progreso
    ]);
}

public function actualizarEstadoTarea(Request $request)
{
    $tarea = Tarea::findOrFail($request->id);
    
    // Si el empleado la manda a revisión
    if ($request->estado == 'En Revision') {
        $tarea->estado = 'En Revision';
        $tarea->observaciones_empleado = $request->notas;
        $tarea->fecha_entrega = now();
    } 
    
    // Si tú como jefe la apruebas
    if ($request->estado == 'Completado') {
        $tarea->estado = 'Completado';
        $tarea->completada = 1; // Para mantener compatibilidad con tu código anterior
    }

    $tarea->save();

    return response()->json([
        'success' => true,
        'estado_actual' => $tarea->estado,
        'proyecto_id' => $tarea->proyecto_id,
        'nuevo_progreso' => $tarea->proyecto->progreso // Se calcula solo con las 'Completadas'
    ]);
}



public function validarJefe(Request $request)
{
    try {
        $tarea = Tarea::findOrFail($request->id);
        $tarea->estado = 'Completado';
        $tarea->completada = 1;
        $tarea->save();

        // Buscamos al empleado igual que en la función de corregir
        $usuario = \App\Models\User::find($tarea->asignado_user_id);
        $empleado = \App\Models\Empleado::where('user_id', $usuario->id)->first();

        if ($empleado && !empty($empleado->email)) {
            // Usamos try-catch interno para que, si falla el mail, 
            // la base de datos SIEMPRE guarde el cambio
            try {
                \Mail::to($empleado->email)->send(new \App\Mail\TareaAprobadaMail($tarea));
            } catch (\Exception $e) {
                \Log::error("Fallo al enviar correo IHCI: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'proyecto_id' => $tarea->proyecto_id,
            'nuevo_progreso' => $tarea->proyecto->progreso
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

    public function updateProgress(Request $request, $id)
    {
        $proyecto = Proyecto::with('designados')->findOrFail($id);
        $rol = $this->rolActual(Auth::user());

        $esAdmin = str_contains($rol, 'admin');
        $esDuenio = ((int) $proyecto->empleado_id === (int) Auth::id());
        $esEncargado = $proyecto->designados->contains(function ($u) {
            return (int) $u->id === (int) Auth::id() && (int) $u->pivot->es_encargado === 1;
        });

        if (!$esAdmin && !$esDuenio && !$esEncargado) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este proyecto.');
        }

        $request->validate([
            'progreso' => 'required|integer|min:0|max:100',
            'estado' => 'required|in:Pendiente,En Proceso, En Revision, Completado, Cancelado, Rechazado',
        ]);

        $proyecto->update([
            'progreso' => $request->progreso,
            'estado' => $request->estado,
        ]);

        return redirect()->back()->with('success', 'Avance actualizado.');
    }

    public function validar($id)
    {
        $rol = $this->rolActual(Auth::user());

        if (!str_contains($rol, 'admin') && !str_contains($rol, 'jefe')) {
            return redirect()->back()->with('error', 'No tienes permiso para validar este proyecto.');
        }

        $proyecto = Proyecto::findOrFail($id);

        $proyecto->update([
            'validado_jefe' => 1,
            'creado_por' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Proyecto validado con éxito.');
    }

public function solicitarCorreccion(Request $request)
{
    try {
        $tarea = Tarea::findOrFail($request->id);
        $tarea->observaciones_jefe = $request->observaciones_jefe;
        $tarea->estado = 'Pendiente';
        $tarea->save();

        // 1. Buscamos al USUARIO (porque la tarea usa asignado_user_id)
        $usuario = \App\Models\User::find($tarea->asignado_user_id);

        // 2. Del usuario, saltamos al EMPLEADO (usando la relación que ya tienes)
        // Si no tienes la relación en el modelo User, la buscamos manualmente:
        $empleado = \App\Models\Empleado::where('user_id', $usuario->id)->first();

        if ($empleado && !empty($empleado->email)) {
            try {
                // USAMOS LA MISMA LÓGICA DE TU OTRO MÓDULO
                \Mail::to($empleado->email)->send(new \App\Mail\TareaCorregidaMail($tarea));
            } catch (\Exception $mailEx) {
                // Si el correo falla, lo ignoramos para que el modal se cierre bien
                \Log::error("Error de correo: " . $mailEx->getMessage());
            }
        }

        return response()->json([
            'success' => true, 
            'proyecto_id' => $tarea->proyecto_id
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'error' => $e->getMessage()
        ], 500);
    }
}

// 1. Para llenar la lista de la derecha (Colaboradores Disponibles)
public function getEmpleados($id) {
    // Asegúrate de que el modelo Empleado tenga el campo user_id
    return response()->json(
        \App\Models\Empleado::where('departamento_id', $id)
            ->where('estado', 'activo')
            ->get(['id', 'user_id', 'nombre', 'apellido'])
    );
}

public function edit($id)
{
    try {
        // 1. Buscamos el proyecto con sus tareas (esto usa la tabla 'tareas')
        $proyecto = \App\Models\Proyecto::with('tareas')->findOrFail($id);

        // 2. Buscamos los usuarios usando la tabla 'proyecto_designados'
        $colaboradores = \App\Models\User::whereHas('proyectos', function($q) use ($id) {
            $q->where('proyecto_designados.proyecto_id', $id);
        })
        ->with('empleado')
        ->get()
        ->map(function($u) {
            return [
                'id' => (string)$u->id,
                'nombre' => $u->empleado 
                    ? ($u->empleado->nombre . ' ' . $u->empleado->apellido) 
                    : $u->usuario
            ];
        });

        return response()->json([
            'proyecto' => $proyecto,
            'colaboradores_actuales' => $colaboradores
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
public function update(Request $request, $id)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
        'designados' => 'required|array|min:1',
        'tareas' => 'nullable|array',
        'tareas.*.titulo' => 'required|string',
        'tareas.*.asignado_user_id' => 'required',
    ]);

    DB::beginTransaction();
    try {
        $proyecto = Proyecto::findOrFail($id);
        
        $proyecto->update($request->only('nombre', 'fecha_inicio', 'fecha_fin'));

        // Sincronizar equipo (Usando tu tabla proyecto_designados)
        $proyecto->designados()->sync($request->designados);

        $tareasMantenerIds = [];

        if ($request->has('tareas')) {
            foreach ($request->tareas as $tareaData) {
                if (isset($tareaData['id']) && !empty($tareaData['id'])) {
                    $tarea = Tarea::findOrFail($tareaData['id']);
                    $tarea->update($tareaData);
                    $tareasMantenerIds[] = $tarea->id;
                } else {
                    $nuevaTarea = $proyecto->tareas()->create([
                        'titulo' => $tareaData['titulo'],
                        'asignado_user_id' => $tareaData['asignado_user_id'],
                        'fecha_inicio' => $tareaData['fecha_inicio'],
                        'fecha_fin' => $tareaData['fecha_fin'],
                        'peso' => $tareaData['peso'] ?? 10,
                        'estado' => 'Pendiente'
                    ]);
                    $tareasMantenerIds[] = $nuevaTarea->id;
                }
            }
        }

        $proyecto->tareas()->whereNotIn('id', $tareasMantenerIds)->delete();

        DB::commit();

        // RESPUESTA JSON PARA AJAX
        return response()->json([
            'status' => 'success',
            'message' => '¡Proyecto y equipo actualizados con éxito!'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Error al guardar: ' . $e->getMessage()
        ], 500);
    }
}



}
