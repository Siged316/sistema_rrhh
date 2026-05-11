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
    protected $depto_id, $anio, $periodo, $mes;

    // Constructor: recibe los parámetros necesarios para generar el reporte
    public function __construct($depto_id, $anio, $periodo, $mes = null) {

        // Guardamos el ID del departamento
        $this->depto_id = $depto_id;

        // Guardamos el año del reporte
        $this->anio = $anio;

        // Guardamos el tipo de periodo (mensual o anual)
        $this->periodo = $periodo;

        // Guardamos el mes si aplica
        $this->mes = $mes;
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
    public function view(): View {

        // Buscamos el departamento por ID
        $departamento = \App\Models\Departamento::find($this->depto_id);

        // Construcción de la consulta
        $query = DB::table('asignacion_evaluaciones as ae')

            // Relación con empleados
            ->join('empleados as e', 'ae.empleado_id', '=', 'e.id')

            // Relación opcional con proyectos
            ->leftJoin('proyectos as p', 'ae.proyecto_id', '=', 'p.id')

            // Selección de campos
            ->select(

                // Si existe proyecto usa el nombre del proyecto,
                // de lo contrario usa el tipo de evaluación
                DB::raw("COALESCE(p.nombre, ae.tipo) as actividad"),

                // Obtiene la fecha más reciente
                DB::raw("MAX(ae.created_at) as fecha"),

                // Calcula el promedio de puntuaciones
                DB::raw("AVG(ae.puntuacion_total) as resultado")
            )

            // Filtra por departamento
            ->where('e.departamento_id', $this->depto_id)

            // Filtra por año
            ->whereYear('ae.created_at', $this->anio)

            // Agrupa por actividad
            ->groupBy('actividad');

        // Validamos si el reporte será mensual
        if ($this->periodo == 'mensual' && $this->mes) {

            // Filtra por mes
            $query->whereMonth('ae.created_at', $this->mes);

            // Texto descriptivo del periodo
            $periodo_texto = "Mensual (" . $this->mes . ")";

        } else {

            // Si no es mensual será anual acumulado
            $periodo_texto = "Anual Acumulado";
        }

        // Ejecutamos la consulta
        $datos = $query->get();

        // Calculamos el promedio general del departamento
        $promedio_depto = $datos->avg('resultado');

        // Retornamos la vista Blade que generará el Excel
        return view('informes.excel_desempeno', [

            // Datos de evaluaciones
            'datos' => $datos,

            // Información del departamento
            'departamento' => $departamento,

            // Año seleccionado
            'anio' => $this->anio,

            // Texto del periodo
            'periodo_texto' => $periodo_texto,

            // Promedio general
            'promedio_depto' => $promedio_depto
        ]);
    }
}