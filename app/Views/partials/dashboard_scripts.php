<?php
// app/Views/partials/dashboard_scripts.php
// Script para la lógica del dashboard, incluyendo gráficos Chart.js y modal.
// Las variables PHP ($assetsByType, $assetsByStatus, $t, $daysAdvance) se pasan desde DashboardController.
?>

<script>
    // Datos para el gráfico de Activos por Tipo
    // Asegurarse de que las variables PHP se impriman correctamente en el JS.
    var assetsByTypeData = <?php echo json_encode($assetsByType); ?>;
    var assetTypeLabels = assetsByTypeData.map(item => item.type_name);
    var assetsTypeCounts = assetsByTypeData.map(item => item.count);

    var assetsByTypeCtx = document.getElementById('assetsByTypeChart').getContext('2d');
    new Chart(assetsByTypeCtx, {
        type: 'pie',
        data: {
            labels: assetTypeLabels,
            datasets: [{
                data: assetsTypeCounts,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: '<?php echo $t('assets_by_type'); ?>' // Traducción
                }
            }
        }
    });

    // Datos para el gráfico de Activos por Estado
    var assetsByStatusData = <?php echo json_encode($assetsByStatus); ?>;
    var assetStatusLabels = assetsByStatusData.map(item => item.status_name);
    var assetsStatusCounts = assetsByStatusData.map(item => item.count);

    var assetsByStatusCtx = document.getElementById('assetsByStatusChart').getContext('2d');
    new Chart(assetsByStatusCtx, {
        type: 'bar',
        data: {
            labels: assetStatusLabels,
            datasets: [{
                label: '<?php echo $t('number_of_assets'); ?>', // Traducción
                data: assetsStatusCounts,
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false,
                },
                title: {
                    display: true,
                    text: '<?php echo $t('assets_by_status'); ?>' // Traducción
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>
