<?php

namespace App\Mail;                      // Namespace donde se encuentran las clases Mail del proyecto

use Illuminate\Bus\Queueable;           // Trait que permite colocar correos en cola (queue)
use Illuminate\Mail\Mailable;           // Clase base para crear correos personalizados en Laravel
use Illuminate\Queue\SerializesModels;  // Trait que permite serializar modelos automáticamente
use App\Models\Tarea;                   // Modelo de tareas

// Clase de correo para notificar que una tarea necesita correcciones
class TareaCorregidaMail extends Mailable
{
    // Traits de Laravel
    // Queueable: permite enviar el correo usando colas
    // SerializesModels: serializa automáticamente modelos Eloquent
    use Queueable, SerializesModels;

    // Variable pública que almacenará la tarea
    // Será accesible desde la vista del correo
    public $tarea;

    // Constructor del correo
    // Recibe una instancia del modelo Tarea
    public function __construct(Tarea $tarea)
    {
        // Guarda la tarea en la propiedad pública
        $this->tarea = $tarea;
    }

    // Método encargado de construir el correo
    public function build()
    {
        return $this

            // Define el asunto del correo
            ->subject('Corrección Requerida: ' . $this->tarea->titulo)

            // Define la vista Blade que se usará como contenido del correo
            ->view('emails.tarea_corregida');
    }
}