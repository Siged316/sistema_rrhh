<?php

/*
|--------------------------------------------------------------------------
| IMPORTACIÓN DE CONTROLADORES Y FACADES
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;                  // Facade para rutas
use Illuminate\Support\Facades\Auth;                   // Facade para autenticación

use App\Http\Controllers\EmpleadoController;           // Controlador de empleados
use App\Http\Controllers\RoleController;               // Controlador de roles
use App\Http\Controllers\PermisosSistemaController;    // Controlador de permisos del sistema
use App\Http\Controllers\SolicitudController;          // Controlador de solicitudes
use App\Http\Controllers\PoliticaVacacionesController; // Controlador de políticas de vacaciones
use App\Http\Controllers\UsuarioController;            // Controlador de usuarios
use App\Http\Controllers\LoginController;              // Controlador de login
use App\Http\Controllers\HoraExtraController;          // Controlador de horas extras
use App\Http\Controllers\DireccionHoraExtraController; // Controlador para aprobación dirección
use App\Http\Controllers\DepartmentController;         // Controlador de departamentos
use App\Http\Controllers\ConfigFirmaController;        // Controlador configuración de firmas
use App\Http\Controllers\FirmaController;              // Controlador de firmas
use App\Http\Controllers\PerfilController;             // Controlador del perfil
use App\Http\Controllers\ProyectoController;           // Controlador de proyectos
use App\Http\Controllers\EvaluacionController;         // Controlador de evaluaciones
use App\Http\Controllers\FormularioController;         // Controlador de formularios
use App\Http\Controllers\AsignacionController;         // Controlador de asignaciones
use App\Http\Controllers\ReporteController;            // Controlador de reportes


/*
|--------------------------------------------------------------------------
| 1. RUTAS PÚBLICAS (INVITADOS)
|--------------------------------------------------------------------------
*/

Route::middleware(['guest'])->group(function () {

    // Mostrar formulario de login
    Route::get('/login', [LoginController::class, 'showLogin'])
        ->name('login');

    // Procesar login
    Route::post('/login', [LoginController::class, 'login'])
        ->name('login.post');

    // Redirección raíz hacia login
    Route::get('/', function () {
        return redirect()->route('login');
    });
});


/*
|--------------------------------------------------------------------------
| 2. RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'force.password.change'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    // Pantalla principal del sistema
    Route::get('/dashboard', fn() => view('index'))
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | SEGURIDAD Y CONTRASEÑAS
    |--------------------------------------------------------------------------
    */

    // Vista para cambiar contraseña
    Route::get('/cambiar-password', fn() => view('auth.cambiar-password'))
        ->name('password.cambiar');

    // Actualizar contraseña
    Route::post('/actualizar-password', [UsuarioController::class, 'actualizarPassword'])
        ->name('password.actualizar');

    // Cerrar sesión
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');


    /*
    |--------------------------------------------------------------------------
    | GESTIÓN DE USUARIOS
    |--------------------------------------------------------------------------
    */

    // Listar usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index'])
        ->name('usuarios.index');

    // Guardar usuario
    Route::post('/usuarios', [UsuarioController::class, 'store'])
        ->name('usuarios.store');

    // Actualizar usuario
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])
        ->name('usuarios.update');

    // Eliminar usuario
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])
        ->name('usuarios.destroy');

    // Activar / desactivar usuario
    Route::put('/usuarios/{id}/estado', [UsuarioController::class, 'toggleEstado'])
        ->name('usuarios.estado');


    /*
    |--------------------------------------------------------------------------
    | ROLES Y PERMISOS
    |--------------------------------------------------------------------------
    */

    // CRUD de roles
    Route::resource('roles', RoleController::class)
        ->except(['show']);


    /*
    |--------------------------------------------------------------------------
    | EMPLEADOS
    |--------------------------------------------------------------------------
    */

    // CRUD de empleados
    Route::resource('empleado', EmpleadoController::class);

    // Cambiar estado del empleado
    Route::patch('/empleado/{id}/estado', [EmpleadoController::class, 'cambiarEstado'])
        ->name('empleado.estado');


    /*
    |--------------------------------------------------------------------------
    | SEGURIDAD DE MÓDULOS
    |--------------------------------------------------------------------------
    */

    // Mostrar permisos del sistema
    Route::get('/seguridad/permisos', [PermisosSistemaController::class, 'index'])
        ->name('permisos_sistema.index');

    // Actualizar permisos del sistema
    Route::post('/seguridad/permisos/update', [PermisosSistemaController::class, 'update'])
        ->name('permisos_sistema.update');


    /*
    |--------------------------------------------------------------------------
    | SOLICITUDES
    |--------------------------------------------------------------------------
    */

    Route::prefix('solicitudes')->group(function () {

        // Listar solicitudes
        Route::get('/', [SolicitudController::class, 'index'])
            ->name('solicitudes.index');

        // Mostrar detalle
        Route::get('/{id}', [SolicitudController::class, 'show'])
            ->name('solicitudes.show');

        // Procesar solicitud
        Route::post('/{id}/procesar', [SolicitudController::class, 'procesar'])
            ->name('solicitudes.procesar');

        // Actualizar solicitud
        Route::put('/{id}', [SolicitudController::class, 'update'])
            ->name('solicitudes.update');

        // Rectificar tipo
        Route::post('/{id}/rectificar', [SolicitudController::class, 'rectificarTipo'])
            ->name('solicitudes.rectificar');

        // Accionar solicitud
        Route::post('/{id}/accionar', [SolicitudController::class, 'accionar'])
            ->name('solicitudes.accionar');

        // Actualizar detalles
        Route::post('/{id}/update-detalles', [SolicitudController::class, 'updateDetalles']);

        // Cálculo permanente
        Route::get('/calculo-permanente/{empleadoId}', [SolicitudController::class, 'calculoPermanente']);
    });


    /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN DE FIRMAS
    |--------------------------------------------------------------------------
    */

    Route::prefix('configuracion-firmas')->group(function () {

        // Listado de configuraciones
        Route::get('/', [ConfigFirmaController::class, 'index'])
            ->name('configuracion.firmas');

        // Guardar configuración
        Route::post('/guardar', [ConfigFirmaController::class, 'store'])
            ->name('configuracion.store');

        // Activar / desactivar configuración
        Route::post('/toggle/{id}', [ConfigFirmaController::class, 'toggle'])
            ->name('configuracion.toggle');

        // Eliminar configuración
        Route::delete('/eliminar/{id}', [ConfigFirmaController::class, 'destroy'])
            ->name('configuracion.destroy');

        // Actualizar configuración
        Route::put('/actualizar/{id}', [ConfigFirmaController::class, 'update'])
            ->name('configuracion.update');
    });


    /*
    |--------------------------------------------------------------------------
    | HORAS EXTRAS
    |--------------------------------------------------------------------------
    */

    Route::prefix('horas-extras')->group(function () {

        // Guardar horas extras
        Route::post('/store', [HoraExtraController::class, 'store'])
            ->name('horas_extras.store');

        // Ver pendientes
        Route::get('/pendientes', [HoraExtraController::class, 'pendientes'])
            ->name('horas_extras.pendientes');

        // Gestión
        Route::get('/gestion', [HoraExtraController::class, 'gestion'])
            ->name('horas_extras.gestion');

        // Validar
        Route::patch('/{id}/validar', [HoraExtraController::class, 'validar'])
            ->name('horas_extras.validar');

        // Obtener firma jefe
        Route::get('/obtener-firma-jefe', [HoraExtraController::class, 'getFirmaJefe'])
            ->name('firma.get');
    });


    /*
    |--------------------------------------------------------------------------
    | APROBACIÓN DIRECCIÓN HORAS EXTRAS
    |--------------------------------------------------------------------------
    */

    // Vista dirección
    Route::get('/direccion/horas-extras', [DireccionHoraExtraController::class, 'index'])
        ->name('direccion.horas_extras');

    // Aprobar / rechazar
    Route::post('/direccion/horas-extras/{id}', [DireccionHoraExtraController::class, 'decidir'])
        ->name('direccion.horas_extras.decidir');


    /*
    |--------------------------------------------------------------------------
    | DEPARTAMENTOS
    |--------------------------------------------------------------------------
    */

    // CRUD departamentos
    Route::resource('departamentos', DepartmentController::class)
        ->except(['show', 'create', 'edit']);

    // Obtener empleados del departamento
    Route::get('/departamentos/{id}/empleados', [ProyectoController::class, 'getEmpleados']);


    /*
    |--------------------------------------------------------------------------
    | POLÍTICAS DE VACACIONES
    |--------------------------------------------------------------------------
    */

    // Listado
    Route::get('/politicas-vacaciones', [PoliticaVacacionesController::class, 'index'])
        ->name('politicas.index');

    // Guardar
    Route::post('/politicas-vacaciones', [PoliticaVacacionesController::class, 'store'])
        ->name('politicas.store');

    // Actualizar
    Route::put('/politicas-vacaciones/{id}', [PoliticaVacacionesController::class, 'update'])
        ->name('politicas.update');

    // Eliminar
    Route::delete('/politicas-vacaciones/{id}', [PoliticaVacacionesController::class, 'destroy'])
        ->name('politicas.destroy');


    /*
    |--------------------------------------------------------------------------
    | FIRMAS
    |--------------------------------------------------------------------------
    */

    // Listado
    Route::get('/firmas', [FirmaController::class, 'index'])
        ->name('firmas.index');

    // Guardar
    Route::post('/firmas', [FirmaController::class, 'store'])
        ->name('firmas.store');

    // Eliminar
    Route::delete('/firmas/{id}', [FirmaController::class, 'destroy'])
        ->name('firmas.destroy');


    /*
    |--------------------------------------------------------------------------
    | PERFIL
    |--------------------------------------------------------------------------
    */

    // Vista perfil
    Route::get('/perfil', [PerfilController::class, 'index'])
        ->name('perfil.index');

    // Actualizar datos
    Route::put('/perfil/update-datos', [PerfilController::class, 'updateDatos'])
        ->name('perfil.update.datos');

    // Actualizar contraseña
    Route::put('/perfil/password', [PerfilController::class, 'updatePassword'])
        ->name('perfil.update.password');


    /*
    |--------------------------------------------------------------------------
    | PROYECTOS
    |--------------------------------------------------------------------------
    */

    // Actualizar progreso
  // 1. RUTAS ESTÁTICAS PRIMERO
Route::get('/proyectos', [ProyectoController::class, 'index'])->name('proyectos.index');
Route::post('/proyectos', [ProyectoController::class, 'store'])->name('proyectos.store');
Route::post('/tareas/completar', [ProyectoController::class, 'completarTarea']);
Route::post('/tareas/enviar-revision', [ProyectoController::class, 'enviarRevision'])->name('tareas.revision');
Route::post('/tareas/validar-jefe', [ProyectoController::class, 'validarJefe'])->name('tareas.validar');
Route::post('/tareas/solicitar-correccion', [ProyectoController::class, 'solicitarCorreccion']);

// 2. RUTAS CON PARÁMETROS {id} DESPUÉS
Route::get('/proyectos/{id}/get-tareas', [ProyectoController::class, 'getTareas']);
Route::get('/proyectos/{id}/edit', [ProyectoController::class, 'edit'])->name('proyectos.edit');
Route::patch('/proyectos/{id}/progreso', [ProyectoController::class, 'updateProgress'])->name('proyectos.progreso');
Route::post('/proyectos/{id}/validar', [ProyectoController::class, 'validar'])->name('proyectos.validar');


// LA RUTA PUT DEBE SER LA ÚLTIMA DE ESTE BLOQUE
Route::put('/proyectos/{id}', [ProyectoController::class, 'update'])->name('proyectos.update');


    /*
    |--------------------------------------------------------------------------
    | EVALUACIONES
    |--------------------------------------------------------------------------
    */

    // Listado
    Route::get('/evaluaciones', [EvaluacionController::class, 'index'])
        ->name('evaluaciones.index');

    // Guardar evaluación
    Route::post('/evaluaciones/guardar', [EvaluacionController::class, 'store'])
        ->name('evaluaciones.store');

    // Comparar evaluación
    Route::get('/evaluaciones/comparar/{empleado_id}', [EvaluacionController::class, 'comparar'])
        ->name('evaluaciones.comparar');

    // Llenar formulario
    Route::get('/evaluacion/llenar/{id}', [EvaluacionController::class, 'llenarFormulario'])
        ->name('evaluaciones.llenar');

    // Guardar respuestas
    Route::post('/evaluacion/guardar', [EvaluacionController::class, 'guardar'])
        ->name('evaluacion.guardar');

    // Mis evaluaciones
    Route::get('/mis-evaluaciones', [EvaluacionController::class, 'index'])
        ->name('evaluaciones.pendientes');
Route::post('/evaluaciones/guardar', [EvaluacionController::class, 'guardar'])
    ->name('evaluaciones.guardar');

    /*
    |--------------------------------------------------------------------------
    | FORMULARIOS
    |--------------------------------------------------------------------------
    */

    Route::prefix('formulario')->group(function () {

        // Listado
        Route::get('/', [FormularioController::class, 'index'])
            ->name('formulario.index');

        // Guardar
        Route::post('/guardar', [FormularioController::class, 'store'])
            ->name('formulario.store');

        // Mostrar
        Route::get('/{id}', [FormularioController::class, 'show'])
            ->name('formulario.show');

        // Actualizar
        Route::put('/{id}/update', [FormularioController::class, 'update'])
            ->name('formulario.update');

        // Agregar pregunta
        Route::post('/{id}/agregar-pregunta', [FormularioController::class, 'agregarPregunta'])
            ->name('formulario.agregarPregunta');

        // Eliminar pregunta
        Route::delete('/pregunta/{id}/eliminar', [FormularioController::class, 'eliminarPregunta'])
            ->name('formulario.eliminarPregunta');

        // Actualizar pregunta
        Route::put('/pregunta/{id}/actualizar', [FormularioController::class, 'actualizarPregunta'])
            ->name('formulario.actualizarPregunta');

        // Guardar asignación
        Route::post('/asignar', [FormularioController::class, 'asignarStore'])
        ->name('asignaciones.store');
    });

});


/*
|--------------------------------------------------------------------------
| INFORMES
|--------------------------------------------------------------------------
*/

Route::prefix('informes')->middleware(['auth'])->group(function () {

    // Pantalla principal
    Route::get('/', [ReporteController::class, 'index'])
        ->name('informes.index');

    // Informe por departamento
    Route::get('/departamento', [ReporteController::class, 'departamento'])
        ->name('informes.departamento');

    // PDF general
    Route::get('/pdf', [ReporteController::class, 'generarPdf'])
        ->name('informes.pdf');

    // Excel general
    Route::get('/excel', [ReporteController::class, 'generarExcel'])
        ->name('informes.excel');

    // Validación general
    Route::get('/validar-datos', [ReporteController::class, 'validarDatos'])->name('informes.validar');

    // Informe individual
    Route::get('/individual', [ReporteController::class, 'individual'])
        ->name('informes.individual');

    // PDF individual
    Route::get('/individual/pdf', [ReporteController::class, 'generarIndividualPdf'])
        ->name('informes.individual.pdf');

    // Excel individual
    Route::get('/individual/excel', [ReporteController::class, 'generarIndividualExcel'])
        ->name('informes.individual.excel');

    // Informe permisos
    Route::get('/permisos', [ReporteController::class, 'permisos'])
        ->name('informes.permisos');

    // Informe compensatorio
    Route::get('/compensatorio', [ReporteController::class, 'compensatorio'])->name('informes.compensatorio');
    Route::get('/compensatorio/pdf', [ReporteController::class, 'pdfCompensatorio'])->name('informes.compensatorio.pdf');
    Route::get('/compensatorio/excel', [ReporteController::class, 'excelCompensatorio'])->name('informes.compensatorio.excel');
    Route::get('/validar-compensatorio', [ReporteController::class, 'validarCompensatorio'])->name('validar.compensatorio');
    
   
   // Rutas para Reportes de Permisos y Vacaciones
   Route::get('/permisos', [ReporteController::class, 'permisos'])->name('informes.permisos');
   Route::get('/validar-permisos', [ReporteController::class, 'validarPermisos'])->name('informes.validar.permisos');
   Route::get('/permisos/pdf', [ReporteController::class, 'generarPermisosPdf'])->name('informes.permisos.pdf');
   Route::get('/informes/permisos/excel', [ReporteController::class, 'exportarPermisosExcel'])->name('informes.permisos.excel');

   
});

// Grupo de rutas para Gráficas Comparativas
Route::prefix('informes/graficas')->group(function () {

    Route::get('/', [ReporteController::class, 'indexGraficas'])
        ->name('informes.graficas.index');

    // DEPTO
    Route::get('/desempeno-depto', [ReporteController::class, 'graficaDepto'])
        ->name('graficas.depto');

    Route::get('/datos-depto', [ReporteController::class, 'dataGraficaDepto'])
        ->name('graficas.data.depto');

    // INDIVIDUAL
    Route::get('/grafica-individual', [ReporteController::class, 'graficaIndividual'])
        ->name('graficas.individual');

    Route::get('/datos-individual', [ReporteController::class, 'dataGraficaIndividual'])
        ->name('graficas.data.individual');

    Route::get('/get-empleados/{depto_id}', [ReporteController::class, 'getEmpleadosPorDepto'])
        ->name('get.empleados');

    // PERMISOS
    Route::get('/grafica-permisos', [ReporteController::class, 'graficaPermisos'])
        ->name('graficas.permisos');

    Route::get('/data-permisos', [ReporteController::class, 'dataGraficaPermisos'])
        ->name('graficas.data.permisos');

    // COMPENSATORIO
    Route::get('/grafica-compensatorio', [ReporteController::class, 'graficaCompensatorio'])
        ->name('graficas.compensatorio');

    Route::get('/data-compensatorio', [ReporteController::class, 'dataGraficaCompensatorio'])
        ->name('graficas.data.compensatorio');
});



Route::get('/test-notificacion-horas', function() {
    // 1. Tomamos una solicitud real
    $horaExtra = \App\Models\HoraExtra::latest()->first();
    if (!$horaExtra) return "No hay horas extras.";

    // 2. Buscamos al jefe del departamento de esa hora extra
    // (Ajusta 'departamento_id' según la columna real en tu tabla)
    $depto = \App\Models\Departamento::where('nombre', $horaExtra->departamento)->first();
    
    if ($depto && $depto->jefe_empleado_id) {
        $jefe = \App\Models\User::whereHas('empleado', function($q) use ($depto) {
            $q->where('id', $depto->jefe_empleado_id);
        })->first();

        if ($jefe) {
            $jefe->notify(new \App\Notifications\NuevaHoraExtra($horaExtra));
            return "Notificación enviada al Jefe del Depto: " . $jefe->email;
        }
    }
    
    return "No se pudo encontrar un jefe para ese departamento.";
});

Route::post('/notificaciones/marcar-leidas', function () {
    auth()->user()->unreadNotifications->markAsRead();
    return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
})->name('notificaciones.marcarLeidas')->middleware('auth');
/*
|--------------------------------------------------------------------------
| REDIRECCIÓN FINAL DEL SISTEMA
|--------------------------------------------------------------------------
*/

// Si el usuario está autenticado entra al dashboard,
// de lo contrario se redirige al login
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});