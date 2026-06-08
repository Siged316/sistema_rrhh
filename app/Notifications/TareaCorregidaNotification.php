<?php

// Namespace donde se almacenan las notificaciones del sistema
namespace App\Notifications;


use Illuminate\Bus\Queueable;                       // Trait que permite manejar la notificación mediante colas (queues)
use Illuminate\Contracts\Queue\ShouldQueue;        // Interfaz que indica que la notificación será enviada en segundo plano usando colas
use Illuminate\Notifications\Messages\MailMessage; // Clase utilizada para construir mensajes de correo electrónicos
use Illuminate\Notifications\Notification;        // Clase base para crear notificaciones personalizadas en Laravel


class TareaCorregidaNotification extends Notification
{
    use Queueable;

    protected $tarea;

    public function __construct($tarea)
    {
        $this->tarea = $tarea;
    }

    public function via($notifiable)
    {
        // Agregamos 'database' para que aparezca en la campanita
        // Y 'mail' si quieres que siga enviando el correo
        return ['database', 'mail'];
    }

    public function toArray($notifiable)
    {
        // Estas claves deben coincidir con lo que tu Blade busca
        return [
         'mensaje' => $this->mensaje, // Ej: "Tu tarea X ha sido aprobada"
         'url' => route('proyectos.index'), // O la ruta específica
         'tipo' => 'tarea' // Para usar en tu match() del blade
        ];
    }
}