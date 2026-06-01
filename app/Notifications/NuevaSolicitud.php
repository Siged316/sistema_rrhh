<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NuevaSolicitud extends Notification
{
    use Queueable;

    protected $empleado;
    protected $solicitudId;

    public function __construct($empleado, $solicitudId)
    {
        $this->empleado = $empleado;
        $this->solicitudId = $solicitudId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
{
    return [
        'tipo' => 'solicitud',
        'solicitud_id' => $this->solicitudId,
        'mensaje' => 'Nueva solicitud recibida',
        'url' => route('solicitudes.index', [], false) . '?solicitud_id=' . $this->solicitudId,
    ];
}
}