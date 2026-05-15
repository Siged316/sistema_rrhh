<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class CompensatorioExport implements FromView, WithDrawings, ShouldAutoSize
{
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function drawings()
    {
        $drawings = [];

        // 1. Logo IHCI
        if (file_exists(public_path('images/IHCI.png'))) {
            $logo = new Drawing();
            $logo->setName('Logo IHCI');
            $logo->setPath(public_path('images/IHCI.png'));
            $logo->setHeight(60);
            $logo->setCoordinates('A1');
            $drawings[] = $logo;
        }

        // 2. Firma desde LONGBLOB (Cambiado para usar el objeto 'firma')
        $firmaObj = $this->data['firma'] ?? null;

        if ($firmaObj && $firmaObj->imagen_path) {
            $imageData = $firmaObj->imagen_path;

            // Si el LONGBLOB viene como recurso (stream), lo convertimos a string
            if (is_resource($imageData)) {
                $imageData = stream_get_contents($imageData);
            }

            // Creamos el archivo temporal
            $tempPath = tempnam(sys_get_temp_dir(), 'firma_');
            file_put_contents($tempPath, $imageData);

            $firma = new Drawing();
            $firma->setName('Firma Autorizada');
            $firma->setPath($tempPath);
            $firma->setHeight(60);
            
            // Calculamos la posición debajo de la tabla
            // Usamos un mínimo de fila 25 para que no choque con los datos de arriba
            $conteoRegistros = count($this->data['todosLosRegistros'] ?? []);
            $filaBase = $conteoRegistros + 18; 
            $puntoFinal = ($filaBase < 25) ? 20 : $filaBase;

            $firma->setCoordinates('B' . $puntoFinal);
            $firma->setOffsetX(50);
            $firma->setOffsetY(-10); // Ajuste leve hacia arriba para que quede sobre la línea
            
            $drawings[] = $firma;
        }

        return $drawings;
    }



    public function view(): View
    {
        return view('informes.compensatorio_excel', $this->data);
    }
}