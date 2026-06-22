<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Ruta</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #0056b3; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #0056b3; margin: 10px 0 5px 0; font-size: 24px; }
        .header p { margin: 0; color: #666; font-size: 12px; }
        .info-table, .repuestos-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table th, .info-table td, .repuestos-table th, .repuestos-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .info-table th { background-color: #f4f4f4; width: 25%; font-weight: bold; }
        .repuestos-table th { background-color: #0056b3; color: white; }
        h2 { font-size: 18px; color: #0056b3; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 50px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .firmas { margin-top: 50px; text-align: center; width: 100%; }
        .firma { display: inline-block; width: 40%; margin: 0 5%; border-top: 1px solid #333; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>G&E Motors</h1>
        <p>Hoja de Ruta / Orden de Trabajo Interna</p>
    </div>

    <table class="info-table">
        <tr>
            <th>Nº Orden</th>
            <td><strong><?php echo htmlspecialchars($orden->numero_ot); ?></strong></td>
            <th>Fecha Ingreso</th>
            <td><?php echo date('d/m/Y', strtotime($orden->created_at)); ?></td>
        </tr>
        <tr>
            <th>Vehículo</th>
            <td><?php echo htmlspecialchars($vehiculo->marca . ' ' . $vehiculo->modelo); ?></td>
            <th>Placa</th>
            <td><?php echo htmlspecialchars($vehiculo->placa); ?></td>
        </tr>
        <tr>
            <th>Mecánico Asignado</th>
            <td colspan="3"><?php echo htmlspecialchars($mecanico ? $mecanico->nombre . ' ' . $mecanico->apellido : 'No asignado'); ?></td>
        </tr>
    </table>

    <h2>Descripción del Problema</h2>
    <p style="padding: 10px; background-color: #f9f9f9; border: 1px solid #eee;">
        <?php echo nl2br(htmlspecialchars($orden->descripcion_problema)); ?>
    </p>

    <h2>Repuestos Autorizados</h2>
    <table class="repuestos-table">
        <thead>
            <tr>
                <th>Código OEM</th>
                <th>Repuesto</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($repuestos)): ?>
                <tr>
                    <td colspan="3" style="text-align: center;">No hay repuestos asignados a esta orden.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($repuestos as $rep): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rep['codigo_oem']); ?></td>
                        <td><?php echo htmlspecialchars($rep['repuesto_nombre']); ?></td>
                        <td style="text-align: center;"><?php echo htmlspecialchars((string)$rep['cantidad']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="firmas">
        <div class="firma">Firma Mecánico</div>
        <div class="firma">VºBº Jefe de Taller</div>
    </div>

    <div class="footer">
        <p>Uso exclusivo interno de G&E Motors.</p>
        <p>Impreso el <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</body>
</html>
