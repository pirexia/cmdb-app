<h3><?= $t('assets_expiring') ?></h3>
<table>
    <thead>
        <tr>
            <th><?= $t('asset_name') ?></th>
            <th><?= $t('serial_number') ?></th>
            <th><?= $t('asset_type') ?></th>
            <th><?= $t('expiration_details') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['nombre']) ?></td>
                <td><?= htmlspecialchars($item['numero_serie'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($item['tipo_activo_nombre'] ?? 'N/A') ?></td>
                <td>
                    <?php
                    $details = [];
                    if ($item['fecha_fin_garantia']) $details[] = $t('warranty') . ": " . $item['fecha_fin_garantia'];
                    if ($item['fecha_fin_mantenimiento']) $details[] = $t('maintenance') . ": " . $item['fecha_fin_mantenimiento'];
                    echo implode('<br>', $details);
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>