<?php

// Namespace donde se encuentra el controlador
namespace App\Http\Controllers;

// Manejo de solicitudes HTTP
use Illuminate\Http\Request;

// Modelo de firmas
use App\Models\Firma;

// Modelo de empleados
use App\Models\Empleado;


// Definición del controlador de firmas
class FirmaController extends Controller
{
    /**
     * MÉTODO: index
     * ---------------------------------
     * Muestra la vista principal de firmas.
     * Carga empleados y firmas existentes.
     */
    public function index()
    {
        // Obtiene todos los empleados ordenados por nombre (A-Z)
        $empleados = Empleado::orderBy('nombre')->get();

        // Obtiene todas las firmas con su relación al empleado
        // (evita consultas adicionales gracias a eager loading)
        $firmas = Firma::with('empleado')->get();

        // Retorna la vista 'firmas.index' enviando ambas variables
        return view('firmas.index', compact('empleados', 'firmas'));
    }


    /**
     * MÉTODO: store
     * ---------------------------------
     * Guarda o actualiza una firma.
     */
    public function store(Request $request)
    {
        // Validación de datos del formulario
        $request->validate([

            // El empleado debe existir en la tabla empleados
            'empleado_id' => 'required|exists:empleados,id',

            // La foto debe ser una imagen y no pesar más de 2MB
            'foto' => 'required|image|max:2048'
        ]);

        // Convierte la imagen subida a formato binario (BLOB)
        // Esto permite guardarla directamente en la base de datos
        $binario = file_get_contents($request->file('foto')->getRealPath());

        // updateOrCreate:
        // - Si ya existe una firma con ese empleado_id → la actualiza
        // - Si no existe → crea una nueva
        Firma::updateOrCreate(

            // Condición de búsqueda
            ['empleado_id' => $request->empleado_id],

            [
                // Imagen almacenada en binario
                'imagen_path' => $binario,

                // Estado activo
                'activo' => 1
            ]
        );

        // Redirige hacia atrás con mensaje de éxito
        return back()->with('success', 'Firma actualizada correctamente.');
    }


    /**
     * MÉTODO: destroy
     * ---------------------------------
     * Elimina una firma por su ID.
     */
    public function destroy($id)
    {
       try {

          // Busca la firma por ID o lanza error si no existe
          $firma = \App\Models\Firma::findOrFail($id);

          // Elimina la firma de la base de datos
          $firma->delete();

          // Mensaje de éxito
          return back()->with('success', 'Firma eliminada correctamente.');

        } catch (\Illuminate\Database\QueryException $e) {

          // Código 23000 → error de integridad (foreign key)
          // Ocurre cuando la firma está siendo usada en otra tabla (ej: solicitudes)
          if ($e->getCode() == "23000") {

              return back()->with(
                  'error_integridad',

                  // Mensaje indicando que la firma está relacionada con registros existentes
                  'No se puede eliminar esta firma porque ya está asociada a solicitudes de permisos existentes.'
              );
          }

          // Cualquier otro error inesperado
          return back()->with('error', 'Ocurrió un error inesperado al intentar eliminar.');
       }
    }
}