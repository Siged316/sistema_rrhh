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
        'estado',                
        'archivo_evidencia',      
        'observaciones_empleado'  
    ];

    /**
     * MÉTODO ESTÁTICO: booted
     * Se ejecuta automáticamente para sincronizar el progreso del proyecto
     */
    protected static function booted()
    {
        // Cuando una tarea se marca como completada o cambia su peso
        static::updated(function ($tarea) {
            if ($tarea->proyecto) {
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
        return $this->belongsTo(User::class, 'asignado_user_id');
    }
}