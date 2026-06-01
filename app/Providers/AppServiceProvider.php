<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // <--- Importante
use App\Models\Solicitud;           // <--- Importante
use Illuminate\Pagination\LengthAwarePaginator; // <--- Importante

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap cualquier servicio de aplicación.
     */
 public function boot(): void
{
    View::composer('horas_extras.gestion', function ($view) {
        $user = auth()->user();
        if (!$user) return;

        $empleadoLogueado = $user->empleado;
        $esAdmin = $user->hasRole('Administrador');
        $esGTH = $user->hasRole('GTH') || $user->hasRole('Gestión de Talento Humano');
        $esAdminOGTH = $esAdmin || $esGTH;
        
        $deptoDirigido = \App\Models\Departamento::where('jefe_empleado_id', $empleadoLogueado->id)->first();
        $esJefe = !is_null($deptoDirigido);

        // Inyectamos permisos básicos que la vista necesita siempre
        $view->with(['esAdmin' => $esAdmin, 'esGTH' => $esGTH, 'esJefe' => $esJefe]);

        // Carga de departamentos (solo si es admin o jefe)
        $departamentos = \App\Models\Departamento::with(['empleados' => fn($q) => $q->orderBy('nombre')]);
        if ($esAdminOGTH) $view->with('departamentos', $departamentos->orderBy('nombre')->get());
        elseif ($esJefe) $view->with('departamentos', $departamentos->where('id', $deptoDirigido->id)->get());
    });
}
}