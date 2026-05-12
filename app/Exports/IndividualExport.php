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
class IndividualExport implements FromView, WithDrawings
{
    protected $empleado;
    protected $datos;
    protected $periodo_texto;
    protected $anio;
    protected $promedio_individual;

     // Constructor: recibe los parámetros necesarios para generar el reporte
    public function __construct($empleado, $datos, $periodo_texto, $anio, $promedio_individual)
    {
        $this->empleado = $empleado;
        $this->datos = $datos;
        $this->periodo_texto = $periodo_texto;
        $this->anio = $anio;
        $this->promedio_individual = $promedio_individual;
    }

     // Método encargado de insertar el logo en el archivo Excel
    public function drawings()
    {
        // Creamos una nueva imagen
        $drawing = new Drawing();

        // Nombre interno de la imagen
        $drawing->setName('Logo IHCI');

        // Descripción de la imagen
        $drawing->setDescription('Logo Institucional');
        
        // Ruta de la imagen dentro de la carpeta public/images
        $drawing->setPath(public_path('images/IHCI.png')); 
        
        // Altura de la imagen
        $drawing->setHeight(75);

        // Celda donde se colocará el logo
        $drawing->setCoordinates('A1');
        
        // Separación horizontal respecto al borde
        $drawing->setOffsetX(10);

        // Separación vertical respecto al borde
        $drawing->setOffsetY(10);
        
        // Retornamos la imagen
        return $drawing;
    }

      // Método principal que genera la vista para el Excel
    public function view(): View
    {
        return view('informes.excel_individual', [
            'empleado' => $this->empleado,
            'datos' => $this->datos,
            'periodo_texto' => $this->periodo_texto,
            'anio' => $this->anio,
            'promedio_individual' => $this->promedio_individual
        ]);
    }
}