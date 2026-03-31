<?php
// Ejecuta: php artisan make:controller PerfilController

namespace App\Http\Controllers;

use Illuminate\Http\Request;          // Para manejar solicitudes HTTP
use Illuminate\Support\Facades\Hash; // Para encriptar contraseñas
use Illuminate\Support\Facades\Auth; // - Obtener el usuario autenticado (Auth::user)
use Illuminate\Validation\Rules\Password; //Validar password
use App\Models\User;                       // Modelo de usuarios

class PerfilController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('empleado.departamento', 'rol');
        return view('perfil.index', compact('user'));
    }

    public function updateDatos(Request $request)
    {
      // 1. Validación: Asegúrate de que los nombres coincidan con los 'name' de tu HTML
      $request->validate([
         'primer_nombre'   => 'required|string|max:100', // Antes tenías 'pnombre'
         'primer_apellido' => 'required|string|max:100',
         'email'           => 'required|email|max:100|unique:users,email,' . auth()->id(),
         'telefono'        => 'nullable|string|max:20',
       ]);

       $user = auth()->user();

       try {
         // 2. Actualizar tabla 'users'
         $user->update([
             'email' => $request->email
           ]);

         // 3. Actualizar tabla 'empleados'
         if ($user->empleado) {
             $user->empleado->update([
                 'nombre'   => $request->primer_nombre,   // Usar el nombre validado arriba
                 'apellido' => $request->primer_apellido,
                 'email'    => $request->email,
                 'contacto' => $request->telefono,
                ]);
            }

           return back()->with('success_datos', '¡Perfil actualizado con éxito!');

        } catch (\Exception $e) {
          // Si falla, descomenta la línea de abajo para ver el error real:
          // dd($e->getMessage()); 
         return back()->with('error', 'Error al procesar la solicitud.');
        }
    }

    public function updatePassword(Request $request)
    {
        // 1. Validación con reglas de complejidad y mensajes en español
        $request->validate([
            'current_password' => 'required',
            'new_password'     => [
                'required',
                'min:8',
                'different:current_password',
                Password::min(8)
                    ->letters()   // Al menos una letra
                    ->mixedCase() // Mayúsculas y minúsculas
                    ->numbers()   // Al menos un número
                    ->symbols(),  // Al menos un símbolo (!@#$%...)
            ],
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'new_password.required'     => 'La nueva contraseña es obligatoria.',
            'new_password.min'          => 'La contraseña debe tener al menos 8 caracteres.',
            'new_password.different'    => 'La nueva contraseña no puede ser igual a la anterior.',
            'new_password'              => 'La clave debe incluir mayúsculas, minúsculas, números y símbolos.',
        ]);

        // 2. Buscar al usuario directamente por ID para asegurar el guardado
        $user = User::findOrFail(auth()->id());

        // 3. Verificar si la contraseña actual coincide
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        try {
            // 4. Actualizar campos y FORZAR el guardado
            $user->password = Hash::make($request->new_password);
            $user->debe_cambiar_password = 0; 
            
            if ($user->save()) {
                return back()->with('success_password', '¡Contraseña actualizada correctamente!');
            } else {
                return back()->with('error', 'No se pudo guardar en la base de datos.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error inesperado: ' . $e->getMessage());
        }
    }
}
