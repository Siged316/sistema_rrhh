<?php

namespace App\Mail;                      // Namespace donde se encuentran las clases Mail del proyecto

use Illuminate\Bus\Queueable;           // Trait que permite colocar correos en cola (queue)
use Illuminate\Mail\Mailable;           // Clase base para crear correos personalizados en Laravel
use Illuminate\Mail\Mailables\Content;  // Clase para definir el contenido del correo
use Illuminate\Mail\Mailables\Envelope; // Clase para definir el encabezado y asunto del correo
use Illuminate\Queue\SerializesModels;  // Trait que permite serializar modelos automáticamente

class TareaAprobadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tarea;

    /**
     * Create a new message instance.
     */
    public function __construct($tarea)
    {
        $this->tarea = $tarea;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tarea Aprobada - IHCI',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tarea_aprobada', // Aquí es donde debe ir tu vista
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
