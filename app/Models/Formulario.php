<?php

// Namespace del modelo
// Todos los modelos de Laravel normalmente se ubican en App\Models
namespace App\Models;


use Illuminate\Database\Eloquent\Model;          // Importamos la clase base Model de Eloquent

// Modelo Formulario
// Representa la tabla de formularios de evaluación
class Formulario extends Model
{
    // Nombre exacto de la tabla en la base de datos
    protected $table = 'evaluacion_formularios';

    // Campos que pueden asignarse masivamente (Mass Assignment)
    protected $fillable = [

        // Nombre del formulario
        'nombre',

        // Descripción general
        'descripcion',

        // Estado del formulario (activo/inactivo)
        'activo',

        // Labels personalizados de calificación
        'label_5',
        'label_4',
        'label_3',
        'label_2',
        'label_1',
    ];

    // Indicamos que esta tabla NO usa created_at ni updated_at
    public $timestamps = false;

    /**
     * RELACIÓN:
     * Un formulario tiene muchas preguntas
     */
    public function preguntas()
    {
        return $this->hasMany(FormularioPregunta::class, 'formulario_id');
    }

    /**
     * RELACIÓN:
     * Un formulario puede pertenecer a un proyecto
     */
    public function proyecto()
    {
        return $this->hasOne(Proyecto::class, 'formulario_id', 'id');
    }
}