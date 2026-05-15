<?php

// Namespace donde se encuentra la clase de exportación
namespace App\Exports;

// Importaciones necesarias
use Illuminate\Contracts\View\View; // Permite retornar una vista Blade
use Maatwebsite\Excel\Concerns\FromView; // Exporta Excel usando una vista
use Maatwebsite\Excel\Concerns\ShouldAutoSize; // Ajusta automáticamente el ancho de columnas (aunque aquí no se usa)
use Maatwebsite\Excel\Concerns\WithDrawings; // Permite insertar imágenes en el Excel
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing; // Clase para manejar imágenes
use Illuminate\Support\Facades\DB; // Facade para consultas SQL

// Clase encargada de exportar el desempeño por departamento
class ExportarDesempenoDepto implements FromView, WithDrawings
{
    // Variables protegidas que almacenarán los filtros
    protected $depto_id, $anio, $periodo, $mes, $firma;

    // Constructor: recibe los parámetros necesarios para generar el reporte
    public function __construct($depto_id, $anio, $periodo, $mes = null, $firma = null) {

        // Guardamos el ID del departamento
        $this->depto_id = $depto_id;

        // Guardamos el año del reporte
        $this->anio = $anio;

        // Guardamos el tipo de periodo (mensual o anual)
        $this->periodo = $periodo;

        // Guardamos el mes si aplica
        $this->mes = $mes;

        $this->firma = $firma;  // Guardamos la firma si aplica
    }

    // Método encargado de insertar el logo en el archivo Excel
    public function drawings()
    {
    $drawings = [];

    // 1. DIBUJO DEL LOGO
    $logo = new Drawing();
    $logo->setName('Logo IHCI');
    $logo->setPath(public_path('images/IHCI.png')); 
    $logo->setHeight(75);
    $logo->setCoordinates('A1');
    $logo->setOffsetX(10);
    $logo->setOffsetY(10);
    $drawings[] = $logo;

    // 2. DIBUJO DE LA FIRMA
    if ($this->firma && $this->firma->imagen_path) {
        $imageData = $this->firma->imagen_path;

        if (is_resource($imageData)) {
            $imageData = stream_get_contents($imageData);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'firma_');
        file_put_contents($tempPath, $imageData);

        $signature = new Drawing();
        $signature->setName('Firma');
        $signature->setPath($tempPath);
        $signature->setHeight(50); // Un poquito más grande para que resalte

        // CAMBIO CLAVE: Bajamos la firma a la fila 15
        // Si la tabla es muy larga, puedes usar: 'B' . (count($datos) + 25)
        $signature->setCoordinates('B15'); 

        // Centrado manual dentro del bloque de firma
        $signature->setOffsetX(50); 
       
        
        $drawings[] = $signature;
    }

    return $drawings;
    }


    // Método principal que genera la vista para el Excel
    public function view(): View {
        $departamento = \App\Models\Departamento::find($this->depto_id);

        $query = DB::table('asignacion_evaluaciones as ae')
            ->join('empleados as e', 'ae.empleado_id', '=', 'e.id')
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')
            ->select(
                DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"),
                DB::raw("MAX(ae.created_at) as fecha"),
                DB::raw("AVG(ae.puntuacion_total) as resultado")
            )
            ->where('e.departamento_id', $this->depto_id)
            ->whereYear('ae.created_at', $this->anio)
            ->groupBy('actividad');

        if ($this->periodo == 'mensual' && $this->mes) {
            $query->whereMonth('ae.created_at', $this->mes);
            $periodo_texto = "Mensual (" . $this->mes . ")";
        } else {
            $periodo_texto = "Anual Acumulado";
        }

        $datos = $query->get();
        $promedio_depto = $datos->avg('resultado');

        return view('informes.excel_desempeno', [
            'datos' => $datos,
            'departamento' => $departamento,
            'anio' => $this->anio,
            'periodo_texto' => $periodo_texto,
            'promedio_depto' => $promedio_depto
        ]);
    }
}