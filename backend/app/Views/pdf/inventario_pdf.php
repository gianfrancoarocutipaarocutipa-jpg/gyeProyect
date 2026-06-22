<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario de Repuestos - G&E Motors</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #4F46E5; /* Indigo-600 */
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #4F46E5;
            margin: 0 0 5px 0;
            font-size: 22px;
        }
        .header p {
            color: #666;
            margin: 0;
            font-size: 12px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4F46E5;
            color: #ffffff;
            font-weight: bold;
            font-size: 12px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .low-stock {
            color: #dc2626; /* Red-600 */
            font-weight: bold;
        }
        .normal-stock {
            color: #16a34a; /* Green-600 */
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>G&E Motors</h1>
        <p>Reporte Oficial de Inventario de Repuestos</p>
    </div>

    <div class="info-section">
        <p><strong>Fecha de Emisión:</strong> <?= $fecha ?></p>
        <p><strong>Total de Artículos:</strong> <?= count($repuestos) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código OEM</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Marca Fabricante</th>
                <th class="text-center">Stock Mínimo</th>
                <th class="text-center">Stock Actual</th>
                <th class="text-right">Precio Unitario</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($repuestos as $repuesto): ?>
                <?php 
                    $isLowStock = $repuesto['stock'] <= $repuesto['stock_minimo'];
                    $stockClass = $isLowStock ? 'low-stock' : 'normal-stock';
                ?>
                <tr>
                    <td><?= htmlspecialchars($repuesto['codigo_oem']) ?></td>
                    <td><?= htmlspecialchars($repuesto['nombre']) ?></td>
                    <td><?= htmlspecialchars($repuesto['categoria'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($repuesto['marca_fabricante'] ?? '-') ?></td>
                    <td class="text-center"><?= $repuesto['stock_minimo'] ?></td>
                    <td class="text-center <?= $stockClass ?>"><?= $repuesto['stock'] ?></td>
                    <td class="text-right">$<?= number_format((float)$repuesto['precio_unitario'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Este documento es generado automáticamente por el Sistema de Gestión G&E Motors.
    </div>

</body>
</html>
