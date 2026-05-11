<!DOCTYPE html>
<html>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #2c3e50; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; border: 1px solid #e1e1e1; border-radius: 8px; overflow: hidden;">
        <div style="background-color: #28a745; color: white; padding: 20px; text-align: center;">
            <h2 style="margin: 0;">¡Tarea Aprobada!</h2>
        </div>
        
        <div style="padding: 30px;">
            <p>Estimado(a) colaborador(a),</p>
            
            <p>Le informamos que la siguiente tarea ha sido revisada y <strong>aprobada satisfactoriamente</strong>:</p>
            
            <div style="background-color: #f4fdf6; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;">
                <p style="margin: 0;"><strong>Tarea:</strong> {{ $tarea->titulo }}</p>
                <p style="margin: 5px 0 0 0;"><strong>Proyecto:</strong> {{ $tarea->proyecto->nombre }}</p>
            </div>
            
            <p>Agradecemos su compromiso y cumplimiento con los estándares institucionales.</p>
            
            <p style="margin-top: 30px;">Atentamente,<br>
            <strong>Gestión de Talento Humano (GTH) - IHCI</strong></p>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #7f8c8d;">
            Este es un mensaje automático, por favor no responder a este correo.
        </div>
    </div>
</body>
</html>