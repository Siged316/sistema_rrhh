<?php

namespace App\Models; //Define el espacio de nombres del modelo dentro de la aplicación.

use Illuminate\Database\Eloquent\Model;  //Importación de la clase base Eloquent Model
use Illuminate\Database\Eloquent\Factories\HasFactory; // Trait de Laravel que permite usar factories para crear instancias del modelo (útil para seeders y pruebas)

class Empleado extends Model
{
    use HasFactory;

    protected $fillable = [
        'dni','user_id', 'nombre', 'apellido', 'email', 'codigo_empleado', 'contacto', 'fecha_nacimiento',
        'fecha_ingreso', 'fecha_baja', 'estado', 'cargo', 'departamento_id',  'dias_vacaciones_anuales',
    ];

    // Relación con departamento
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    // Obtener jefe del departamento
    public function jefe()
    {
        return $this->departamento ? $this->departamento->jefeEmpleado : null;
    }

    // Departamentos donde es jefe
    public function departamentosComoJefe()
    {
        return $this->hasMany(Departamento::class, 'jefe_empleado_id');
    }
    public function user()
    {
      // Un empleado pertenece a un usuario (o tiene un usuario)
      return $this->hasOne(User::class, 'empleado_id');
    }

    protected static function booted()
{
    // Se ejecuta cada vez que guardas un empleado
    static::saved(function ($empleado) {
        // Buscamos si el departamento asignado a este empleado lo tiene como jefe
        $departamentoAsignado = \App\Models\Departamento::where('id', $empleado->departamento_id)
            ->where('jefe_empleado_id', $empleado->id)
            ->first();

        if ($departamentoAsignado) {
            // Usamos query builder para evitar un bucle infinito de eventos
            \DB::table('empleados')
                ->where('id', $empleado->id)
                ->update(['cargo' => 'JEFE']);
        }
    });
}

/**
 * Este método intercepta la llamada a $empleado->cargo
 */
public function getCargoAttribute($value)
{
    // Verificamos si este empleado es jefe de algún departamento
    // Usamos la relación departamentosComoJefe que ya tienes definida
    if ($this->departamentosComoJefe()->exists()) {
        return 'JEFE';
    }

    // Si no es jefe, devuelve el valor real que tiene en la tabla (Analista, etc.)
    return $value;
}

public function firma()
{
    // Buscamos la firma vinculada a este empleado que esté marcada como activa
    return $this->hasOne(Firma::class, 'empleado_id')->where('activo', 1);
}

/**
     * Relación: Un empleado tiene muchas evaluaciones
     */
    public function evaluaciones()
    {
        // Usamos el ID de la tabla evaluaciones que apunta a este empleado
        return $this->hasMany(Evaluacion::class, 'empleado_id');
    }

    /**
 * Relación con las Horas Extras (Lo que el empleado gana)
 */
public function horasExtras()
{
    return $this->hasMany(HoraExtra::class, 'empleado_id');
}

/**
 * Relación con las Solicitudes de Tiempo (Lo que el empleado consume/pide)
 */
public function solicitudesTiempo()
{
    // Ajusta el nombre del modelo 'SolicitudVacacion' si usas otro para tiempo compensatorio
    return $this->hasMany(SolicitudVacacion::class, 'empleado_id')
                ->where('tipo_solicitud', 'tiempo_compensatorio');
}

/**
 * Esta es la relación que busca el controlador para el PDF
 * Fusiona la lógica para que el reporte vea todos los movimientos
 */
public function movimientosTiempo()
{
    // Si tienes una tabla unificada de 'movimientos_tiempo', úsala aquí.
    // Si no, podemos usar 'horasExtras' como base para el reporte de "ganadas"
    return $this->hasMany(HoraExtra::class, 'empleado_id');
}

/**
 * Esta es la relación que busca el controlador para el PDF
 * Fusiona la lógica para los documentos
 */
public function documentos()
    {
        // Esto le dice a Laravel que busque en documentos_laborales usando 'empleado_id'
        return $this->hasMany(DocumentoLaboral::class, 'empleado_id');
    }
    
}



