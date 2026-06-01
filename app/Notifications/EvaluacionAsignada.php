<?php
namespace App\Notifications;                  // Namespace donde se encuentran las notificaciones del proyecto

use Illuminate\Bus\Queueable;                 // Trait que permite usar colas para enviar notificaciones
use Illuminate\Notifications\Notification;    // Clase base para crear notificaciones en Laravel

class EvaluacionAsignada extends Notification
{
    use Queueable;             // Permite que la notificación pueda enviarse mediante colas

    protected $tipo;          //Almacenar el tipo de evaluación
    protected $formulario_id;  //Almacenar el ID del formulario

    // Recibimos el tipo y el ID del formulario desde el controlador
    public function __construct($tipo = 'Evaluación', $formulario_id = null)
    {
        $this->tipo = $tipo;
        $this->formulario_id = $formulario_id;
    }

    public function via($notifiable)
    {
        return ['database']; // O 'mail' si también usas correo
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje' => "Se te ha asignado una nueva " . $this->tipo,
            // Guardamos la URL hacia el index de evaluaciones
            'url' => route('evaluaciones.index'), 
            'formulario_id' => $this->formulario_id,
        ];
    }

}