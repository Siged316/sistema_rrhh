<?php

namespace App\Http\Controllers; //Define la ubicación lógica del controlador dentro de la aplicación.

//Importación de clases (use)
use App\Models\Role;              // Modelo Eloquent que representa la tabla roles
use Illuminate\Http\Request;      // Clase para manejar solicitudes HTTP
use Illuminate\Database\QueryException; //Validación

//Clase RoleController
class RoleController extends Controller
{
    /**
     * Mostrar el listado de roles
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Obtener todos los registros de la tabla roles
        $roles = Role::all();

        // Retornar la vista y enviar la variable $roles
        return view('roles.index', compact('roles'));
    }

    /**
     * Mostrar el formulario para crear un nuevo rol
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Retorna la vista del formulario de creación
        return view('roles.create');
    }

    /**
     * Guardar un nuevo rol en la base de datos
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        //Normalización del nombre del rol
        $nombreNormalizado = trim(preg_replace('/\s+/', ' ', $request->nombre));

        // Se reemplaza el valor original del request por el valor normalizado
        $request->merge([
            'nombre' => $nombreNormalizado
        ]);

        //Validación de datos
        $request->validate([
            'nombre' => [
                'required',
                function ($attribute, $value, $fail) {
                    // Verifica si existe un rol con el mismo nombre normalizado
                    $exists = Role::whereRaw(
                        'LOWER(TRIM(nombre)) = ?',
                        [mb_strtolower($value)]
                    )->exists();

                    // Si existe, se detiene la validación
                    if ($exists) {
                        $fail('Este rol ya existe.');
                    }
                }
            ],
            'descripcion' => 'required',
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
        ]);

        //Creación del rol
        Role::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        //Redirección con mensaje de éxito
        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol creado correctamente');
    }

    /**
     * Mostrar el formulario para editar un rol existente
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // Buscar el rol por ID o lanzar error 404
        $rol = Role::findOrFail($id);

        // Retornar la vista de edición con los datos del rol
        return view('roles.edit', compact('rol'));
    }

    /**
     * Actualizar un rol existente
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Buscar el rol por ID
        $rol = Role::findOrFail($id);

        //Normalización del nombre
        $nombreNormalizado = trim(preg_replace('/\s+/', ' ', $request->nombre));
        $request->merge(['nombre' => $nombreNormalizado]);

        //Validación de datos
        $request->validate([
            'nombre' => 'required|unique:roles,nombre,' . $rol->id,
            'descripcion' => 'required',
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Este nombre ya está en uso.',
            'descripcion.required' => 'La descripción es obligatoria.',
        ]);

        //Actualización del rol
        $rol->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        // Redirección con mensaje de éxito
        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol actualizado correctamente');
    }

    /**
     * Eliminar un rol del sistema
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Buscar el rol por ID
        $rol = Role::findOrFail($id);

        try {
          // Eliminar el rol de la base de datos
          $rol->delete();

          // Redirección con mensaje de confirmación
           return redirect()
            ->route('roles.index')
            ->with('success', 'Rol eliminado correctamente');
        } catch (QueryException $e) {

          return redirect()
            ->route('roles.index')
            ->with('error', 'No se puede eliminar el rol porque está asignado a uno o más usuarios.');
       }
    }
}
