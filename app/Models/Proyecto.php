<?php

// Define el namespace donde está el modelo
namespace App\Models;

// Importa la clase base Model de Eloquent
use Illuminate\Database\Eloquent\Model;

// Definición del modelo Proyecto
class Proyecto extends Model
{
    // Especifica el nombre de la tabla en la base de datos
    protected $table = 'proyectos';

    // Campos que se pueden llenar de forma masiva (mass assignment)
    // Permite usar create(), update(), etc. sin errores de seguridad
    protected $fillable = [
        'nombre',          // Nombre del proyecto
        'descripcion',     // Descripción detallada
        'fecha_inicio',    // Fecha de inicio
        'fecha_fin',       // Fecha de finalización
        'progreso',        // Porcentaje de avance (ej: 0-100)
        'validado_jefe',   // Indica si el jefe validó el proyecto (boolean)
        'empleado_id',     // ID del empleado responsable
        'formulario_id',     // ID del formulario
        'estado',          // Estado del proyecto (activo, finalizado, etc.)
        'creado_por'       // Usuario que creó el proyecto (jefe/admin)
    ];

    /**
     * RELACIÓN: Proyecto → Usuario (Responsable)
     * -----------------------------------------
     * Indica que un proyecto pertenece a un usuario (empleado responsable).
     * 
     */
    public function usuario()
    {
        // belongsTo:
        // muchos proyectos pertenecen a un usuario
        return $this->belongsTo(User::class, 'empleado_id');
    }

    /**
     * RELACIÓN: Proyecto → Usuario (Creador)
     * -----------------------------------------
     * Indica quién creó el proyecto (normalmente jefe o administrador).
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
 * RELACIÓN: Proyecto → Usuarios designados
 * -----------------------------------------
 * Define una relación muchos a muchos entre proyectos y usuarios
 * usando la tabla intermedia "proyecto_designados".
 */
public function designados()
{
    return $this->belongsToMany(
                    User::class,           // Modelo relacionado (usuarios)
                    'proyecto_designados', // Tabla pivote/intermedia
                    'proyecto_id',         // FK en la tabla pivote que apunta a proyectos
                    'user_id'              // FK en la tabla pivote que apunta a usuarios
                )
                // Incluye el campo extra de la tabla pivote
                ->withPivot('es_encargado');
}

/**
 * RELACIÓN: Proyecto → Tareas
 * ---------------------------------
 * Indica que un proyecto tiene muchas tareas asociadas.
 */
public function tareas()
{
    // hasMany:
    // Un proyecto puede tener múltiples tareas
    return $this->hasMany(Tarea::class, 'proyecto_id');
}

/**
 * MÉTODO: actualizarProgreso
 * ---------------------------------
 * Calcula el progreso del proyecto en base al peso de sus tareas.
 * El progreso es proporcional (no por cantidad, sino por importancia).
 */
public function actualizarProgreso()
{
    // Obtiene todas las tareas relacionadas al proyecto
    $tareas = $this->tareas;
    
    // Si no hay tareas, el progreso es 0
    if ($tareas->isEmpty()) {
        $this->update(['progreso' => 0]);
        return;
    }

    // Suma el peso total de todas las tareas
    // (cada tarea puede tener diferente importancia)
    $totalPeso = $tareas->sum('peso');

    // Suma SOLO el peso de las tareas completadas
    // (completada = 1 significa terminada)
    $pesoCompletado = $tareas->where('completada', 1)->sum('peso');

    // Calcula el porcentaje real basado en peso
    // Ejemplo: si completaste tareas que suman 40 de 100 → 40%
    $nuevoProgreso = ($totalPeso > 0) 
        ? round(($pesoCompletado / $totalPeso) * 100) 
        : 0;

    // Actualiza el proyecto:
    // - progreso calculado
    // - estado automático según avance
    $this->update([
        'progreso' => $nuevoProgreso,

        // Si llega a 100% → completado
        // Si no → en proceso
        'estado' => $nuevoProgreso == 100 ? 'completado' : 'En Proceso'
    ]);
}

public function formulario()
{
    return $this->belongsTo(Formulario::class, 'formulario_id', 'id');
}

public function recalcularEstado()
{
    // Cuenta tareas que NO están completadas (completada = 0)
    $tareasPendientes = $this->tareas()->where('completada', 0)->count();
    
    // Si no hay tareas pendientes y hay al menos una tarea, marcar como completado
    if ($tareasPendientes === 0 && $this->tareas()->count() > 0) {
        $this->estado = 'Completado';
    } else {
        // Si hay tareas pendientes, puedes dejarlo como "En Proceso"
        $this->estado = 'En Proceso';
    }
    
    $this->save();
}
}