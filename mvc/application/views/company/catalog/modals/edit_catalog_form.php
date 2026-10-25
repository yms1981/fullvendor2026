<?php
/**
 * Modal para editar catálogo - Similar al modal de agregar
 * @var array $product Lista de productos
 * @var array $category Lista de categorías
 * @var array $lang_prods Textos de productos
 * @var array $lang_labels Textos de etiquetas
 * @var array $lang_optionbasic Textos básicos
 * @var int $catalog_id ID del catálogo a editar
 */
?>
<div class="modal fade" id="modalEditCatalog<?= $catalog_id ?>" tabindex="-1" role="dialog" aria-labelledby="modalEditCatalogLabel<?= $catalog_id ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="catalogEdit<?= $catalog_id ?>" novalidate class="forms-sample" action="<?= company_url('catalog/update') ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="catalog_id" value="<?= $catalog_id ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditCatalogLabel<?= $catalog_id ?>"><?= $lang_prods['edit_catalog'] ?? 'Editar Catálogo' ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row fv-catalog-edit-fields">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label><?= $lang_prods['catalog_name'] ?? 'Nombre del Catálogo' ?>: <span class="color-red">*</span></label>
                                <input type="text" class="form-control" name="catalog_name" id="edit_catalog_name<?= $catalog_id ?>" value="" required="">
                                <span class="error"></span>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div id="myselect2-edit<?= $catalog_id ?>" class="form-group">
                                <label><?= $lang_optionbasic['label_activo'] ?? 'Estado' ?>: </label>
                                <select class="form-control" name="status" id="edit_status<?= $catalog_id ?>" required>
                                    <option value="1"><?= $lang_optionbasic['activo'] ?? 'Activo' ?></option>
                                    <option value="0"><?= $lang_optionbasic['desactivo'] ?? 'Inactivo' ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-5">
                            <div class="form-group">
                                <label><?= $lang_prods['cabeza_pagina'] ?? 'Cabecera de Página' ?>:</label>
                                <input type="text" class="form-control" name="cabeza_pagina" id="edit_cabeza_pagina<?= $catalog_id ?>" value="">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div id="myselect2-edit<?= $catalog_id ?>" class="form-group">
                                <label><?= $lang_prods['product_name'] ?? 'Productos' ?>: </label>
                                <select class="form-control js-example-edit<?= $catalog_id ?>" name="products[]" multiple="multiple" style="width: 100%;" data-search-url="<?= company_url('products/searchProductSelect2') ?>" data-placeholder="<?= htmlspecialchars((string) ($lang_optionbasic['nothing_selected'] ?? $lang_prods['product_search_type_to_search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-msg-searching="<?= htmlspecialchars((string) ($lang_prods['product_search_searching'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-msg-no-results="<?= htmlspecialchars((string) ($lang_prods['product_search_no_results'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-msg-input-too-short="<?= htmlspecialchars((string) ($lang_prods['product_search_type_to_search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($product) && is_array($product)): ?>
                                        <?php foreach ($product as $prod): ?>
                                            <?php
                                            $row = is_array($prod) ? (object) $prod : $prod;
                                            $proId = (int) ($row->pro_id ?? $row->product_id ?? 0);
                                            $label = strtoupper(trim((string) ($row->sku ?? '') . ' | ' . (string) ($row->name ?? '')));
                                            if ($proId <= 0 || $label === '|' || $label === '') {
                                                continue;
                                            }
                                            ?>
                                            <option value="<?= $proId ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group">
                                <label><?= $lang_prods['categories'] ?? 'Categorías' ?>:</label>
                                <select id="kt_dual_listbox_edit<?= $catalog_id ?>" name="categories[]" class="dual-listbox" multiple="multiple" style="visibility: hidden;">
                                    <?php if (isset($category) && is_array($category)): ?>
                                        <?php foreach ($category as $categ): ?>
                                            <option value="<?= $categ->cat_id ?>">
                                                <?= $categ->category_name ? strtoupper($categ->category_name) : "&nbsp;" ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Ordenador de categorias (Select) - Similar al modal de agregar -->
                    <?php 
                    // Inicialmente vacío, se cargará con los datos del catálogo
                    $category_selected_order = [];
                    $this->load->view('company/catalog/ordenador_de_categorias/dualist-box-edit', [
                        'category_selected_order' => $category_selected_order, 
                        'category' => $category ?? [], 
                        'lang_prods' => $lang_prods ?? []
                    ]) 
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                        <?= $lang_labels['f_cancelar'] ?? 'Cancelar' ?>
                    </button>
                    <button type="submit" class="btn btn-success" id="btnSaveEdit<?= $catalog_id ?>">
                        <?= $lang_labels['save'] ?? 'Guardar' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
// Cargar el script de inicialización del formulario (similar al modal de agregar)
echo '<script>window.FV_CATALOG_SKIP_SELECT2_AUTOINIT = true;</script>';
$scriptContent = $this->load->view('company/catalog/script-add-edit', $lang_prods ?? [], true);
// Reemplazar el selector del dropdownParent para que funcione en el modal de edición
$scriptContent = str_replace('dropdownParent: $("#myselect2")', 'dropdownParent: $("#modalEditCatalog' . $catalog_id . '")', $scriptContent);
$scriptContent = str_replace('#modalAddCatalog', '#modalEditCatalog' . $catalog_id, $scriptContent);
$scriptContent = str_replace('.js-example', '.js-example-edit' . $catalog_id, $scriptContent);
$scriptContent = str_replace('#kt_dual_listbox_1', '#kt_dual_listbox_edit' . $catalog_id, $scriptContent);
echo $scriptContent;
?>
<script>
// Script específico para el modal de edición - Similar al modal de agregar
(function() {
    console.log('📜 Script del modal de edición cargado');
    var catalogId = <?= $catalog_id ?>;
    var modalId = '#modalEditCatalog' + catalogId;
    console.log('📋 Modal ID configurado:', modalId, '| Catalog ID:', catalogId);
    
    function waitForJQuery(callback, maxAttempts) {
        maxAttempts = maxAttempts || 100;
        var attempts = 0;
        
        function check() {
            attempts++;
            if (typeof jQuery !== 'undefined' && typeof jQuery === 'function') {
                if (typeof window.$ === 'undefined') {
                    window.$ = jQuery;
                }
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            } else {
                console.error('jQuery not loaded after ' + (maxAttempts * 100) + 'ms');
                callback();
            }
        }
        check();
    }
    
    waitForJQuery(function() {
        var $ = jQuery;
        
        // Función para esperar a que select2 esté disponible
        function waitForSelect2(callback, maxAttempts) {
            maxAttempts = maxAttempts || 50;
            var attempts = 0;
            
            function check() {
                attempts++;
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
                    callback();
                } else if (attempts < maxAttempts) {
                    setTimeout(check, 100);
                } else {
                    console.warn('select2 not loaded after ' + (maxAttempts * 100) + 'ms');
                    if (typeof jQuery !== 'undefined') {
                        callback();
                    }
                }
            }
            check();
        }
        
        // Variable para almacenar los datos del catálogo
        var catalogData = null;
        var defaultCatalogReorderHint = <?= json_encode(($pageTexts['catalogo_default_reorder_hint'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
        var defaultCatalogReorderTitle = <?= json_encode(($pageTexts['catalogo_default_reorder_title'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;

        function applyDefaultCatalogEditRestrictions(id, isDefault) {
            var targetModalId = '#modalEditCatalog' + id;
            var $modal = $(targetModalId);
            var $title = $('#modalEditCatalogLabel' + id);
            $modal.find('.fv-default-catalog-hint').remove();

            if (!$title.data('original-title')) {
                $title.data('original-title', $title.text());
            }

            if (!isDefault) {
                $modal.removeClass('fv-catalog-reorder-only');
                $title.text($title.data('original-title'));
                $modal.find('.fv-catalog-edit-fields').removeClass('d-none');
                $('#edit_catalog_name' + id).prop('readonly', false);
                $('#edit_status' + id).prop('disabled', false);
                $('#edit_cabeza_pagina' + id).prop('readonly', false);
                var $productSelect = $('.js-example-edit' + id);
                $productSelect.prop('disabled', false);
                if (typeof window.initCatalogProductsSelect2 === 'function') {
                    window.initCatalogProductsSelect2($productSelect, $(targetModalId), []);
                }
                $modal.find('#kt_dual_listbox_edit' + id).closest('.form-group').show();
                $modal.find('.fv-catalog-order-available, .fv-catalog-order-toolbar').removeClass('d-none');
                $modal.find('.fv-catalog-order-selected').removeClass('col-12').addClass('col-5');
                return;
            }

            $modal.addClass('fv-catalog-reorder-only');
            $title.text(defaultCatalogReorderTitle || $title.data('original-title'));

            if (defaultCatalogReorderHint) {
                $modal.find('.modal-body').prepend(
                    '<div class="alert alert-info fv-default-catalog-hint mb-3">' + defaultCatalogReorderHint + '</div>'
                );
            }

            $modal.find('.fv-catalog-edit-fields').addClass('d-none');
            $('#edit_catalog_name' + id).prop('readonly', true);
            $('#edit_status' + id).prop('disabled', true);
            $('#edit_cabeza_pagina' + id).prop('readonly', true);
            $('.js-example-edit' + id).prop('disabled', true).trigger('change.select2');
            $modal.find('.fv-catalog-order-available, .fv-catalog-order-toolbar').addClass('d-none');
            $modal.find('.fv-catalog-order-selected').removeClass('col-5').addClass('col-12');
            $modal.find('#example2Right .list-group-item').attr('no_removed', 'true');
        }
        
        // Función para cargar los datos del catálogo (global, funciona con cualquier ID)
        window.loadEditCatalogData = function(id) {
            console.log('🚀 === INICIANDO CARGA DE DATOS DEL CATÁLOGO ===');
            console.log('📌 ID del catálogo recibido:', id);
            
            // Determinar el modalId basado en el ID recibido
            var targetModalId = '#modalEditCatalog' + id;
            console.log('📌 Modal objetivo:', targetModalId);
            
            // Verificar que el modal existe
            if ($(targetModalId).length === 0) {
                console.error('❌ Modal no encontrado:', targetModalId);
                return;
            }
            
            var url = '<?= company_url("catalog/getEditData/") ?>' + id;
            console.log('🌐 URL de la petición:', url);
            console.log('📡 Iniciando petición AJAX...');
            
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('📥 Response received:', response);
                    if (response.success) {
                        catalogData = response;
                        var catalog = response.catalog;
                        window['isDefaultCatalogEdit_' + id] = parseInt(catalog.default_catalog, 10) === 1;
                        applyDefaultCatalogEditRestrictions(id, window['isDefaultCatalogEdit_' + id]);
                        
                        console.log('📦 Datos del catálogo cargados:', {
                            catalog_id: catalog.catalog_id,
                            catalog_name: catalog.catalog_name,
                            status: catalog.status
                        });
                        
                        // Llenar campos básicos usando el ID recibido
                        $('#edit_catalog_name' + id).val(catalog.catalog_name || '');
                        $('#edit_status' + id).val(catalog.status || '1');
                        $('#edit_cabeza_pagina' + id).val(catalog.cabeza_pagina || '');
                        
                        // Cargar productos seleccionados en select2
                        if (typeof window.initCatalogProductsSelect2 === 'function') {
                            window.initCatalogProductsSelect2(
                                $('.js-example-edit' + id),
                                $(targetModalId),
                                response.selectedProducts || []
                            );
                        } else if (response.selectedProducts && response.selectedProducts.length > 0) {
                            var productIds = response.selectedProducts.map(function(prodId) { return String(prodId); });
                            $('.js-example-edit' + id).val(productIds).trigger('change');
                        }
                        
                        // Cargar categorías seleccionadas en el ordenador desde catalog_category
                        console.log('🔍 === CARGANDO CATEGORÍAS DESDE TABLA catalog_category ===');
                        console.log('📊 Total de categorías encontradas en catalog_category:', response.category_selected_order ? response.category_selected_order.length : 0);

                        function renderOrderedCategoriesDirect(categories) {
                            var $rightContainer = $(targetModalId + ' #example2Right');
                            if (!$rightContainer.length || !categories || !categories.length) {
                                return false;
                            }
                            $rightContainer.empty();
                            categories.forEach(function(cat, index) {
                                var catId = String(cat.cat_id || cat.category_id);
                                var categoryName = (cat.category_name || ('CATEGORÍA ' + catId)).toString().toUpperCase();
                                var orderNumber = index + 1;
                                var itemHtml = '<div class="list-group-item fullvendor-cursor-pointer" value="' + catId + '" no_removed="true">' +
                                    '<div class="d-flex align-items-center">' +
                                    '<span class="css-order-item d-flex justify-content-center align-items-center animate__animated animate__zoomIn selected-category-to-catalog">' + orderNumber + '</span>' +
                                    '<span>&nbsp;' + $('<div>').text(categoryName).html() + '</span>' +
                                    '</div>' +
                                    '<input hidden type="text" name="categ_selected[]" value="' + catId + '">' +
                                    '</div>';
                                $rightContainer.append(itemHtml);
                            });
                            applyDefaultCatalogEditRestrictions(id, true);

                            var rightEl = $rightContainer.get(0);
                            if (rightEl && typeof Sortable !== 'undefined') {
                                if (rightEl.sortableInstance) {
                                    rightEl.sortableInstance.destroy();
                                    rightEl.sortableInstance = null;
                                }
                                rightEl.sortableInstance = new Sortable(rightEl, {
                                    animation: 150,
                                    onEnd: function() {
                                        $rightContainer.find('.list-group-item').each(function(index) {
                                            var $span = $(this).find('span.css-order-item');
                                            if ($span.length) {
                                                $span.text(index + 1);
                                            }
                                        });
                                    }
                                });
                            }
                            return true;
                        }
                        
                        if (response.category_selected_order && response.category_selected_order.length > 0) {
                            console.log('📋 Categorías obtenidas de catalog_category:', response.category_selected_order);

                            // Catálogo FullVendor: pintar todas las categorías directo en el panel derecho.
                            if (window['isDefaultCatalogEdit_' + id]) {
                                renderOrderedCategoriesDirect(response.category_selected_order);
                                return;
                            }
                            
                            // Mostrar detalle de cada categoría desde catalog_category
                            console.log('📝 Detalle de categorías desde catalog_category:');
                            response.category_selected_order.forEach(function(cat, index) {
                                console.log('  📌 Categoría ' + (index + 1) + ' (desde catalog_category):', {
                                    catalog_id: cat.catalog_id,
                                    category_id: cat.category_id || cat.cat_id,
                                    category_name: cat.category_name,
                                    order: cat.order,
                                    qty_products: cat.qty_products || 0,
                                    source: 'catalog_category'
                                });
                            });
                            
                            console.log('✅ === FIN CATEGORÍAS DESDE catalog_category ===');
                            
                            // Guardar las categorías para moverlas después de que el selector esté inicializado
                            // Usar el ID recibido, no el catalogId del modal
                            window['catalogCategoriesToLoad_' + id] = response.category_selected_order;
                            
                            // Función global para mover las categorías al recuadro derecho (disponible para cualquier modal)
                            window['moveCategoriesToRight_' + id] = function() {
                                var categoriesToLoad = window['catalogCategoriesToLoad_' + id];
                                var targetModalId = '#modalEditCatalog' + id;
                                if (!categoriesToLoad || categoriesToLoad.length === 0) {
                                    console.log('ℹ️ No hay categorías guardadas para cargar');
                                    return false;
                                }
                                
                                console.log('🎯 Moviendo categorías al recuadro derecho del modal:', targetModalId);
                                var $leftContainer = $(targetModalId + ' #example2Left');
                                var $rightContainer = $(targetModalId + ' #example2Right');
                                
                                console.log('🔍 Buscando contenedores:', {
                                    left: $leftContainer.length,
                                    right: $rightContainer.length,
                                    leftItems: $leftContainer.find('.list-group-item').length,
                                    rightItems: $rightContainer.find('.list-group-item').length
                                });
                                
                                if (!$leftContainer.length || !$rightContainer.length) {
                                    console.error('❌ Contenedores no encontrados en modal:', targetModalId);
                                    return false;
                                }
                                
                                var leftItemsCount = $leftContainer.find('.list-group-item').length;
                                if (leftItemsCount === 0) {
                                    console.warn('⚠️ No hay categorías en el contenedor izquierdo aún');
                                    return false;
                                }
                                
                                // Limpiar el contenedor derecho primero
                                $rightContainer.empty();
                                
                                var movedCount = 0;
                                
                                // Mover las categorías seleccionadas al contenedor derecho en el orden correcto
                                categoriesToLoad.forEach(function(cat, index) {
                                    var catId = String(cat.cat_id || cat.category_id);
                                    var catName = cat.category_name || '';
                                    
                                    console.log('🔎 Buscando categoría ID:', catId, 'Nombre:', catName);
                                    
                                    // Buscar en el contenedor izquierdo - intentar múltiples métodos
                                    var $item = null;
                                    
                                    // Método 1: Buscar por atributo value del div (estructura del dual-listbox)
                                    $item = $leftContainer.find('.list-group-item[value="' + catId + '"]');
                                    
                                    // Método 2: Buscar por input hidden con value exacto
                                    if ($item.length === 0) {
                                        $item = $leftContainer.find('.list-group-item input[type="hidden"][value="' + catId + '"]').closest('.list-group-item');
                                    }
                                    
                                    // Método 3: Buscar por cualquier input con ese valor (más flexible)
                                    if ($item.length === 0) {
                                        $leftContainer.find('.list-group-item').each(function() {
                                            var $input = $(this).find('input');
                                            if ($input.length > 0) {
                                                var inputValue = String($input.val() || $input.attr('value') || '');
                                                if (inputValue === catId || inputValue.trim() === catId) {
                                                    $item = $(this);
                                                    return false; // break
                                                }
                                            }
                                        });
                                    }
                                    
                                    // Método 4: Buscar por texto que contenga el nombre de la categoría (último recurso)
                                    if ($item.length === 0 && catName) {
                                        var searchName = catName.trim().toLowerCase();
                                        $leftContainer.find('.list-group-item').each(function() {
                                            var itemText = $(this).text().toLowerCase().trim();
                                            if (itemText.indexOf(searchName) !== -1 || searchName.indexOf(itemText) !== -1) {
                                                var $input = $(this).find('input');
                                                if ($input.length > 0) {
                                                    var inputValue = String($input.val() || $input.attr('value') || '');
                                                    // Verificar que el ID coincida aproximadamente
                                                    if (inputValue === catId || inputValue.trim() === catId) {
                                                        $item = $(this);
                                                        return false; // break
                                                    }
                                                }
                                            }
                                        });
                                    }
                                    
                                    console.log('🔍 Resultado búsqueda:', $item.length > 0 ? '✅ Encontrada' : '❌ No encontrada');
                                    
                                    if ($item.length > 0) {
                                        console.log('✅ Categoría encontrada en izquierdo, moviendo:', catName);
                                        
                                        // Clonar el item para evitar problemas de DOM
                                        var $clonedItem = $item.clone(true, true);
                                        
                                        var $input = $clonedItem.find('input');
                                        var $span = $clonedItem.find('span.css-order-item');
                                        
                                        // Configurar el input
                                        $input.attr('name', 'categ_selected[]');
                                        $input.val(catId);
                                        
                                        // Mostrar y actualizar el número de orden
                                        if ($span.length) {
                                            $span.removeClass('hidden-important');
                                            $span.text(index + 1);
                                        } else {
                                            // Si no existe el span, crearlo
                                            var $dFlex = $clonedItem.find('.d-flex');
                                            if ($dFlex.length) {
                                                $dFlex.prepend(
                                                    '<span class="css-order-item d-flex justify-content-center align-items-center animate__animated animate__zoomIn selected-category-to-catalog">' + (index + 1) + '</span>'
                                                );
                                            }
                                        }
                                        
                                        // Agregar atributo data-movable si no tiene productos
                                        if (cat.qty_products === undefined || cat.qty_products == null || cat.qty_products < 1) {
                                            $clonedItem.attr('data-movable', 'true');
                                        }
                                        
                                        // Remover clase de selección activa
                                        $clonedItem.removeClass('active-selected-category');
                                        
                                        // Agregar al contenedor derecho
                                        $rightContainer.append($clonedItem);
                                        
                                        // Remover el original del contenedor izquierdo
                                        $item.remove();
                                        
                                        movedCount++;
                                    } else {
                                        console.warn('⚠️ Categoría no encontrada en izquierdo, creando directamente en derecho:', catId, catName);
                                        
                                        // Si no se encuentra en el izquierdo, crear el item directamente en el derecho
                                        var orderNumber = index + 1;
                                        var categoryName = catName ? catName.toUpperCase() : 'CATEGORÍA ' + catId;
                                        
                                        // Crear el HTML del item de categoría
                                        var itemHtml = '<div class="list-group-item fullvendor-cursor-pointer" value="' + catId + '" ' +
                                            (cat.qty_products === undefined || cat.qty_products == null || cat.qty_products < 1 ? 'data-movable="true"' : '') + '>' +
                                            '<div class="d-flex align-items-center">' +
                                            '<span class="css-order-item d-flex justify-content-center align-items-center animate__animated animate__zoomIn selected-category-to-catalog">' + orderNumber + '</span>' +
                                            '<span>&nbsp;' + categoryName + '</span>' +
                                            '</div>' +
                                            '<input hidden type="text" name="categ_selected[]" value="' + catId + '">' +
                                            '</div>';
                                        
                                        // Agregar al contenedor derecho
                                        $rightContainer.append(itemHtml);
                                        
                                        console.log('✅ Categoría creada directamente en recuadro derecho:', categoryName);
                                        movedCount++;
                                    }
                                });
                                
                                // Actualizar el orden de todas las categorías en el contenedor derecho
                                $rightContainer.find('.list-group-item').each(function(index) {
                                    var $span = $(this).find('span.css-order-item');
                                    if ($span.length) {
                                        $span.text(index + 1);
                                    }
                                });
                                
                                console.log('✅ Categorías movidas. Total en contenedor derecho:', $rightContainer.find('.list-group-item').length, '| Movidas:', movedCount);

                                if (window['isDefaultCatalogEdit_' + id]) {
                                    applyDefaultCatalogEditRestrictions(id, true);
                                }
                                
                                // Limpiar la variable después de mover
                                delete window['catalogCategoriesToLoad_' + id];
                                
                                return movedCount > 0;
                            };
                            
                            // Intentar mover las categorías con varios intentos
                            var attempts = 0;
                            var maxAttempts = 20;
                            
                            function tryMoveCategories() {
                                attempts++;
                                var targetModalId = '#modalEditCatalog' + id;
                                var $leftContainer = $(targetModalId + ' #example2Left');
                                var $rightContainer = $(targetModalId + ' #example2Right');
                                var leftItemsCount = $leftContainer.length ? $leftContainer.find('.list-group-item').length : 0;
                                
                                console.log('🔄 Intento ' + attempts + ' - Contenedores:', {
                                    left: $leftContainer.length,
                                    right: $rightContainer.length,
                                    leftItems: leftItemsCount
                                });
                                
                                if ($leftContainer.length && $rightContainer.length) {
                                    // Esperar a que haya items en el contenedor izquierdo
                                    if (leftItemsCount > 0) {
                                        console.log('✅ Contenedores listos con ' + leftItemsCount + ' categorías, moviendo categorías (intento ' + attempts + ')');
                                        // Usar la función global
                                        var moveFunction = window['moveCategoriesToRight_' + id];
                                        if (moveFunction && typeof moveFunction === 'function') {
                                            var success = moveFunction();
                                            if (success) {
                                                console.log('✅ Categorías movidas exitosamente');
                                                // Limpiar la función después de usarla
                                                delete window['moveCategoriesToRight_' + id];
                                                return; // Éxito
                                            }
                                        }
                                    }
                                }
                                
                                if (attempts < maxAttempts) {
                                    console.log('⏳ Esperando categorías en contenedor izquierdo... (intento ' + attempts + ')');
                                    setTimeout(tryMoveCategories, 500);
                                } else {
                                    console.error('❌ No se pudieron encontrar las categorías después de ' + maxAttempts + ' intentos');
                                    // Limpiar las variables si no se pudo mover
                                    delete window['catalogCategoriesToLoad_' + id];
                                    delete window['moveCategoriesToRight_' + id];
                                }
                            }
                            
                            // Iniciar después de un delay más largo para asegurar que el selector esté completamente inicializado
                            setTimeout(tryMoveCategories, 1500);
                        } else {
                            console.log('ℹ️ No hay categorías seleccionadas para cargar desde catalog_category');
                        }
                    } else {
                        console.error('Error in response:', response);
                        alert(response.msg || 'Error al cargar los datos del catálogo');
                        $(modalId).modal('hide');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ === ERROR EN PETICIÓN AJAX ===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    console.error('Status Code:', xhr.status);
                    console.error('URL:', url);
                    var msg = <?= json_encode($pageTexts['not_found'] ?? 'Error al cargar los datos del catálogo', JSON_UNESCAPED_UNICODE) ?>;
                    if (xhr.responseText) {
                        try {
                            var parsed = JSON.parse(xhr.responseText);
                            if (parsed && parsed.msg) {
                                msg = parsed.msg;
                            }
                        } catch (e) { /* respuesta no JSON */ }
                    }
                    if (typeof alert_func !== 'undefined') {
                        alert_func([msg, 'error']);
                    } else {
                        alert(msg);
                    }
                    var targetModalId = '#modalEditCatalog' + id;
                    $(targetModalId).modal('hide');
                },
                complete: function() {
                    console.log('✅ Petición AJAX completada (éxito o error)');
                }
            });
        };
        
        // Antes de mostrar el modal, rellenar el nombre desde el botón (si existe)
        $(modalId).on('show.bs.modal', function (event) {
            console.log('🔓 Modal de edición se está abriendo:', modalId);
            var button = $(event.relatedTarget); // Botón que abrió el modal
            console.log('🔘 Botón que abrió el modal:', button);
            if (button && button.length) {
                var nameFromButton = button.data('catalog-name');
                console.log('📝 Nombre del catálogo desde el botón:', nameFromButton);
                if (nameFromButton) {
                    $('#edit_catalog_name' + catalogId).val(nameFromButton);
                }
            }
        });

        // Inicializar cuando el modal se muestre (similar al modal de agregar)
        $(modalId).on('shown.bs.modal', function() {
            console.log('✅ Modal de edición completamente abierto:', modalId);
            console.log('🔄 Llamando a loadEditCatalogData con catalogId:', catalogId);
            // PRIMERO: Cargar los datos del catálogo desde la API
            loadEditCatalogData(catalogId);
            
            // TERCERO: Cargar SortableJS y luego inicializar el selector
            function loadSortableJS(callback) {
                if (typeof Sortable !== 'undefined') {
                    if (callback) callback();
                    return;
                }
                
                var script = document.createElement('script');
                script.src = '<?= base_url("companyasset/js/sortable.min.js") ?>';
                script.onload = callback;
                script.onerror = function() {
                    console.error('Error al cargar SortableJS');
                };
                document.head.appendChild(script);
            }
            
            loadSortableJS(function() {
                if (typeof Sortable === 'undefined') {
                    console.error('Sortable no está disponible después de cargar el script');
                    return;
                }
                
                // Dar un delay para asegurar que el DOM y los datos estén listos
                setTimeout(function() {
                    initCategorySelectorEdit();
                    
                    // Después de inicializar el selector, intentar mover las categorías si hay datos guardados
                    // Buscar cualquier ID que tenga categorías guardadas
                    var foundId = null;
                    for (var key in window) {
                        if (key.startsWith('catalogCategoriesToLoad_')) {
                            var storedId = key.replace('catalogCategoriesToLoad_', '');
                            var targetModalId = '#modalEditCatalog' + storedId;
                            if ($(targetModalId).length > 0) {
                                foundId = storedId;
                                break;
                            }
                        }
                    }
                    
                    if (foundId && window['catalogCategoriesToLoad_' + foundId]) {
                        setTimeout(function() {
                            var targetModalId = '#modalEditCatalog' + foundId;
                            var $leftContainer = $(targetModalId + ' #example2Left');
                            var $rightContainer = $(targetModalId + ' #example2Right');
                            var leftItemsCount = $leftContainer.length ? $leftContainer.find('.list-group-item').length : 0;
                            
                            console.log('🔄 Intentando mover categorías después de inicializar selector para modal:', targetModalId);
                            if (leftItemsCount > 0) {
                                // Usar la función global si está disponible, sino usar la lógica inline
                                var moveFunction = window['moveCategoriesToRight_' + foundId];
                                if (moveFunction && typeof moveFunction === 'function') {
                                    console.log('✅ Usando función global moveCategoriesToRight_' + foundId);
                                    var success = moveFunction();
                                    if (success) {
                                        console.log('✅ Categorías movidas exitosamente usando función global');
                                        delete window['moveCategoriesToRight_' + foundId];
                                    }
                                } else {
                                    // Fallback: usar lógica inline si la función global no está disponible
                                    console.log('⚠️ Función global no disponible, usando lógica inline');
                                    var categoriesToLoad = window['catalogCategoriesToLoad_' + foundId];
                                    if (categoriesToLoad && categoriesToLoad.length > 0) {
                                        $rightContainer.empty();
                                        var movedCount = 0;
                                        
                                        categoriesToLoad.forEach(function(cat, index) {
                                            var catId = String(cat.cat_id || cat.category_id);
                                            var $item = $leftContainer.find('.list-group-item input[type="hidden"][value="' + catId + '"]').closest('.list-group-item');
                                            
                                            if ($item.length === 0) {
                                                $item = $leftContainer.find('.list-group-item').filter(function() {
                                                    var $input = $(this).find('input');
                                                    return $input.length > 0 && String($input.val()) === catId;
                                                }).first();
                                            }
                                            
                                            if ($item.length > 0) {
                                                var $clonedItem = $item.clone(true, true);
                                                var $input = $clonedItem.find('input');
                                                var $span = $clonedItem.find('span.css-order-item');
                                                
                                                $input.attr('name', 'categ_selected[]');
                                                $input.val(catId);
                                                
                                                if ($span.length) {
                                                    $span.removeClass('hidden-important');
                                                    $span.text(index + 1);
                                                } else {
                                                    var $dFlex = $clonedItem.find('.d-flex');
                                                    if ($dFlex.length) {
                                                        $dFlex.prepend('<span class="css-order-item d-flex justify-content-center align-items-center animate__animated animate__zoomIn selected-category-to-catalog">' + (index + 1) + '</span>');
                                                    }
                                                }
                                                
                                                if (cat.qty_products === undefined || cat.qty_products == null || cat.qty_products < 1) {
                                                    $clonedItem.attr('data-movable', 'true');
                                                }
                                                
                                                $clonedItem.removeClass('active-selected-category');
                                                $rightContainer.append($clonedItem);
                                                $item.remove();
                                                movedCount++;
                                            }
                                        });
                                        
                                        $rightContainer.find('.list-group-item').each(function(index) {
                                            var $span = $(this).find('span.css-order-item');
                                            if ($span.length) {
                                                $span.text(index + 1);
                                            }
                                        });
                                        
                                        console.log('✅ Categorías movidas después de inicializar selector:', movedCount);
                                    }
                                }
                                // Limpiar la variable después de mover
                                delete window['catalogCategoriesToLoad_' + foundId];
                            }
                        }, 800);
                    } else {
                        console.log('ℹ️ No se encontraron categorías guardadas para mover');
                    }
                }, 600);
            });
            
            // Función para inicializar el selector de categorías en el modal de edición (similar al de agregar)
            function initCategorySelectorEdit() {
                console.log('=== INICIANDO initCategorySelectorEdit para modal ' + catalogId + ' ===');
                
                // Verificar que los elementos existan dentro del modal
                var modal = document.querySelector(modalId);
                var leftContainer = modal ? modal.querySelector('#example2Left') : document.querySelector(modalId + ' #example2Left');
                var rightContainer = modal ? modal.querySelector('#example2Right') : document.querySelector(modalId + ' #example2Right');
                var all_itemscategories = document.querySelectorAll(modalId + ' .fullvendor-select-order-categories .list-group-item');
                var btn_addall = modal ? modal.querySelector('#btn_addall') : document.querySelector(modalId + ' #btn_addall');
                var btn_addone = modal ? modal.querySelector('#btn_addone') : document.querySelector(modalId + ' #btn_addone');
                var btn_removeone = modal ? modal.querySelector('#btn_removeone') : document.querySelector(modalId + ' #btn_removeone');
                var btn_removeall = modal ? modal.querySelector('#btn_removeall') : document.querySelector(modalId + ' #btn_removeall');
                
                console.log('Elementos encontrados:', {
                    leftContainer: !!leftContainer,
                    rightContainer: !!rightContainer,
                    itemsCount: all_itemscategories.length,
                    btn_addall: !!btn_addall,
                    btn_addone: !!btn_addone,
                    btn_removeone: !!btn_removeone,
                    btn_removeall: !!btn_removeall
                });
                
                if (!leftContainer || !rightContainer || !btn_addall || !btn_addone || !btn_removeone || !btn_removeall) {
                    console.error('❌ Elementos del selector de categorías no encontrados en el modal de edición');
                    return false;
                }
                
                if (all_itemscategories.length === 0) {
                    console.warn('Category selector (edit): no category items yet');
                    return true;
                }
                
                console.log('✅ Todos los elementos encontrados correctamente en modal de edición');
                
                // Variables para este modal
                var container_working = null;
                var container_past = null;
                var list_itemsfocus = [];
                
                // Funciones auxiliares
                var get_Containerselecteditems = function() { return document.querySelector(modalId + ' #example2Right'); };
                var get_Containernotselecteditems = function() { return document.querySelector(modalId + ' #example2Left'); };
                var get_selecteditems = function() { return document.querySelectorAll(modalId + ' #example2Right .list-group-item'); };
                var get_notselecteditems = function() { return document.querySelectorAll(modalId + ' #example2Left .list-group-item'); };
                
                var order_categories = function(elements) {
                    elements.forEach(function(el, index) {
                        index++;
                        var span = el.querySelector('span.css-order-item');
                        if (span) span.innerText = index;
                    });
                };
                
                var update_ItemsnotSelected = function() {
                    var listGroupItems = document.querySelectorAll(modalId + ' #example2Left .list-group-item');
                    Array.from(listGroupItems).forEach(function(el) {
                        var spanElement = el.querySelector('span.css-order-item');
                        if (spanElement && !spanElement.classList.contains('hidden-important')) {
                            spanElement.classList.add('hidden-important');
                            var itemEl = el.querySelector('input');
                            if (itemEl) itemEl.removeAttribute('name');
                        }
                    });
                };
                
                var addall_or_addone = function(elements) {
                    var fragment = document.createDocumentFragment();
                    elements.forEach(function(el) {
                        var itemEl = el.querySelector('input');
                        var span_order = el.querySelector('span.css-order-item');
                        if (span_order) span_order.classList.remove('hidden-important');
                        el.classList.remove('active-selected-category');
                        if (itemEl) itemEl.setAttribute('name', 'categ_selected[]');
                        fragment.appendChild(el);
                    });
                    rightContainer.appendChild(fragment);
                    order_categories(get_selecteditems());
                };
                
                var removeall_or_addone = function(elements, forceRemove) {
                    // Si forceRemove no está definido, usar false por defecto
                    forceRemove = forceRemove === undefined ? false : forceRemove;
                    
                    var fragment = document.createDocumentFragment();
                    var movedCount = 0;
                    elements.forEach(function(el) {
                        // Si forceRemove es true, mover todos los elementos sin verificar data-movable
                        // Si no, solo mover los que tienen data-movable="true" (para drag & drop)
                        var canMove = forceRemove === true || (el.dataset.movable === "true" || el.dataset.movable === true);
                        
                        if (canMove) {
                            var itemEl = el.querySelector('input');
                            var span_order = el.querySelector('span.css-order-item');
                            
                            // Ocultar el número de orden
                            if (span_order) {
                                span_order.classList.add('hidden-important');
                            }
                            
                            // Remover clase de selección activa
                            el.classList.remove('active-selected-category');
                            
                            // Remover el atributo name del input para que no se envíe en el formulario
                            if (itemEl) {
                                itemEl.removeAttribute('name');
                            }
                            
                            fragment.appendChild(el);
                            movedCount++;
                        }
                    });
                    
                    if (movedCount > 0) {
                        leftContainer.appendChild(fragment);
                        order_categories(get_selecteditems());
                        console.log('✅ ' + movedCount + ' categoría(s) removida(s) correctamente');
                    } else {
                        console.warn('⚠️ No se pudieron remover categorías (pueden tener productos asociados)');
                    }
                };
                
                var delete_fromlist = function(item) {
                    var index = list_itemsfocus.indexOf(item);
                    if (index !== -1) {
                        list_itemsfocus.splice(index, 1);
                    }
                };
                
                var add_tolist = function(item) {
                    if (!list_itemsfocus.includes(item)) {
                        list_itemsfocus.push(item);
                    }
                };
                
                // Inicializar Sortable si no está inicializado
                var isDefaultCatalogEdit = window['isDefaultCatalogEdit_' + catalogId] === true;
                if (leftContainer.sortableInstance) {
                    leftContainer.sortableInstance.destroy();
                    leftContainer.sortableInstance = null;
                }
                if (rightContainer.sortableInstance) {
                    rightContainer.sortableInstance.destroy();
                    rightContainer.sortableInstance = null;
                }

                if (isDefaultCatalogEdit) {
                    rightContainer.sortableInstance = new Sortable(rightContainer, {
                        animation: 150,
                        onEnd: function() {
                            order_categories(get_selecteditems());
                        },
                    });
                } else if (!leftContainer.sortableInstance) {
                    leftContainer.sortableInstance = new Sortable(leftContainer, {
                        group: 'shared',
                        animation: 150,
                        onEnd: function(evt) {
                            var itemEl = evt.item.querySelector('input');
                            if (!(evt.to === leftContainer) && itemEl && !itemEl.hasAttribute('name')) {
                                var span_order = evt.item.querySelector('span.css-order-item');
                                if (span_order) span_order.classList.remove('hidden-important');
                                itemEl.setAttribute('name', 'categ_selected[]');
                                var categories_selected = evt.to.querySelectorAll('.list-group-item');
                                order_categories(categories_selected);
                            }
                        },
                    });
                }

                if (!isDefaultCatalogEdit && !rightContainer.sortableInstance) {
                    rightContainer.sortableInstance = new Sortable(rightContainer, {
                        group: {
                            name: 'shared',
                            pull: function(to, from, dragEl) {
                                return dragEl.dataset.movable === "true";
                            },
                        },
                        animation: 150,
                        onEnd: function(evt) {
                            var itemEl = evt.item.querySelector('input');
                            if (!(evt.to === rightContainer) && itemEl && itemEl.hasAttribute('name')) {
                                var span_order = evt.item.querySelector('span.css-order-item');
                                if (span_order) span_order.classList.add('hidden-important');
                                itemEl.removeAttribute('name');
                            }
                            var categories_selected = evt.from.querySelectorAll('.list-group-item');
                            order_categories(categories_selected);
                            update_ItemsnotSelected();
                        },
                    });
                }
                
                // Limpiar event listeners anteriores usando namespaces
                $(modalId).off('click.categorySelectorEdit', '#btn_addall');
                $(modalId).off('click.categorySelectorEdit', '#btn_addone');
                $(modalId).off('click.categorySelectorEdit', '#btn_removeone');
                $(modalId).off('click.categorySelectorEdit', '#btn_removeall');
                
                // Agregar eventos a los botones usando event delegation desde el modal
                $(modalId).on('click.categorySelectorEdit', '#btn_addall', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón Add All clickeado (edición)');
                    var categories_notselected = document.querySelectorAll(modalId + ' #example2Left .list-group-item');
                    console.log('Categorías no seleccionadas encontradas:', categories_notselected.length);
                    addall_or_addone(Array.from(categories_notselected));
                });
                
                $(modalId).on('click.categorySelectorEdit', '#btn_addone', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón Add One clickeado (edición), items seleccionados:', list_itemsfocus.length);
                    if (list_itemsfocus.length > 0) {
                        btn_addone.disabled = true;
                        btn_removeone.disabled = true;
                        addall_or_addone(list_itemsfocus);
                        list_itemsfocus.length = 0; // Limpiar array
                    }
                });
                
                $(modalId).on('click.categorySelectorEdit', '#btn_removeone', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón Remove One clickeado (edición), items seleccionados:', list_itemsfocus.length);
                    if (list_itemsfocus.length > 0) {
                        // Pasar true como segundo parámetro para forzar la remoción
                        removeall_or_addone(list_itemsfocus, true);
                        update_ItemsnotSelected();
                        list_itemsfocus.length = 0; // Limpiar array
                        btn_addone.disabled = true;
                        btn_removeone.disabled = true;
                    } else {
                        console.warn('⚠️ No hay items seleccionados para remover');
                    }
                });
                
                $(modalId).on('click.categorySelectorEdit', '#btn_removeall', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón Remove All clickeado (edición)');
                    var categories_selected = document.querySelectorAll(modalId + ' #example2Right .list-group-item');
                    console.log('Categorías seleccionadas encontradas:', categories_selected.length);
                    // Pasar true como segundo parámetro para forzar la remoción de todas
                    removeall_or_addone(Array.from(categories_selected), true);
                    update_ItemsnotSelected();
                });
                
                // Agregar eventos a los items usando event delegation desde el modal
                console.log('🔧 Configurando event listeners para items de categorías (edición)...');
                $(modalId).off('click.modalCategoryEdit', '.fullvendor-select-order-categories .list-group-item');
                $(modalId).on('click.modalCategoryEdit', '.fullvendor-select-order-categories .list-group-item', function(e) {
                    console.log('🖱️ CLICK DETECTADO en categoría (edición)');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var item = this;
                    console.log('📦 Item clickeado:', item);
                    console.log('📦 Item text:', item.textContent);
                    console.log('📦 Item parent:', item.parentElement);
                    console.log('📦 Item parent id:', item.parentElement ? item.parentElement.id : 'NO ID');
                    
                    // Limpiar todas las selecciones previas dentro del modal
                    var all_items = document.querySelectorAll(modalId + ' .fullvendor-select-order-categories .list-group-item.active-selected-category');
                    console.log('🧹 Limpiando selecciones previas:', all_items.length);
                    all_items.forEach(function(prevItem) {
                        prevItem.classList.remove('active-selected-category');
                        delete_fromlist(prevItem);
                    });
                    
                    container_past = container_working;
                    container_working = item.parentElement ? item.parentElement.id : null;
                    console.log('📂 Contenedor anterior:', container_past);
                    console.log('📂 Contenedor actual:', container_working);
                    
                    // Si cambió de contenedor, limpiar selecciones del anterior
                    if (container_past && container_past != container_working) {
                        var items = document.querySelectorAll(modalId + ' #' + container_past + ' .list-group-item.active-selected-category');
                        console.log('🔄 Cambió de contenedor, limpiando:', items.length, 'items');
                        items.forEach(function(prevItem) {
                            prevItem.classList.remove('active-selected-category');
                            delete_fromlist(prevItem);
                        });
                    }
                    
                    if (!item.getAttribute('no_removed')) {
                        if (container_working == 'example2Left') {
                            console.log('✅ Habilitando botón agregar uno (edición)');
                            btn_addone.disabled = false;
                            btn_removeone.disabled = true;
                        } else if (container_working == 'example2Right') {
                            console.log('✅ Habilitando botón remover uno (edición)');
                            btn_addone.disabled = true;
                            btn_removeone.disabled = false;
                        }
                    }
                    
                    // Toggle selección
                    var hasClass = item.classList.contains('active-selected-category');
                    console.log('🎯 Tiene clase active-selected-category?', hasClass);
                    
                    if (!hasClass) {
                        item.classList.add('active-selected-category');
                        add_tolist(item);
                        console.log('✅ Categoría SELECCIONADA (edición), total seleccionadas:', list_itemsfocus.length);
                        console.log('✅ Clases del item después de seleccionar:', item.className);
                    } else {
                        item.classList.remove('active-selected-category');
                        delete_fromlist(item);
                        console.log('❌ Categoría DESELECCIONADA (edición), total seleccionadas:', list_itemsfocus.length);
                        console.log('❌ Clases del item después de deseleccionar:', item.className);
                        
                        // Si no hay items seleccionados, deshabilitar botones
                        if (list_itemsfocus.length === 0) {
                            btn_addone.disabled = true;
                            btn_removeone.disabled = true;
                        }
                    }
                });
                
                console.log('✅ Event listeners configurados correctamente para modal de edición');
            }
        });
        
        // Limpiar cuando el modal se oculte
        $(modalId).on('hidden.bs.modal', function() {
            $('.fullvendor-select-order-categories .list-group-item').removeClass('active-selected-category');
            var $modal = $(modalId);
            $modal.removeClass('fv-catalog-reorder-only');
            $modal.find('.fv-default-catalog-hint').remove();
            $modal.find('.fv-catalog-edit-fields, .fv-catalog-order-available, .fv-catalog-order-toolbar').removeClass('d-none');
            $modal.find('.fv-catalog-order-selected').removeClass('col-12').addClass('col-5');
        });
        
        // Manejar el envío del formulario
        $('#catalogEdit' + catalogId).on('submit', function(e) {
            e.preventDefault();
            console.log('📤 Enviando formulario de edición...');

            // Asegurar nombre en catálogo FullVendor (campos ocultos/deshabilitados).
            if (window['isDefaultCatalogEdit_' + catalogId]) {
                var $nameInput = $('#edit_catalog_name' + catalogId);
                if (!$nameInput.val()) {
                    $nameInput.val('FullVendor Catalog');
                }
                $('#edit_status' + catalogId).prop('disabled', false);
            }
            
            const button = e.submitter || $(this).find('button[type="submit"]')[0];
            var textBefore = button ? button.innerHTML : '';
            if (button) {
                button.disabled = true;
                button.setAttribute('data-original-text', textBefore);
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + textBefore;
                
                setTimeout(function() {
                    button.disabled = false;
                    button.innerHTML = textBefore;
                }, 10000);
            }
            
            const datos = new FormData(this);
            
            // Log para depuración
            console.log('📋 Datos del formulario:');
            for (let pair of datos.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            console.log('📤 URL de acción:', $(this).attr('action'));
            
            fetch($(this).attr('action'), {
                method: 'POST',
                body: datos,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('📥 Respuesta recibida:', response.status, response.statusText);
                
                // Leer el texto de la respuesta primero para ver qué contiene
                return response.text().then(text => {
                    console.log('📄 Contenido de la respuesta:', text);
                    
                    if (!response.ok) {
                        // Intentar parsear como JSON para obtener el mensaje de error
                        try {
                            var errorData = JSON.parse(text);
                            throw new Error(errorData.msg || 'Error del servidor: ' + response.status);
                        } catch (e) {
                            throw new Error('Error del servidor: ' + response.status + ' - ' + text.substring(0, 200));
                        }
                    }
                    
                    // Intentar parsear como JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error al parsear JSON:', e);
                        console.error('Texto recibido:', text);
                        throw new Error('Respuesta no válida del servidor. Ver consola para más detalles.');
                    }
                });
            })
            .then(data => {
                console.log('✅ Datos recibidos:', data);
                
                if (button) {
                    button.disabled = false;
                    button.innerHTML = button.getAttribute('data-original-text') || '<?= $lang_labels['save'] ?? 'Guardar' ?>';
                }
                
                if (!data.success) {
                    var errMsg = data.msg || <?= json_encode($pageTexts['update_error'] ?? 'Error al actualizar el catálogo', JSON_UNESCAPED_UNICODE) ?>;
                    if (typeof window.alert_func === 'function') {
                        window.alert_func([errMsg, 'error']);
                    } else if (typeof swal !== 'undefined') {
                        swal({ title: errMsg, icon: 'error' });
                    }
                    return;
                }
                
                // Éxito: el mensaje se muestra vía set_flash en la siguiente página.
                $(modalId).modal('hide');
                if (data.urlredirect) {
                    window.location.href = data.urlredirect;
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('❌ Error al enviar formulario:', error);
                if (button) {
                    button.disabled = false;
                    button.innerHTML = button.getAttribute('data-original-text') || textBefore;
                }
                var errorMsg = error.message || <?= json_encode($pageTexts['update_error'] ?? 'Error al actualizar el catálogo', JSON_UNESCAPED_UNICODE) ?>;
                if (typeof window.alert_func === 'function') {
                    window.alert_func([errorMsg, 'error']);
                } else if (typeof swal !== 'undefined') {
                    swal({ title: errorMsg, icon: 'error' });
                }
            });
        });
    });
})();
</script>
