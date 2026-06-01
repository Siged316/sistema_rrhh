<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/*
|--------------------------------------------------------------------------
| Notificación NuevaHoraExtra
|--------------------------------------------------------------------------
| Esta clase se encarga de crear una notificación cuando se registra
| una nueva solicitud de horas extras.
|
| Extiende de Notification, que es la clase base de Laravel para
| manejar notificaciones.
|--------------------------------------------------------------------------
*/
class NuevaHoraExtra extends Notification
{
    /*
    |--------------------------------------------------------------------------
    | Propiedad protegida
    |--------------------------------------------------------------------------
    | Almacena la información de la solicitud de horas extras que será
    | utilizada para construir la notificación.
    |--------------------------------------------------------------------------
    */
    protected $horaExtra;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Recibe el objeto de horas extras y lo guarda en la propiedad
    | $horaExtra para usarlo posteriormente.
    |--------------------------------------------------------------------------
    */
    public function __construct($horaExtra)
    {
        $this->horaExtra = $horaExtra;
    }

    /*
    |--------------------------------------------------------------------------
    | Canales de notificación
    |--------------------------------------------------------------------------
    | Define por qué medio será enviada la notificación.
    |
    | 'database' indica que la notificación se almacenará en la tabla
    | notifications de la base de datos.
    |--------------------------------------------------------------------------
    */
    public function via($notifiable)
    {
        return ['database'];
    }

    /*
    |--------------------------------------------------------------------------
    | Datos que se guardarán en la base de datos
    |--------------------------------------------------------------------------
    | Este método retorna un arreglo con la información que Laravel
    | almacenará en la columna "data" de la tabla notifications.
    |--------------------------------------------------------------------------
    */
    public function toArray($notifiable)
    {
        return [

            // ID de la solicitud de horas extras
            'id' => $this->horaExtra->id,

            // Tipo de notificación para identificarla fácilmente
            'tipo' => 'horas_extras',

            // Mensaje que verá el usuario en la notificación
            // Si no existe nombre, muestra "Sin nombre"
            'mensaje' => 'Nueva solicitud de horas extras: ' .
                ($this->horaExtra->nombre ?? 'Sin nombre'),

            // Ruta a la que será redirigido el usuario
            // al hacer clic en la notificación
            'url' => '/horas-extras/gestion',
        ];
    }
}