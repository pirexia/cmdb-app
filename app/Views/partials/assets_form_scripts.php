<?php
// app/Views/partials/assets_form_scripts.php
// Este archivo contiene la lógica JavaScript específica para el formulario de creación/edición de activos.
// Gestiona los desplegables dependientes (Tipo -> Fabricante -> Modelo) y la carga de campos personalizados.
// Las variables PHP ($t) se pasan desde layout/base.php.
?>

$(document).ready(function() {
    // --- Lógica para campos dependientes en el formulario de activos ---
    var tipoActivoSelect = $('#id_tipo_activo');
    var fabricanteSelect = $('#id_fabricante');
    var modeloSelect = $('#id_modelo');

    // Función para establecer el estado inicial de los campos al cargar la página.
    function initializeAssetFormState() {
        // Si no hay un tipo de activo seleccionado (ej. en /assets/create),
        // deshabilitar fabricante y modelo.
        if (!tipoActivoSelect.val()) {
            fabricanteSelect.prop('disabled', true);
            modeloSelect.prop('disabled', true);
        }
        // Si hay un tipo de activo pero no un fabricante (ej. en edición),
        // deshabilitar solo el modelo.
        else if (!fabricanteSelect.val()) {
            modeloSelect.prop('disabled', true);
        }
    }

    // Habilitar el campo de fabricante cuando se selecciona un tipo de activo.
    tipoActivoSelect.change(function() {
        var tipoActivoId = $(this).val();
        fabricanteSelect.prop('disabled', !tipoActivoId);
        // Al cambiar el tipo, siempre se resetea y deshabilita el modelo.
        modeloSelect.prop('disabled', true).empty().append('<option value=""><?= $t('select_a_model') ?></option>');
    });

    // --- Lógica para el filtrado dinámico de modelos por fabricante en el formulario de activos ---
    // Se activa cuando el valor del select con ID 'id_fabricante' cambia.
    $('#id_fabricante').change(function() {
        var fabricanteId = $(this).val(); // Obtiene el ID del fabricante seleccionado.
        var modeloSelect = $('#id_modelo'); // Referencia al select de modelos.
        
        // Limpia las opciones actuales y añade una opción por defecto.
        modeloSelect.empty().append('<option value=""><?= $t('select_a_model') ?></option>');

        console.log('Fabricante seleccionado ID:', fabricanteId); // Depuración en consola.

        // Habilita o deshabilita el campo de modelos según si hay un fabricante seleccionado.
        if (fabricanteId) {
            modeloSelect.prop('disabled', false); // Habilita el campo Modelo.
            
            // Realiza una llamada AJAX para obtener los modelos asociados al fabricante seleccionado.
            $.ajax({
                url: '/api/models/byManufacturer/' + fabricanteId, // Endpoint API para obtener modelos por fabricante.
                method: 'GET', // Método HTTP GET.
                success: function(models) {
                    console.log('Modelos recibidos de la API (fabricante):', models); // Depuración: muestra los modelos recibidos.
                    
                    if (models && models.length > 0) {
                        // Itera sobre los modelos recibidos y los añade como opciones al select.
                        $.each(models, function(index, model) {
                            modeloSelect.append('<option value="' + model.id + '">' + model.nombre + '</option>');
                        });

                        // Si el formulario está en modo edición y ya hay un modelo seleccionado para el activo,
                        // intenta pre-seleccionar ese modelo en la lista filtrada.
                        // El ID del modelo actual se lee de un atributo 'data-' en el contenedor de campos personalizados.
                        var currentAssetModelId = $('#custom-fields-container').data('current-asset-model-id');
                        console.log('ID de modelo de activo actual (para pre-seleccionar):', currentAssetModelId); // Depuración.

                        if (currentAssetModelId) {
                            var found = false;
                            if (Array.isArray(models)) { 
                                models.forEach(function(model) {
                                    if (model.id == currentAssetModelId) { 
                                        found = true;
                                    }
                                });
                            }
                            if (found) {
                                modeloSelect.val(currentAssetModelId); // Selecciona el modelo si se encuentra en la lista.
                                console.log('Modelo actual pre-seleccionado:', currentAssetModelId); // Depuración.
                            } else {
                                console.log('Modelo actual no encontrado en la lista filtrada.'); // Depuración.
                            }
                        }
                    } else {
                        // Si no se reciben modelos para el fabricante, muestra un mensaje.
                        modeloSelect.append('<option value="">No hay modelos para este fabricante</option>');
                    }
                },
                error: function(xhr, status, error) {
                    // Manejo de errores de la llamada AJAX.
                    console.error('Error al cargar modelos por fabricante:', error);
                    modeloSelect.append('<option value="">Error al cargar modelos</option>');
                }
            });
        } else {
            modeloSelect.prop('disabled', true);  // Deshabilita el campo Modelo si no hay fabricante seleccionado.
            console.log('No se ha seleccionado fabricante. Modelo deshabilitado.'); // Depuración.
        }
    });

    // --- Lógica para cargar campos personalizados cuando cambia el tipo de activo ---
    // originalAssetTypeId guarda el ID del tipo de activo inicial (útil en modo edición).
    var originalAssetTypeId = $('#id_tipo_activo').val();
    var typeChangeWarningDiv = $('#type-change-warning'); // Div de advertencia al cambiar el tipo de activo.

    // Obtiene el contenedor de campos personalizados y lee los valores iniciales de un atributo 'data-'.
    var customFieldsContainer = $('#custom-fields-container');
    var rawCustomValuesFromDataAttr = customFieldsContainer.data('custom-field-values'); // Lee el JSON desde el atributo data-.

    // Convierte la cadena JSON de valores personalizados a un objeto JavaScript.
    var initialCustomValuesMap = {};
    if (rawCustomValuesFromDataAttr) {
        try {
            // jQuery ya parsea automáticamente los atributos data-* como JSON si son válidos.
            // Si ya es un objeto, no hace falta JSON.parse. Si es string, sí.
            var parsedValues = (typeof rawCustomValuesFromDataAttr === 'string') ? JSON.parse(rawCustomValuesFromDataAttr) : rawCustomValuesFromDataAttr;
            if (Array.isArray(parsedValues)) {
                parsedValues.forEach(function(cfv) {
                    initialCustomValuesMap[cfv.id_definicion_campo] = cfv.valor;
                });
            }
        } catch (e) {
            console.error("Error parsing initial custom field values from data-attribute:", e, rawCustomValuesFromDataAttr);
            // Si hay un error de parseo, initialCustomValuesMap se mantiene vacío.
        }
    }
    console.log('Valores personalizados iniciales (desde data-attribute, parseado JS):', initialCustomValuesMap); // Depuración.


    // Se activa cuando el valor del select con ID 'id_tipo_activo' cambia.
    tipoActivoSelect.change(function() { // Usamos tipoActivoSelect que ya está definido
        var currentAssetTypeId = $(this).val(); // Obtiene el ID del tipo de activo seleccionado.
        customFieldsContainer.empty(); // Limpia los campos personalizados existentes en el contenedor.

        // Muestra u oculta la advertencia si el tipo de activo ha cambiado en modo edición.
        if (originalAssetTypeId && originalAssetTypeId !== currentAssetTypeId) {
            typeChangeWarningDiv.removeClass('d-none'); // Muestra la advertencia.
        } else {
            typeChangeWarningDiv.addClass('d-none');    // Oculta la advertencia.
        }

        // Si se ha seleccionado un tipo de activo, realiza una llamada AJAX para obtener sus definiciones de campos.
        if (currentAssetTypeId) {
            $.ajax({
                url: '/api/custom-fields/definitions/' + currentAssetTypeId, // Endpoint API para definiciones de campos.
                method: 'GET', // Método HTTP GET.
                success: function(definitions) {
                    console.log('Definiciones de campos personalizadas recibidas (AJAX):', definitions); // Depuración.
                    
                    // Determina qué mapa de valores usar para renderizar:
                    // - initialCustomValuesMap si el tipo de activo es el mismo que al cargar la página (modo edición).
                    // - Un mapa vacío si el tipo de activo ha cambiado o es un nuevo activo (para no pre-llenar con valores incorrectos).
                    var valuesToRenderMap = (currentAssetTypeId == originalAssetTypeId) ? initialCustomValuesMap : {};
                    renderCustomFields(definitions, valuesToRenderMap); // Llama a la función para renderizar los campos.
                },
                error: function(xhr, status, error) {
                    // Manejo de errores de la llamada AJAX.
                    console.error('Error al cargar definiciones de campos personalizados (AJAX):', error);
                    customFieldsContainer.html('<div class="alert alert-danger" role="alert">' + <?= json_encode($t('error_loading_custom_fields')) ?> + '</div>');
                }
            });
        } else {
            // Si no hay tipo de activo seleccionado, muestra un mensaje informativo.
            customFieldsContainer.html('<div class="alert alert-info" role="alert">' + <?= json_encode($t('select_asset_type_for_custom_fields')) ?> + '</div>');
            typeChangeWarningDiv.addClass('d-none'); // Asegura que la advertencia esté oculta.
        }
    });

    // --- Función para renderizar los campos personalizados en el formulario ---
    // Esta función es llamada desde el AJAX (al cambiar el tipo de activo) y desde la carga inicial.
    // Recibe las DEFINICIONES de los campos y un MAPA de valores para pre-llenarlos.
    function renderCustomFields(definitions, valuesMap) { 
        var customFieldsContainer = $('#custom-fields-container');
        customFieldsContainer.empty(); // Limpia el contenedor antes de añadir nuevos campos.

        if (definitions && definitions.length > 0) {
            var htmlFields = '';
            console.log('Mapa de valores para renderizar (dentro de renderCustomFields):', valuesMap); // Depuración.

            // Itera sobre cada definición de campo personalizado.
            definitions.forEach(function(def) {
                // Obtiene el valor existente para este campo desde el 'valuesMap'.
                var fieldValue = valuesMap[def.id] !== undefined ? valuesMap[def.id] : '';
                var inputId = 'custom_field_' + def.id; // ID único para el input.
                var inputName = inputId; // Nombre del input para el envío del formulario.
                var isRequired = def.es_requerido == 1 ? 'required' : ''; // Atributo 'required' si el campo es obligatorio.
                
                // Construye la etiqueta del campo, incluyendo un asterisco si es requerido.
                var labelHtml = '<label for="' + inputId + '" class="form-label">' + def.nombre_campo + (isRequired ? ' <span class="text-danger">*</span>' : '') + '</label>';
                var fieldHtml = ''; // HTML del input del campo.
                
                // Campo oculto para indicar el tipo de dato booleano al backend.
                var hiddenTypeField = '';
                if (def.tipo_dato === 'booleano') {
                    hiddenTypeField = '<input type="hidden" name="custom_field_type_' + def.id + '" value="booleano">';
                }

                // Genera el HTML del input según el tipo de dato definido.
                switch (def.tipo_dato) {
                    case 'texto':
                        fieldHtml = '<input type="text" class="form-control" id="' + inputId + '" name="' + inputName + '" value="' + fieldValue + '" ' + isRequired + '>';
                        break;
                    case 'texto_largo':
                        fieldHtml = '<textarea class="form-control" id="' + inputId + '" name="' + inputName + '" rows="3" ' + isRequired + '>' + fieldValue + '</textarea>';
                        break;
                    case 'numero':
                        fieldHtml = '<input type="number" step="any" class="form-control" id="' + inputId + '" name="' + inputName + '" value="' + fieldValue + '" ' + isRequired + '>';
                        if (def.unidad) {
                            fieldHtml += '<small class="form-text text-muted">Unidad: ' + def.unidad + '</small>';
                        }
                        break;
                    case 'fecha':
                        fieldHtml = '<input type="date" class="form-control" id="' + inputId + '" name="' + inputName + '" value="' + fieldValue + '" ' + isRequired + '>';
                        break;
                    case 'booleano':
                        // Marca el checkbox como 'checked' si el valor es '1'.
                        var checked = (fieldValue === '1') ? 'checked' : ''; 
                        fieldHtml = '<div class="form-check form-switch">' +
                                    '<input type="checkbox" class="form-check-input" id="' + inputId + '" name="' + inputName + '" value="1" ' + checked + '>' +
                                    '<label class="form-check-label" for="' + inputId + '">' + def.nombre_campo + '</label>' +
                                    hiddenTypeField +
                                    '</div>';
                        labelHtml = ''; // La etiqueta ya está dentro del div del checkbox.
                        break;
                    case 'lista':
                        var optionsHtml = '<option value=""><?= $t('select_an_option') ?></option>'; // Opción por defecto traducida
                        if (def.opciones_lista) {
                            def.opciones_lista.split(',').forEach(function(option) {
                                option = option.trim();
                                var selected = (fieldValue == option) ? 'selected' : ''; // Pre-selecciona la opción si coincide.
                                optionsHtml += '<option value="' + option + '" ' + selected + '>' + option + '</option>';
                            });
                        }
                        fieldHtml = '<select class="form-select" id="' + inputId + '" name="' + inputName + '" ' + isRequired + '>' + optionsHtml + '</select>';
                        break;
                }
                // Añade el campo completo al HTML de los campos.
                htmlFields += '<div class="mb-3">' + labelHtml + fieldHtml + (def.descripcion ? '<small class="form-text text-muted">' + def.descripcion + '</small>' : '') + '</div>';
            });
            customFieldsContainer.html(htmlFields); // Inserta todos los campos generados en el contenedor.
        } else {
            // Si no hay definiciones de campos, muestra un mensaje informativo.
            customFieldsContainer.html('<div class="alert alert-info" role="alert">' + <?= json_encode($t('no_custom_fields_defined')) ?> + '</div>');
        }
    }


    // --- Ejecución inicial al cargar la página ---
    // Determina si el activo ya existe y tiene un tipo asignado (modo edición).
    var initialAssetTypeId = tipoActivoSelect.val(); // Usamos tipoActivoSelect que ya está definido
    if (initialAssetTypeId) { 
        // Si es edición, llama a la API para cargar las DEFINICIONES de campos personalizados.
        $.ajax({
            url: '/api/custom-fields/definitions/' + initialAssetTypeId, 
            method: 'GET',
            success: function(definitions) {
                // Para la carga inicial, usa initialCustomValuesMap (que se llena desde el data-attribute).
                renderCustomFields(definitions, initialCustomValuesMap); // Pasa las definiciones y el mapa de valores iniciales.
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar campos personalizados iniciales (AJAX):', error);
                $('#custom-fields-container').html('<div class="alert alert-danger" role="alert">' + <?= json_encode($t('error_loading_custom_fields')) ?> + '</div>');
            }
        });
    } else {
        // En modo creación, si no hay tipo seleccionado inicialmente, muestra el mensaje.
        $('#custom-fields-container').html('<div class="alert alert-info" role="alert">' + <?= json_encode($t('select_asset_type_for_custom_fields')) ?> + '</div>');
    }

    // --- Ejecutar la carga inicial de modelos por fabricante ---
    // Esto solo ocurre si ya hay un fabricante seleccionado al cargar la página (en modo edición).
    var initialFabricanteId = fabricanteSelect.val(); // Usamos fabricanteSelect que ya está definido
    if (initialFabricanteId) {
        fabricanteSelect.trigger('change'); // Dispara el evento change para cargar los modelos iniciales.
    }

    // --- Inicializar el estado del formulario al cargar la página ---
    initializeAssetFormState();
});