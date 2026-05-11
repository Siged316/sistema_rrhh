<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2 style="color: #0056b3;">Notificación de Revisión de Tareas</h2>
    
    <p>Estimado(a) colaborador(a),</p>
    
    <p>Le informamos que se ha completado la revisión de la tarea: <strong>{{ $tarea->titulo }}</strong>.</p>
    
    <p><strong>Observaciones y ajustes requeridos:</strong></p>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;">
        {{ $tarea->observaciones_jefe }}
    </div>
    
    <p>Por favor, ingrese al sistema para realizar las correcciones necesarias y actualizar el estado de la tarea.</p>
    
    <hr>
    <p style="font-size: 12px; color: #777;">Este es un mensaje automático generado por el Sistema de Gestión de RRHH - IHCI.</p>
</body>
</html>