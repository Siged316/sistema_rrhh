<?php

namespace App\Http\Controllers; //Define que esta clase pertenece al espacio de nombres de controladores.

use Illuminate\Http\Request;    //Importación de la clase Request.
use App\Models\Role;            // Importación del modelo Role.
use App\Models\RolModulo;       //Importación del modelo RolModulo.
use App\Models\Modulo;          //Importación del modelo Modulo.




class PermisosSistemaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Método index
    |--------------------------------------------------------------------------
    | Muestra la pantalla de administración de permisos del sistema.
    | Permite seleccionar un rol y ver los módulos habilitados.
    */
    public function index(Request $request)
    {
     $roles = \App\Models\Role::all();
    
     // Si no hay role_id en la URL, $roleId será null
     $roleId = $request->query('role_id'); 

     $permisos = [];
        if ($roleId) {
         $permisos = RolModulo::where('role_id', $roleId)
                    ->pluck('visible', 'modulo')
                    ->toArray();
       }

      return view('seguridad.permisos', compact('roles', 'roleId', 'permisos'));
    }

    /*
    |--------------------------------------------------------------------------
    | Método update
    |--------------------------------------------------------------------------
    | Guarda o actualiza los permisos de acceso a módulos
    | para un rol específico.
    */
    public function update(Request $request)
    {
        // 1. Validación inicial
        $request->validate([
            'role_id' => 'nullable', // Permitimos que llegue vacío para controlarlo nosotros
            'modulos' => 'array'
        ]);

        $roleId = $request->role_id;

        // CONTROL CRÍTICO: Si presionan actualizar sin haber seleccionado ningún rol
        if (empty($roleId)) {
            return redirect()->route('permisos_sistema.index')
                ->with('info', 'Por favor, seleccione un rol antes de intentar actualizar los permisos.');
        }

        $modulosSistema = [
            'seguridad',
            'administración',
            'permisos_laborales',
            'informes',
            'proyectos'
        ];

        // 2. Obtener estado actual en la Base de Datos
        $permisosActuales = RolModulo::where('role_id', $roleId)
            ->pluck('visible', 'modulo')
            ->toArray();

        // 3. Detectar si realmente se cambió algún switch
        $hayCambios = false;
        foreach ($modulosSistema as $modulo) {
            $estadoFormulario = isset($request->modulos[$modulo]) ? 1 : 0;
            $estadoActual = $permisosActuales[$modulo] ?? 0;

            if ($estadoFormulario !== $estadoActual) {
                $hayCambios = true;
                break; // Con un solo cambio, ya es válido procesar
            }
        }

        // SI NO HUBO CAMBIOS: Redirección limpia al rol actual
        if (!$hayCambios) {
            return redirect()->route('permisos_sistema.index', ['role_id' => $roleId])
                ->with('info', 'No se realizaron cambios en los permisos.');
        }

        // 4. PROCESAR ACTUALIZACIÓN (Solo si hay cambios)
        foreach ($modulosSistema as $modulo) {
            RolModulo::updateOrCreate(
                [
                    'role_id' => $roleId,
                    'modulo'  => $modulo
                ],
                [
                    'visible' => isset($request->modulos[$modulo]) ? 1 : 0
                ]
            );
        }

        // REDIRECCIÓN EXITOSA: Usamos un nombre único 'success_permisos' para no chocar con el perfil
        return redirect()->route('permisos_sistema.index', ['role_id' => $roleId])
            ->with('success_permisos', 'Permisos actualizados correctamente.');
    }
}
