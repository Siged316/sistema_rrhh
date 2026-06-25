<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class SolicitudActualizada extends Mailable
{
    use SerializesModels;

    public $solicitud;
    public $estado;

    public function __construct($solicitud, $estado)
    {
        $this->solicitud = $solicitud;
        $this->estado = $estado;
    }

    public function build()
    {
        return $this->subject('Notificación de Solicitud: ' . strtoupper($this->estado))
                    ->view('emails.solicitud_estado'); 
    }

public function attachments(): array
{
    // 1. Empleado (igual que SHOW)
    $partes = explode(' ', trim($this->solicitud->nombre));

    $empleado = \App\Models\Empleado::where('nombre', 'LIKE', '%' . ($partes[0] ?? '') . '%')
        ->where('apellido', 'LIKE', '%' . end($partes) . '%')
        ->first();

    // 2. 
    $totalDerechoHistorico = 0;

    $solicitadoA = null;
    $cargoAutorizador = null;

    if ($empleado && $empleado->departamento_id) {
        $departamento = \App\Models\Departamento::find($empleado->departamento_id);
        
        if ($departamento && $departamento->jefe_empleado_id) {
            $jefe = \App\Models\Empleado::find($departamento->jefe_empleado_id);
            if ($jefe) {
                $solicitadoA = $jefe->nombre . ' ' . $jefe->apellido;
                $cargoAutorizador = $jefe->cargo;
            }
        }
    }

    if ($empleado) {

        $fechaIngreso = \Carbon\Carbon::parse($empleado->fecha_ingreso);
        $ahora = now();

        $aniosCumplidos = floor($fechaIngreso->diffInYears($ahora));

        $tipoContrato = strtolower($empleado->tipo_contrato ?? '');
        $tipo = strtolower(trim($this->solicitud->tipo));
        $esVacaciones = str_contains($tipo, 'vacaciones');
        $esOtros = str_contains(strtolower($this->solicitud->tipo), 'otros');

        if ($tipoContrato === 'permanente') {

            $ciclosACalcular = $aniosCumplidos + 1;

            for ($i = 1; $i <= $ciclosACalcular; $i++) {

                $anioBusqueda = ($i > 4) ? 4 : $i;

                $politica = \App\Models\PoliticaVacaciones::whereRaw('LOWER(tipo_contrato) = ?', ['permanente'])
                    ->where('anio_antiguedad', $anioBusqueda)
                    ->first();

                if ($politica) {
                    $totalDerechoHistorico += $politica->dias_anuales;
                }
            }

        } else {

            $politica = \App\Models\PoliticaVacaciones::whereRaw('LOWER(tipo_contrato) = ?', [$tipoContrato])->first();

            $totalDerechoHistorico = $politica
                ? $politica->dias_anuales
                : ($empleado->dias_vacaciones_anuales ?? 0);
        }
    }

    // 3. Días consumidos
    $diasConsumidosOficial = (int) \DB::table('vacaciones')
        ->where('empleado_id', $empleado->id)
        ->where('estado', 'aprobado')
        ->sum('dias_aprobados');

    // 4. Saldo (igual SHOW)
    if ($this->solicitud->estado === 'aprobado' && $esVacaciones) {

        $saldoActual = $totalDerechoHistorico - ($diasConsumidosOficial - $this->solicitud->dias);
        $nuevoSaldo  = $totalDerechoHistorico - $diasConsumidosOficial;

    } else {

        $saldoActual = $totalDerechoHistorico - $diasConsumidosOficial;
        $nuevoSaldo  = $esVacaciones ? ($saldoActual - $this->solicitud->dias) : $saldoActual;
    }
    
    $aprobaciones = \App\Models\SolicitudAprobacion::with('firma')
    ->where('solicitud_id', $this->solicitud->id)
    ->orderBy('paso_orden')
    ->get();
    
   

    // 5. PDF
   $pdf = Pdf::loadView('pdf.solicitud', [
     'solicitud' => $this->solicitud,
     'empleado' => $empleado,
     'saldoActual' => $saldoActual,
     'nuevoSaldo' => $nuevoSaldo,
     'solicitante' => $this->solicitud->nombre,
      'tipo' => $tipo,
      'esOtros' => $esOtros,
     'aprobaciones' => $aprobaciones,
     'solicitadoA' => $solicitadoA,         
     'cargoAutorizador' => $cargoAutorizador,

    ]);

    return [
        Attachment::fromData(fn () => $pdf->output(), 'Solicitud_' . $this->solicitud->id . '.pdf')
            ->withMime('application/pdf'),
    ];
}
}