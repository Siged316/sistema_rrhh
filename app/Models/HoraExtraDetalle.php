<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoraExtraDetalle extends Model
{
    use HasFactory;

    protected $table = 'horas_extras_detalle';

    protected $fillable = [
        'hora_extra_id',
        'fecha1', 'hora_inicio1', 'periodo_inicio1', 'hora_fin1', 'periodo_fin1', 'actividad1',
        'fecha2', 'hora_inicio2', 'periodo_inicio2', 'hora_fin2', 'periodo_fin2', 'actividad2',
        'fecha3', 'hora_inicio3', 'periodo_inicio3', 'hora_fin3', 'periodo_fin3', 'actividad3',
        'fecha4', 'hora_inicio4', 'periodo_inicio4', 'hora_fin4', 'periodo_fin4', 'actividad4',
        'fecha5', 'hora_inicio5', 'periodo_inicio5', 'hora_fin5', 'periodo_fin5', 'actividad5',
    ];

    /**
     * Relación inversa con la cabecera de la hora extra.
     */
    public function horaExtra()
    {
        return $this->belongsTo(HoraExtra::class, 'hora_extra_id');
    }
}
