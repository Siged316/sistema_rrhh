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
    protected  $firma;

     // Constructor: recibe los parámetros necesarios para generar el reporte
    public function __construct($empleado, $datos, $periodo_texto, $anio, $promedio_individual, $firma)
    {
        $this->empleado = $empleado;
        $this->datos = $datos;
        $this->periodo_texto = $periodo_texto;
        $this->anio = $anio;
        $this->promedio_individual = $promedio_individual;
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

        // CAMBIO CLAVE: Bajamos la firma a la fila 25
        // Si la tabla es muy larga, puedes usar: 'B' . (count($datos) + 25)
        $signature->setCoordinates('B27'); 

        // Centrado manual dentro del bloque de firma
        $signature->setOffsetX(50); 
       
        
        $drawings[] = $signature;
    }

    return $drawings;
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