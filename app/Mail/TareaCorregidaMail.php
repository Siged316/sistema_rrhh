<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Tarea;

class TareaCorregidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tarea;

    public function __construct(Tarea $tarea)
    {
        $this->tarea = $tarea;
    }

    public function build()
    {
        return $this
            ->subject('Corrección Requerida: ' . $this->tarea->titulo)
            ->view('emails.tarea_corregida'); // Crearemos esta vista ahora
    }
}