<?php
/**
 * app/Views/masters/locations/form.php
 *
 * Formulario para crear/editar una ubicación.
 */
$this->layout('layout/base', [
    'pageTitle' => $pageTitle ?? $t('location'),
    'flashMessages' => $flashMessages ?? []
]);

$this->start('head_assets'); ?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<?php $this->stop();

$isCreateMode = !isset($item);
$formAction = $isCreateMode ? '/admin/masters/location/create' : '/admin/masters/location/update/' . ($item['id'] ?? '');
?>
<?php $this->start('page_content') ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="card-title mb-0"><?= $this->e($pageTitle) ?></h4>
                </div>
                <div class="card-body">
                    <form action="<?= $this->e($formAction) ?>" method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label"><?= $t('name') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= $this->e($item['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label"><?= $t('description') ?></label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= $this->e($item['descripcion'] ?? '') ?></textarea>
                        </div>

                        <hr>
                        <h5 class="mb-3"><?= $t('direccion') ?> (Opcional)</h5>
                        <p class="text-muted small"><?= $t('location_form_geolocate_hint') ?? 'Rellenar estos campos permitirá geolocalizar la ubicación en un mapa.' ?></p>

                        <div class="mb-3">
                            <label for="direccion" class="form-label"><?= $t('direccion') ?></label>
                            <input type="text" class="form-control" id="direccion" name="direccion" value="<?= $this->e($item['direccion'] ?? '') ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="codigo_postal" class="form-label"><?= $t('codigo_postal') ?></label>
                                <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" value="<?= $this->e($item['codigo_postal'] ?? '') ?>">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="poblacion" class="form-label"><?= $t('poblacion') ?></label>
                                <input type="text" class="form-control" id="poblacion" name="poblacion" value="<?= $this->e($item['poblacion'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="provincia" class="form-label"><?= $t('provincia') ?></label>
                                <input type="text" class="form-control" id="provincia" name="provincia" value="<?= $this->e($item['provincia'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pais" class="form-label"><?= $t('pais') ?? 'País' ?></label>
                                <input type="text" class="form-control" id="pais" name="pais" value="<?= $this->e($item['pais'] ?? '') ?>">
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3"><?= $t('coordinates') ?></h5>
                        <div id="geocode-result" class="mb-3"></div> <!-- Contenedor para alertas -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="latitud" class="form-label">Latitud</label>
                                <input type="text" class="form-control" id="latitud" name="latitud" value="<?= $this->e($item['latitud'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="longitud" class="form-label">Longitud</label>
                                <input type="text" class="form-control" id="longitud" name="longitud" value="<?= $this->e($item['longitud'] ?? '') ?>" readonly>
                            </div>
                        </div>
                        <!-- Contenedor del Mapa -->
                        <div id="map-container" class="mt-3" style="display: none;">
                            <p class="text-muted small"><?= $t('location_form_map_hint') ?? 'Haz clic en el mapa para ajustar la posición exacta.' ?></p>
                            <div id="map" style="height: 300px; border-radius: 0.25rem;"></div>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="/admin/masters/location" class="btn btn-secondary"><?= $t('cancel') ?></a>
                            <button type="submit" class="btn btn-primary"><?= $t('save') ?></button>
                            <button type="button" id="geocode-btn" class="btn btn-info"><?= $t('search_coordinates') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>

<?php $this->start('scripts') ?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
var map = null;
var marker = null;

function updateCoordinates(lat, lon) {
    $('#latitud').val(lat.toFixed(6));
    $('#longitud').val(lon.toFixed(6));
}

$(document).ready(function() {
    // --- Lógica del botón de geocodificación ---
    $('#geocode-btn').on('click', function() {
        var btn = $(this);
        var resultDiv = $('#geocode-result');
        
        // Deshabilitar botón y mostrar carga
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Buscando...');
        resultDiv.html('');

        var addressData = {
            direccion: $('#direccion').val(),
            codigo_postal: $('#codigo_postal').val(),
            poblacion: $('#poblacion').val(),
            provincia: $('#provincia').val(),
            pais: $('#pais').val()
        };

        $.ajax({
            url: '/api/geocode',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(addressData),
            success: function(response) {
                if (response.success) {
                    $('#latitud').val(response.data.lat);
                    $('#longitud').val(response.data.lon);
                    
                    if (response.data.is_approximate) {
                        resultDiv.html('<div class="alert alert-warning"><?= $t('approximate_coordinates_found') ?></div>');
                    } else {
                        resultDiv.html('<div class="alert alert-success"><?= $t('coordinates_found_successfully') ?></div>');
                    }
                    initializeOrUpdateMap(response.data.lat, response.data.lon);
                } else {
                    $('#latitud').val('');
                    $('#longitud').val('');
                    resultDiv.html('<div class="alert alert-danger"><?= $t('no_coordinates_found_for_address') ?></div>');
                }
            },
            error: function(xhr) {
                resultDiv.html('<div class="alert alert-danger">Error en la comunicación con la API.</div>');
            },
            complete: function() {
                // Habilitar botón y restaurar texto
                btn.prop('disabled', false).html('<?= $t('search_coordinates') ?>');
            }
        });
    });

    // --- Lógica del mapa interactivo ---
    function initializeOrUpdateMap(lat, lon) {
        $('#map-container').show();

        if (map) {
            // Si el mapa ya existe, simplemente movemos la vista y el marcador
            map.setView([lat, lon], 15);
            if (marker) {
                marker.setLatLng([lat, lon]);
            } else {
                marker = L.marker([lat, lon], { draggable: true }).addTo(map);
                addMarkerDragEvent();
            }
        } else {
            // Si el mapa no existe, lo inicializamos
            map = L.map('map').setView([lat, lon], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            marker = L.marker([lat, lon], { draggable: true }).addTo(map);
            addMarkerDragEvent();

            // Actualizar coordenadas al hacer clic en el mapa
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateCoordinates(e.latlng.lat, e.latlng.lng);
            });
        }

        // Forzar al mapa a recalcular su tamaño.
        // Esto es crucial para evitar el problema de los cuadros grises cuando el mapa
        // se inicializa en un contenedor oculto. Usamos requestAnimationFrame para
        // asegurarnos de que se ejecute después de que el navegador haya renderizado el contenedor.
        requestAnimationFrame(function() {
            if (map) {
                map.invalidateSize(true); // El 'true' fuerza una actualización más agresiva.
            }
        });
    }

    function addMarkerDragEvent() {
        marker.on('dragend', function(e) {
            var position = marker.getLatLng();
            updateCoordinates(position.lat, position.lng);
        });
    }

    // Si la página se carga con coordenadas (modo edición), inicializar el mapa
    var initialLat = $('#latitud').val();
    var initialLon = $('#longitud').val();
    if (initialLat && initialLon) {
        initializeOrUpdateMap(parseFloat(initialLat), parseFloat(initialLon));
    }
});
</script>
<?php $this->stop() ?>