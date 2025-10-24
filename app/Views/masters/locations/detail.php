<?php
/**
 * app/Views/masters/locations/detail.php
 *
 * Vista de detalle para una ubicación.
 */
$this->layout('layout/base', [
    'pageTitle' => $pageTitle ?? $t('location'),
    'flashMessages' => $flashMessages ?? []
]);
?>

<?php $this->start('head_assets') ?>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<?php $this->stop() ?>
<?php $this->start('page_content') ?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><?= $this->e($item['nombre']) ?></h4>
                    <a href="/admin/masters/location" class="btn btn-sm btn-secondary"><?= $t('go_back') ?? 'Volver' ?></a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><?= $t('details') ?></h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong><?= $t('name') ?>:</strong> <?= $this->e($item['nombre']) ?></li>
                                <li class="list-group-item"><strong><?= $t('description') ?>:</strong> <?= $this->e($item['descripcion'] ?? $t('na')) ?></li>
                                <li class="list-group-item"><strong><?= $t('direccion') ?>:</strong> <?= $this->e($item['direccion'] ?? $t('na')) ?></li>
                                <li class="list-group-item"><strong><?= $t('poblacion') ?>:</strong> <?= $this->e($item['poblacion'] ?? $t('na')) ?></li>
                                <li class="list-group-item"><strong><?= $t('codigo_postal') ?>:</strong> <?= $this->e($item['codigo_postal'] ?? $t('na')) ?></li>
                                <li class="list-group-item"><strong><?= $t('provincia') ?>:</strong> <?= $this->e($item['provincia'] ?? $t('na')) ?></li>
                                <li class="list-group-item"><strong><?= $t('pais') ?>:</strong> <?= $this->e($item['pais'] ?? $t('na')) ?></li>
                                <li class="list-group-item"><strong><?= $t('coordinates') ?>:</strong> <?= $this->e($item['latitud'] ?? $t('na')) ?>, <?= $this->e($item['longitud'] ?? $t('na')) ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($item['latitud']) && !empty($item['longitud'])): ?>
                                <div id="map" style="height: 400px; border-radius: 0.25rem;"></div>
                            <?php else: ?>
                                <div class="alert alert-info text-center" role="alert">
                                    <i class="bi bi-geo-alt-fill fs-3"></i>
                                    <p class="mt-2"><?= $t('no_coordinates_available') ?? 'No hay coordenadas disponibles para mostrar el mapa.' ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>

<?php $this->start('scripts') ?>
<?php if (!empty($item['latitud']) && !empty($item['longitud'])): ?>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        $(document).ready(function() {
            var lat = <?= json_encode((float)$item['latitud']) ?>;
            var lon = <?= json_encode((float)$item['longitud']) ?>;

            var map = L.map('map').setView([lat, lon], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            L.marker([lat, lon]).addTo(map)
                .bindPopup('<?= $this->e($item['nombre']) ?>')
                .openPopup();
        });
    </script>
<?php endif; ?>
<?php $this->stop() ?>