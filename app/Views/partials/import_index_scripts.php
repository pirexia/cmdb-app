<script>
    // Script para la lógica dinámica de descarga de plantilla de activos en el módulo de importación.
    $(document).ready(function() {
        var assetTypeSelect = $('#asset_type_select');
        var downloadButton = $('#download_assets_template_btn');

        // Función para actualizar el enlace de descarga de la plantilla de activos.
        function updateDownloadLink() {
            var selectedAssetTypeId = assetTypeSelect.val(); // Obtiene el ID del tipo de activo seleccionado.
            if (selectedAssetTypeId) {
                // Construye la URL de descarga con el ID del tipo de activo.
                downloadButton.attr('href', '/admin/import/template/assets/' + selectedAssetTypeId);
                downloadButton.removeClass('disabled'); // Habilita el botón de descarga.
            } else {
                // Si no hay tipo de activo seleccionado, deshabilita el botón.
                downloadButton.attr('href', '#');
                downloadButton.addClass('disabled');
            }
        }
        updateDownloadLink(); // Inicializa el estado del enlace de descarga al cargar la página.
        assetTypeSelect.change(function() { updateDownloadLink(); }); // Actualiza el enlace cuando el tipo de activo en el select cambia.
    });
</script>
