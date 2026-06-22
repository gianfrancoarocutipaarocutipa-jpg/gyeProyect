<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico OBD-II</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #0056b3; padding-bottom: 10px; margin-bottom: 20px; }
        .header img { width: 150px; }
        .header h1 { color: #0056b3; margin: 10px 0 5px 0; font-size: 24px; }
        .header p { margin: 0; color: #666; font-size: 12px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table th, .info-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .info-table th { background-color: #f4f4f4; width: 25%; font-weight: bold; }
        h2 { font-size: 18px; color: #0056b3; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .diagnostico-item { margin-bottom: 15px; background-color: #f9f9f9; padding: 10px; border-left: 4px solid #d9534f; }
        .codigo { font-weight: bold; color: #d9534f; font-size: 16px; }
        .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 50px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>G&E Motors</h1>
        <p>RUC: 20123456789 | Av. Industrial 123, Tacna</p>
        <p>Teléfono: (052) 123-456 | Email: contacto@gemmotors.com</p>
    </div>

    <h2>Diagnóstico Electrónico Automotriz (OBD-II)</h2>
    
    <table class="info-table">
        <tr>
            <th>Orden de Trabajo</th>
            <td><?php echo htmlspecialchars($orden->numero_ot); ?></td>
            <th>Fecha</th>
            <td><?php echo date('d/m/Y', strtotime($orden->created_at)); ?></td>
        </tr>
        <tr>
            <th>Cliente</th>
            <td><?php echo htmlspecialchars($cliente->nombre); ?></td>
            <th>DNI/RUC</th>
            <td><?php echo htmlspecialchars($cliente->dni_ruc); ?></td>
        </tr>
        <tr>
            <th>Vehículo</th>
            <td><?php echo htmlspecialchars($vehiculo->marca . ' ' . $vehiculo->modelo . ' (' . $vehiculo->anio . ')'); ?></td>
            <th>Placa</th>
            <td><?php echo htmlspecialchars($vehiculo->placa); ?></td>
        </tr>
    </table>

    <h2>Resultados del Escáner</h2>
    <?php if (empty($diagnosticos)): ?>
        <p>No se encontraron códigos de falla registrados en esta orden.</p>
    <?php else: ?>
        <?php foreach ($diagnosticos as $diag): ?>
            <div class="diagnostico-item">
                <p><strong>Fecha de lectura:</strong> <?php echo date('d/m/Y H:i', strtotime($diag->created_at)); ?></p>
                <?php if (!empty($diag->codigos_falla)): ?>
                    <p><strong>Códigos Detectados:</strong></p>
                    <ul>
                        <?php foreach ($diag->codigos_falla as $codigo): ?>
                            <li><span class="codigo"><?php echo htmlspecialchars($codigo); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Sin códigos de falla.</p>
                <?php endif; ?>
                <p><strong>Observaciones del Mecánico:</strong><br>
                <?php echo nl2br(htmlspecialchars($diag->observaciones ?? 'Ninguna.')); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="footer">
        <p>Este documento es un reporte técnico generado por el sistema G&E Motors. No válido como comprobante de pago.</p>
        <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</body>
</html>
