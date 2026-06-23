<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; }
        .header-aprobada { background: #20a565; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .header-rechazada { background: #c0392b; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; background-color: #ffffff; }
        .footer { font-size: 11px; color: #999; margin-top: 20px; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #2c3e50; padding: 15px; margin: 15px 0; }
        .badge-aprobada { background: #27ae60; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-rechazada { background: #c0392b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        @if($solicitud->estado == 'aprobado') <div class="header-aprobada">
                <h2 style="margin:0;">Solicitud Aprobada</h2>
            </div>
            <div class="content">
                <p>Estimado(a) <strong>{{ $solicitud->nombre }}</strong>,</p>
                <p>Le informamos que su solicitud con código <span class="badge-aprobada">#{{ $solicitud->id }}</span> ha sido <strong>AUTORIZADA</strong>.</p>
                
                <div class="info-box">
                    <p>Adjunto los detalles de la solicitud Aprobada.</p>
                </div>
        @else
            <div class="header-rechazada">
                <h2 style="margin:0;">Solicitud Rechazada</h2>
            </div>
            <div class="content">
                <p>Estimado(a) <strong>{{ $solicitud->nombre }}</strong>,</p>
                <p>Lamentamos informarle que su solicitud con código <span class="badge-rechazada">#{{ $solicitud->id }}</span> ha sido <strong>RECHAZADA</strong>.</p>
                
                <div class="info-box" style="border-left-color: #c0392b;">
                    <p><strong>Motivo del rechazo:</strong></p>
                    <p>{{ $solicitud->observaciones ?? 'No se especificó un motivo.' }}</p>
                </div>
        @endif

            <p>Atentamente,<br>
            <strong>Gestión de Talento Humano (GTH)</strong></p>
        </div>

        <div class="footer">
            Este es un mensaje automático generado por el Sistema.<br>
            © {{ date('Y') }} Instituto Hondureño de Cultura Interamericana.
        </div>
    </div>
</body>
</html>