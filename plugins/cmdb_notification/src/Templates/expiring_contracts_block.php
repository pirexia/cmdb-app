<h3><?= $t('contracts_expiring') ?></h3>
<table>
    <thead>
        <tr>
            <th><?= $t('contract_number') ?></th>
            <th><?= $t('contract_type') ?></th>
            <th><?= $t('provider') ?></th>
            <th><?= $t('end_date') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['numero_contrato']) ?></td>
                <td><?= htmlspecialchars($item['tipo_contrato_nombre'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($item['proveedor_nombre'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($item['fecha_fin']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>