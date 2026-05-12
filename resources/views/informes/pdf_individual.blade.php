<!DOCTYPE html>
<html>
<head>
    <title>Reporte Individual de Desempeño</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #003366; padding-bottom: 10px; }
        .title { color: #003366; text-transform: uppercase; margin: 0; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; font-size: 14px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th { background-color: #003366; color: white; padding: 10px; font-size: 13px; text-align: left; }
        .data-table td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #777; }
        .puntuacion { font-weight: bold; color: #003366; }
    </style>
</head>
<body>
    <div class="header">
        <h2 class="title">Instituto Hondureño de Cultura Interamericana</h2>
        <h3>Reporte Individual de Evaluación de Desempeño</h3>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Colaborador:</strong> {{ $empleado->nombre }} {{ $empleado->apellido }}</td>
            <td><strong>Año:</strong> {{ $anio }}</td>
        </tr>
        <tr>
            <td><strong>Fecha de Impresión:</strong> {{ date('d/m/Y H:i A') }}</td>
            <td><strong>Departamento:</strong> {{ $empleado->departamento->nombre ?? 'N/A' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Actividad / Proyecto</th>
                <th>Fecha de Evaluación</th>
                <th>Resultado (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datos as $dato)
                <tr>
                    <td>{{ $dato->actividad }}</td>
                    <td>{{ \Carbon\Carbon::parse($dato->fecha)->format('d/m/Y') }}</td>
                    <td class="puntuacion">{{ number_format($dato->resultado, 2) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">No se registraron evaluaciones en este periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Sistema de Recursos Humanos IHCI - Generado de forma automática
    </div>
</body>
</html>