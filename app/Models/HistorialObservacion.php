<?php

namespace App\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialObservacion extends Model
{
    use HasFactory;
protected $table = 'historial_observaciones';
   // Define explícitamente la tabla que usa este modelo 
    protected $fillable = [
        'tarea_id', 
        'user_id', 
        'mensaje',
        'tipo',
        'archivo_path'
    ];
   
public function tarea()
{
    return $this->belongsTo(Tarea::class);
}

public function usuario()
{
    // 'user_id' es el nombre de la columna en tu tabla de historial
    
    return $this->belongsTo(User::class, 'user_id');
}
}
