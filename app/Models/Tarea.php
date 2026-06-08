<?php

// Define el namespace donde está el modelo
namespace App\Models;

// Importa la clase base Model de Eloquent
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    // 1. Campos permitidos para asignación masiva
    protected $fillable = [
        'proyecto_id', 
        'asignado_user_id', 
        'titulo', 
        'fecha_inicio', 
        'fecha_fin', 
        'peso', 
        'completada',
        'fecha_entrega', 
        'estado',                
        'archivo_evidencia',      
        'observaciones_empleado',
        'observaciones_jefe'
    ];

    /**
     * MÉTODO ESTÁTICO: booted
     * Se ejecuta automáticamente para sincronizar el progreso del proyecto
     */
    protected static function booted()
    {
        // Cuando una tarea se marca como completada o cambia su peso
        static::updated(function ($tarea) {
            // Solo actualizar si realmente ha cambiado algo relevante
           if ($tarea->isDirty(['completada', 'peso']) && $tarea->proyecto) {
            $tarea->proyecto->actualizarProgreso();
           }
        });
    }

    /**
     * RELACIÓN: Tarea -> Proyecto
     */
    public function proyecto()
    {
        // Definida una sola vez para evitar el error "Cannot redeclare"
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * RELACIÓN: Tarea -> Usuario (Responsable)
     */
    public function responsable()
    {
       return $this->belongsTo(\App\Models\Empleado::class, 'asignado_user_id', 'user_id');
    }

     /**
     * RELACIÓN: Tarea -> historial (Responsable)
     */
    // app/Models/Tarea.php

    public function historial()
    {
      // Una tarea tiene muchas observaciones en el historial
      return $this->hasMany(HistorialObservacion::class, 'tarea_id')->orderBy('created_at', 'ASC');
    }


}