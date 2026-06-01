<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HoraExtra;
use App\Models\Empleado;
use App\Models\Departamento;
use App\Notifications\NuevaHoraExtra;

/*
|--------------------------------------------------------------------------
| Comando para notificar horas extras pendientes
|--------------------------------------------------------------------------
| Este comando busca todas las solicitudes de horas extras que aún
| no han sido notificadas y envía una notificación al jefe del
| departamento correspondiente.
|--------------------------------------------------------------------------
*/
class NotificarHorasExtrasPendientes extends Command
{
    // Nombre con el que se ejecutará el comando:
    // php artisan horas-extras:notificar
    protected $signature = 'horas-extras:notificar';

    // Descripción mostrada en Artisan
    protected $description = 'Notifica al jefe del departamento sobre nuevas solicitudes de horas extras';

    /*
    |--------------------------------------------------------------------------
    | Método principal del comando
    |--------------------------------------------------------------------------
    */
    public function handle()
    {
        // Obtener solicitudes pendientes de notificación
        $solicitudes = HoraExtra::where('notificacion_enviada', 0)->get();

        // Mostrar cuántas solicitudes se encontraron
        $this->info('Solicitudes pendientes: ' . $solicitudes->count());

        // Recorrer cada solicitud pendiente
        foreach ($solicitudes as $horaExtra) {

            $this->info('Procesando hora extra ID: ' . $horaExtra->id);

            // Intentar enviar la notificación
            $notificada = $this->notificarJefeDepartamento($horaExtra);

            if ($notificada) {

                // Marcar la solicitud como notificada
                $horaExtra->update([
                    'notificacion_enviada' => 1,
                ]);

                $this->info('Notificada correctamente.');
            } else {

                // Mostrar advertencia si falló la notificación
                $this->warn('No se pudo notificar esta solicitud.');
            }
        }

        return Command::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Notificar al jefe del departamento
    |--------------------------------------------------------------------------
    | Obtiene el empleado asociado a la hora extra, identifica su
    | departamento, localiza al jefe y le envía una notificación.
    |--------------------------------------------------------------------------
    */
    private function notificarJefeDepartamento($horaExtra)
    {
        // Obtener empleado relacionado con la hora extra
        $empleado = $horaExtra->empleado;

        if (!$empleado) {
            $this->warn('La hora extra no tiene empleado relacionado.');
            return false;
        }

        // Verificar que el empleado tenga departamento asignado
        if (!$empleado->departamento_id) {
            $this->warn('El empleado no tiene departamento_id.');
            return false;
        }

        // Buscar departamento del empleado
        $departamento = Departamento::find($empleado->departamento_id);

        if (!$departamento) {
            $this->warn('No se encontró el departamento ID: ' . $empleado->departamento_id);
            return false;
        }

        // Verificar que exista un jefe asignado al departamento
        if (!$departamento->jefe_empleado_id) {
            $this->warn('El departamento no tiene jefe_empleado_id.');
            return false;
        }

        // Obtener empleado que es jefe del departamento
        $jefeEmpleado = Empleado::find($departamento->jefe_empleado_id);

        if (!$jefeEmpleado) {
            $this->warn('No se encontró el empleado jefe ID: ' . $departamento->jefe_empleado_id);
            return false;
        }

        // Verificar que el jefe tenga un usuario asociado
        if (!$jefeEmpleado->user) {
            $this->warn('El jefe empleado no tiene usuario relacionado.');
            return false;
        }

        // Enviar notificación al usuario del jefe
        $jefeEmpleado->user->notify(new NuevaHoraExtra($horaExtra));

        // Confirmar envío en consola
        $this->info('Notificación enviada al usuario ID: ' . $jefeEmpleado->user->id);

        return true;
    }
}