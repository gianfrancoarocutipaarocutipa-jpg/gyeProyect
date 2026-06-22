<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Servicio</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #000; margin: 0; font-size: 24px; text-transform: uppercase; }
        .header h2 { color: #555; margin: 5px 0 0 0; font-size: 18px; }
        .header p { margin: 2px 0; color: #666; font-size: 12px; }
        .invoice-box { border: 1px solid #ccc; padding: 20px; border-radius: 5px; }
        .info-row { width: 100%; margin-bottom: 15px; }
        .info-col { display: inline-block; width: 49%; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .table th { background-color: #f4f4f4; color: #333; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .total-row th { font-size: 16px; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #777; border-top: 1px dashed #ccc; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <h1>G&E Motors</h1>
            <h2>Boleta de Servicio Electrónica</h2>
            <p>RUC: 20123456789 | Av. Industrial 123, Tacna</p>
            <p>Tel: (052) 123-456 | contacto@gemmotors.com</p>
        </div>

        <div class="info-row">
            <div class="info-col">
                <strong>Cliente:</strong> <?php echo htmlspecialchars($cliente->nombre); ?><br>
                <strong>DNI/RUC:</strong> <?php echo htmlspecialchars($cliente->dni_ruc); ?><br>
                <strong>Teléfono:</strong> <?php echo htmlspecialchars($cliente->telefono ?? 'N/A'); ?>
            </div>
            <div class="info-col" style="text-align: right;">
                <strong>Nº Orden:</strong> <?php echo htmlspecialchars($orden->numero_ot); ?><br>
                <strong>Fecha Emisión:</strong> <?php echo date('d/m/Y'); ?><br>
                <strong>Vehículo:</strong> <?php echo htmlspecialchars($vehiculo->placa); ?>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">Cant.</th>
                    <th style="width: 55%;">Descripción</th>
                    <th style="width: 20%;" class="text-right">P. Unit. (S/)</th>
                    <th style="width: 20%;" class="text-right">Importe (S/)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Repuestos -->
                <?php if (!empty($repuestos)): ?>
                    <tr>
                        <td colspan="4" style="background-color: #f9f9f9;"><strong>Repuestos e Insumos</strong></td>
                    </tr>
                    <?php foreach ($repuestos as $rep): ?>
                        <tr>
                            <td class="text-center"><?php echo $rep['cantidad']; ?></td>
                            <td><?php echo htmlspecialchars($rep['repuesto_nombre']); ?></td>
                            <td class="text-right"><?php echo number_format($rep['precio_unitario'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($rep['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Mano de Obra (Resumen) -->
                <tr>
                    <td colspan="4" style="background-color: #f9f9f9;"><strong>Servicios</strong></td>
                </tr>
                <tr>
                    <td class="text-center">1</td>
                    <td>Servicio de Reparación / Mantenimiento (Mano de Obra)</td>
                    <td class="text-right"><?php echo number_format($costoManoObra, 2); ?></td>
                    <td class="text-right"><?php echo number_format($costoManoObra, 2); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">SUBTOTAL:</th>
                    <th class="text-right">S/ <?php echo number_format($total / 1.18, 2); ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">IGV (18%):</th>
                    <th class="text-right">S/ <?php echo number_format($total - ($total / 1.18), 2); ?></th>
                </tr>
                <tr class="total-row">
                    <th colspan="3" class="text-right">TOTAL A PAGAR:</th>
                    <th class="text-right">S/ <?php echo number_format($total, 2); ?></th>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>Gracias por confiar en G&E Motors. ¡Su vehículo está en buenas manos!</p>
            <p>Esta es una representación impresa de la Boleta de Servicio Electrónica.</p>
        </div>
    </div>
</body>
</html>
