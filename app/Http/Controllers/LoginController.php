<?php

// Namespace del controlador
// Indica que este controlador pertenece a App\Http\Controllers
namespace App\Http\Controllers;

// Importamos Request para manejar datos enviados por formularios
use Illuminate\Http\Request;

// Importamos Auth para manejar autenticación (login, logout, usuario actual)
use Illuminate\Support\Facades\Auth;

use App\Models\User;

use Illuminate\Support\Facades\Hash;


class LoginController extends Controller
{
    // Muestra la vista del formulario de inicio de sesión
    public function showLogin() {
        return view('auth.login');
    }

    // Procesa el intento de inicio de sesión
    public function login(Request $request) {
    $request->validate([
        'usuario'  => 'required|string',
        'password' => 'required|string',
    ]);

    $login = $request->input('usuario');
    $password = $request->input('password');

   $user = User::where('usuario', $login)
            ->orWhere('email', $login)
            ->first();

            // 1. Validar si el usuario existe
    if (!$user) {
        return back()->withErrors(['usuario' => 'El usuario o correo electrónico no existe.'])->onlyInput('usuario');
    }



    // Verificamos credenciales ANTES de iniciar sesión
    if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {

        // 1. Verificamos estado del usuario
        if ($user->estado !== 'activo') {
            return back()->withErrors(['usuario' => 'Tu cuenta está inactiva.']);
        }

       
       // 2. Validar relación y estado del EMPLEADO (Solo si NO es administrador)
       if (!$user->isAdmin()) {
           if (!$user->empleado || $user->empleado->estado !== 'activo' || $user->empleado->fecha_baja !== null) {
              return back()->withErrors(['usuario' => 'Acceso deshabilitado.']);
            }
        }

        // SI PASA LAS VALIDACIONES, RECIÉN AQUÍ INICIAMOS SESIÓN
        Auth::login($user);
        $request->session()->regenerate();

        // 3. Forzar cambio de contraseña
        if ($user->debe_cambiar_password == 1) {
            return redirect()->route('password.cambiar')
                ->with('info', 'Debes actualizar tu contraseña temporal.');
        }

        
        return redirect()->intended('/dashboard');
    }


    return back()->withErrors([
        'usuario' => 'El usuario o la contraseña son incorrectos.'
    ])->onlyInput('usuario');
    }


    // Cierra la sesión del usuario
    public function logout(Request $request) {

        // Cerramos la sesión
        Auth::logout();

        // Invalidamos la sesión actual
        $request->session()->invalidate();

        // Regeneramos el token CSRF por seguridad
        $request->session()->regenerateToken();

        // Redirigimos al formulario de login
        return redirect('/login');
    }
}
