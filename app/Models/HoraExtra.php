<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoraExtra extends Model
{
    use HasFactory;

    protected $table = 'horas_extras';

    protected $fillable = [
        'empleado_id',
        'nombre',
        'departamento_aprobador_id', 
        'lugar',
        'solicitado_a',              
        'departamento',
        'paso_actual',
        'horas_acumuladas',
        'observaciones_jefe',
        'codigo_formato',
        'horas_pagadas',
        'estado',
        'aprobado_por',
        'fecha_aprobacion'
    ];

    /* =========================
       RELACIONES
    ========================== */

    // Empleado que realizó las horas extra
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    // Usuario que aprobó (jefe, dirección, etc.)
    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    // Departamento que debe aprobar
    public function departamentoAprobador()
    {
        return $this->belongsTo(Departamento::class, 'departamento_aprobador_id');
    }

    // Detalle de actividades
    public function detalles()
    {
        return $this->hasMany(HoraExtraDetalle::class, 'hora_extra_id');
    }

   /**
     * Este método se activa automáticamente al crear una nueva solicitud.
     * Busca al empleado por nombre y le asigna su ID real.
     */
    protected static function booted()
    {
        static::creating(function ($horaExtra) {
            // Si el ID viene vacío pero el nombre trae texto (como pasa con el Form de 365)
            if (empty($horaExtra->empleado_id) && !empty($horaExtra->nombre)) {
                
                // Buscamos al empleado en la tabla 'empleados' por su nombre completo
                $empleado = \App\Models\Empleado::where(\DB::raw("CONCAT(nombre, ' ', apellido)"), 'LIKE', '%' . $horaExtra->nombre . '%')
                    ->orWhere('nombre', 'LIKE', '%' . $horaExtra->nombre . '%')
                    ->first();

                if ($empleado) {
                    // Si lo encuentra, le pone el ID (ej. 128) automáticamente antes de guardar
                    $horaExtra->empleado_id = $empleado->id;
                }
            }
        });
    }


    /* =========================
       SCOPES (MUY ÚTILES)
    ========================== */

    // Horas pendientes para un departamento
    public function scopePendientesPorDepartamento($query, $departamentoId)
    {
        return $query->where('estado', 'pendiente')
                     ->where('departamento_aprobador_id', $departamentoId);
    }
}
