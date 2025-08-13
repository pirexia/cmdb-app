<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso de Caducidad CMDB</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
        h2 { color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { font-size: 0.9em; color: #777; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Aviso de Caducidad de CMDB App</h2>
        <p>Estimado equipo de administración de CMDB,</p>
        <p>Este es un aviso automatizado de elementos próximos a caducar en los próximos <strong><?= htmlspecialchars($daysAdvance) ?> días</strong>.</p>

        <?php if (!empty($assets)): ?>
            <h3>Activos Próximos a Caducar</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Nº Serie</th>
                        <th>Tipo</th>
                        <th>Detalles de Caducidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td><?= htmlspecialchars($asset['id']) ?></td>
                            <td><?= htmlspecialchars($asset['name']) ?></td>
                            <td><?= htmlspecialchars($asset['serial'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($asset['type'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($asset['details']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($contracts)): ?>
            <h3>Contratos Próximos a Caducar</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nº Contrato</th>
                        <th>Tipo</th>
                        <th>Proveedor</th>
                        <th>Fecha Fin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><?= htmlspecialchars($contract['number']) ?></td>
                            <td><?= htmlspecialchars($contract['type'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($contract['provider'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($contract['endDate']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (empty($assets) && empty($contracts)): ?>
            <p>No se encontraron elementos próximos a caducar en los próximos <?= htmlspecialchars($daysAdvance) ?> días.</p>
        <?php endif; ?>

        <p>Por favor, revisa la aplicación CMDB para más detalles y tomar las acciones necesarias.</p>
        <p>Atentamente,<br>Tu CMDB App</p>
    </div>
    <div class="footer">
        Este es un correo automático, por favor no lo respondas.
    </div>
</body>
</html>
