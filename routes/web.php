<?php

/*
 Importación de controladores y facades necesarios
*/
use App\Http\Controllers\EmpleadoController;           // Controlador de empleados
use App\Http\Controllers\RoleController;               // Controlador de roles
use App\Http\Controllers\PermisosSistemaController;    // Controlador de permisos del sistema
use App\Http\Controllers\SolicitudController;            // Controlador de permisos laborales
use App\Http\Controllers\PoliticaVacacionesController; // Controlador de políticas de vacaciones
use App\Http\Controllers\UsuarioController;            // Controlador de usuarios
use App\Http\Controllers\LoginController;              //controla el inicio de sesión
use Illuminate\Support\Facades\Route;                  // Facade para definir rutas
use Illuminate\Support\Facades\Auth;                   // Facade para autenticación
use App\Http\Controllers\TiempoCompensatorioController; // Controlador de tiempo compensatorio
use App\Http\Controllers\HoraExtraController;           // Controlador de horas extras
use App\Http\Controllers\DireccionHoraExtraController;  // Controlador de horas extras para aprobar
use App\Http\Controllers\DepartmentController;         // Controlador para departamento
use App\Http\Controllers\FirmaController;                 // Controlador para firmas
use App\Http\Controllers\ConfigFirmaController;         // Controlador para firmas



/*
|--------------------------------------------------------------------------
| 1. RUTAS PÚBLICAS (GUEST)
|--------------------------------------------------------------------------
*/
Route::middleware(['guest'])->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    
    // Raíz redirige a Login
    Route::get('/', function () {
        return redirect()->route('login');
    });
});

/*
|--------------------------------------------------------------------------
| 2. RUTAS PROTEGIDAS (AUTH)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'force.password.change'])->group(function () {

    // --- DASHBOARD / INICIO ---
   Route::get('/dashboard', fn() => view('index'))->name('dashboard');

    // --- SEGURIDAD Y PERFIL ---
    Route::get('/cambiar-password', fn() => view('auth.cambiar-password'))->name('password.cambiar');
    Route::post('/actualizar-password', [UsuarioController::class, 'actualizarPassword'])->name('password.actualizar');
    
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    // --- MÓDULO: GESTIÓN DE USUARIOS ---
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    Route::put('/usuarios/{id}/estado', [UsuarioController::class, 'toggleEstado'])->name('usuarios.estado');

    // --- MÓDULO: ROLES Y PERMISOS ---
    Route::resource('roles', RoleController::class)->except(['show']);

    // --- MÓDULO: EXPEDIENTE DE EMPLEADOS ---
    Route::resource('empleado', EmpleadoController::class);
    Route::patch('/empleado/{id}/estado', [EmpleadoController::class, 'cambiarEstado'])->name('empleado.estado');  
    
    // --- MÓDULO: SEGURIDAD DE MÓDULOS ---
    Route::get('/seguridad/permisos', [PermisosSistemaController::class, 'index'])->name('permisos_sistema.index');
    Route::post('/seguridad/permisos', [PermisosSistemaController::class, 'update'])->name('permisos_sistema.update');
    
    // --- MÓDULO: PERMISOS LABORALES ---
    // --- MÓDULO  DE SOLICITUDES ---
    Route::prefix('solicitudes')->group(function () {
       // Listado
       Route::get('/', [SolicitudController::class, 'index'])->name('solicitudes.index');
    
       // Ver detalle (Modal/Impresión)
       Route::get('/{id}', [SolicitudController::class, 'show'])->name('solicitudes.show');
    
       // El motor de aprobación (Este es el que usa el botón de firma)
       Route::post('/{id}/procesar', [SolicitudController::class, 'procesar'])->name('solicitudes.procesar');

       // Rutas de edición y rectificación
       Route::put('/{id}', [SolicitudController::class, 'update'])->name('solicitudes.update');
       Route::post('/{id}/rectificar', [SolicitudController::class, 'rectificarTipo'])->name('solicitudes.rectificar');
       Route::post('/{id}/accionar', [SolicitudController::class, 'accionar'])->name('solicitudes.accionar');
       Route::post('/{id}/update-detalles', [SolicitudController::class, 'updateDetalles']);
  
      // Procesar calculo de contrato permanente
      Route::get('/calculo-permanente/{empleadoId}', [App\Http\Controllers\SolicitudController::class, 'calculoPermanente']);

    });


    // Agrupa las rutas de configuración para mantener el orden
    Route::prefix('configuracion-firmas')->group(function () {
    
      // Esta es la ruta que te falta (la que muestra la lista de firmas)
      Route::get('/', [ConfigFirmaController::class, 'index'])->name('configuracion.firmas');
    
      // Ruta para procesar el formulario del modal (Guardar nueva)
      Route::post('/guardar', [ConfigFirmaController::class, 'store'])->name('configuracion.store');
    
      // Ruta para activar/desactivar (el switch)
      Route::post('/toggle/{id}', [ConfigFirmaController::class, 'toggle'])->name('configuracion.toggle');

      Route::delete('/eliminar/{id}', [ConfigFirmaController::class, 'destroy'])->name('configuracion.destroy');
      Route::put('/actualizar/{id}', [ConfigFirmaController::class, 'update'])->name('configuracion.update');
    });

    // --- RUTAS PARA PERMISOS (USO DE TIEMPO) ---
    Route::prefix('tiempo-compensatorio')->group(function () {
    Route::get('/', [TiempoCompensatorioController::class, 'index'])->name('tiempo_compensatorio.index');
    Route::get('/crear', [TiempoCompensatorioController::class, 'create'])->name('tiempo_compensatorio.create');
    Route::post('/store', [TiempoCompensatorioController::class, 'store'])->name('tiempo_compensatorio.store');
    Route::get('/{id}', [TiempoCompensatorioController::class, 'show'])->name('tiempo_compensatorio.show');
    Route::delete('/{id}', [TiempoCompensatorioController::class, 'destroy'])->name('tiempo_compensatorio.destroy');
    
    });

    // --- RUTAS PARA HORAS EXTRAS (REGISTRO FT-GTH-002) ---
    Route::prefix('horas-extras')->group(function () {
      // Para guardar desde el modal
      Route::post('/store', [HoraExtraController::class, 'store'])->name('horas_extras.store');
    
      // Para ver la lista de pendientes (la que vería el jefe)
      Route::get('/pendientes', [HoraExtraController::class, 'pendientes'])->name('horas_extras.pendientes');
    
      Route::get('/gestion', [HoraExtraController::class, 'gestion'])->name('horas_extras.gestion');

      // Para aprobar o rechazar (usamos validar para que coincida con el controlador anterior)
      Route::patch('/{id}/validar', [HoraExtraController::class, 'validar'])->name('horas_extras.validar');
    });

    // --- RUTAS PARA APROBAR HORAS EXTRAS (REGISTRO FT-GTH-002) ---
    Route::get('/direccion/horas-extras', [DireccionHoraExtraController::class, 'index'])
        ->name('direccion.horas_extras');

    Route::post('/direccion/horas-extras/{id}', [DireccionHoraExtraController::class, 'decidir'])
        ->name('direccion.horas_extras.decidir');

    Route::prefix('horas-extras')->group(function () {
      // Esta será tu nueva pantalla principal
      Route::get('/gestion', [HoraExtraController::class, 'gestion'])->name('horas_extras.gestion');
    
      // Ruta para que el jefe procese (Aprobar/Rechazar)
      Route::patch('/{id}/validar', [HoraExtraController::class, 'validar'])->name('horas_extras.validar');
   });

    // --- MÓDULO: DEPARTAMENTO ---
    Route::resource('departamentos', DepartmentController::class)
    ->except(['show', 'create', 'edit']);

    // --- MÓDULO: POLÍTICAS DE VACACIONES ---
    Route::get('/politicas-vacaciones', [PoliticaVacacionesController::class, 'index'])->name('politicas.index');
    Route::post('/politicas-vacaciones', [PoliticaVacacionesController::class, 'store'])->name('politicas.store');
    Route::put('/politicas-vacaciones/{id}', [PoliticaVacacionesController::class, 'update'])->name('politicas.update');
    Route::delete('/politicas-vacaciones/{id}', [PoliticaVacacionesController::class, 'destroy'])->name('politicas.destroy');

     // --- MÓDULO: Firmas ---
    Route::get('/firmas', [FirmaController::class, 'index'])->name('firmas.index');
    Route::post('/firmas', [FirmaController::class, 'store'])->name('firmas.store');
    Route::delete('/firmas/{id}', [FirmaController::class, 'destroy'])->name('firmas.destroy');
    
     // --- MÓDULO: Perfil ---
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil.index');
    Route::put('/perfil/update-datos', [PerfilController::class, 'updateDatos'])->name('perfil.update.datos');
    Route::put('/perfil/password', [PerfilController::class, 'updatePassword'])->name('perfil.update.password');

    
    // Redirección si ya está logueado
    Route::get('/', function () {
       return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
    });
});