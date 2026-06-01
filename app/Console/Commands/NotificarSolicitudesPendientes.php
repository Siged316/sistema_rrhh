<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Solicitud;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Notifications\NuevaSolicitud;

class NotificarSolicitudesPendientes extends Command
{
    protected $signature = 'solicitudes:notificar';

    protected $description = 'Notifica al jefe del departamento sobre nuevas solicitudes de permisos';

    public function handle()
    {
        $solicitudes = Solicitud::where('notificacion_enviada', 0)->get();

        $this->info('Solicitudes pendientes: ' . $solicitudes->count());

        foreach ($solicitudes as $solicitud) {
            $this->info('Procesando solicitud ID: ' . $solicitud->id);

            $notificada = $this->notificarJefeDepartamento($solicitud);

            if ($notificada) {
                $solicitud->update([
                    'notificacion_enviada' => 1,
                ]);

                $this->info('Notificada correctamente.');
            } else {
                $this->warn('No se pudo notificar esta solicitud.');
            }
        }

        return Command::SUCCESS;
    }

    private function notificarJefeDepartamento($solicitud)
    {
        if (!$solicitud->departamento) {
            $this->warn('La solicitud no tiene departamento.');
            return false;
        }

        $departamento = Departamento::where('nombre', $solicitud->departamento)->first();

        if (!$departamento) {
            $this->warn('No se encontró el departamento: ' . $solicitud->departamento);
            return false;
        }

        if (!$departamento->jefe_empleado_id) {
            $this->warn('El departamento no tiene jefe_empleado_id.');
            return false;
        }

        $jefeEmpleado = Empleado::find($departamento->jefe_empleado_id);

        if (!$jefeEmpleado) {
            $this->warn('No se encontró el empleado jefe ID: ' . $departamento->jefe_empleado_id);
            return false;
        }

        if (!$jefeEmpleado->user) {
            $this->warn('El jefe empleado no tiene usuario relacionado.');
            return false;
        }

        $jefeEmpleado->user->notify(new NuevaSolicitud($solicitud, $solicitud->id));

        $this->info('Notificación enviada al usuario ID: ' . $jefeEmpleado->user->id);

        return true;
    }
}