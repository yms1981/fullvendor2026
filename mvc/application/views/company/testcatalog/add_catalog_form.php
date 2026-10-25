<?php
/**
 * @var array $product Lista de productos
 * @var array $category Lista de categorías
 * @var array $lang_prods Textos de productos
 * @var array $lang_labels Textos de etiquetas
 * @var array $lang_optionbasic Textos básicos
 */
?>
<div class="modal fade" id="modalAddCatalog" tabindex="-1" role="dialog" aria-labelledby="modalAddCatalogLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="catalogAdd" novalidate class="forms-sample" action="<?= company_url('catalog/create') ?>" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddCatalogLabel"><?= $lang_prods['add_new_catalog'] ?? 'Agregar Nuevo Catálogo' ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label><?= $lang_prods['catalog_name'] ?? 'Nombre del Catálogo' ?>: <span class="color-red">*</span></label>
                                <input type="text" class="form-control" name="catalog_name" value="<?php echo set_value('catalog_name'); ?>" required="">
                                <span class="error"><?php echo form_error('catalog_name'); ?></span>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div id="myselect2" class="form-group">
                                <label><?= $lang_optionbasic['label_activo'] ?? 'Estado' ?>: </label>
                                <select class="form-control" name="status" required>
                                    <option value="1" selected><?= $lang_optionbasic['activo'] ?? 'Activo' ?></option>
                                    <option value="0"><?= $lang_optionbasic['desactivo'] ?? 'Inactivo' ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-5">
                            <div class="form-group">
                                <label><?= $lang_prods['cabeza_pagina'] ?? 'Cabecera de Página' ?>:</label>
                                <input type="text" class="form-control" name="cabeza_pagina">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div id="myselect2" class="form-group">
                                <label><?= $lang_prods['product_name'] ?? 'Productos' ?>: </label>
                                <select class="form-control js-example" name="products[]" multiple="multiple" style="visibility: hidden;" data-placeholder="<?= $lang_optionbasic['nothing_selected'] ?? 'Nada seleccionado' ?>">
                                    <?php if (isset($product) && is_array($product)): ?>
                                        <?php foreach ($product as $prod): ?>
                                            <option value="<?= $prod->pro_id ?>">
                                                <?= strtoupper($prod->sku . ' | ' . $prod->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group">
                                <label><?= $lang_prods['categories'] ?? 'Categorías' ?>:</label>
                                <select id="kt_dual_listbox_1" name="categories[]" class="dual-listbox" multiple="multiple" style="visibility: hidden;">
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
                    <!-- Ordenador de categorias (Select)  -->
                    <?php 
                    // Asegurar que category_selected_order esté definido para el modal de agregar
                    $category_selected_order = isset($category_selected_order) ? $category_selected_order : [];
                    $this->load->view('company/catalog/ordenador_de_categorias/dualist-box-edit', ['category_selected_order' => $category_selected_order, 'category' => $category ?? [], 'lang_prods' => $lang_prods ?? []]) 
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                        <?= $lang_labels['f_cancelar'] ?? 'Cancelar' ?>
                    </button>
                    <button type="submit" class="btn btn-success">
                        <?= $lang_labels['save'] ?? 'Guardar' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
// Cargar el script de inicialización del formulario
// Ajustar el script para que funcione dentro del modal
$scriptContent = $this->load->view('company/catalog/script-add-edit', $lang_prods ?? [], true);
// Reemplazar el selector del dropdownParent para que funcione en el modal
$scriptContent = str_replace('dropdownParent: $("#myselect2")', 'dropdownParent: $("#modalAddCatalog")', $scriptContent);
echo $scriptContent;
?>
<script>
// Asegurar que select2 y dual-listbox se inicialicen cuando el modal se abra
(function() {
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
                    // Intentar ejecutar el callback de todas formas si jQuery está disponible
                    if (typeof jQuery !== 'undefined') {
                        callback();
                    }
                }
            }
            check();
        }
        
        // Inicializar cuando el modal se muestre
        $('#modalAddCatalog').on('shown.bs.modal', function() {
            // Esperar a que select2 esté disponible antes de inicializarlo
            waitForSelect2(function() {
                if (typeof $.fn.select2 !== 'undefined') {
                    // Destruir instancias previas si existen
                    if ($(".js-example").hasClass('select2-hidden-accessible')) {
                        $(".js-example").select2('destroy');
                    }
                    // Inicializar select2 con el contenedor correcto del modal
                    $(".js-example").select2({
                        dropdownParent: $("#modalAddCatalog")
                    });
                } else {
                    console.error('select2 no está disponible para inicializar');
                }
            });
            
            // Cargar SortableJS si no está disponible
            function loadSortableJS(callback) {
                if (typeof Sortable !== 'undefined') {
                    console.log('✅ Sortable ya está disponible');
                    if (callback) callback();
                    return;
                }
                
                console.log('⏳ Cargando SortableJS...');
                var script = document.createElement('script');
                script.src = '<?= base_url('companyasset/js/sortable.min.js') ?>';
                script.onload = function() {
                    console.log('✅ SortableJS cargado correctamente');
                    if (callback) callback();
                };
                script.onerror = function() {
                    console.error('❌ Error al cargar SortableJS');
                };
                document.head.appendChild(script);
            }
            
            // Cargar SortableJS y luego inicializar el selector
            loadSortableJS(function() {
                if (typeof Sortable === 'undefined') {
                    console.error('❌ Sortable no está disponible después de cargar el script');
                    return;
                }
                console.log('✅ Sortable disponible, procediendo con la inicialización...');
                
                // Dar un pequeño delay para asegurar que el DOM esté completamente renderizado
                setTimeout(function() {
                    var result = initCategorySelector();
                    if (result) {
                        console.log('✅ Selector de categorías inicializado correctamente');
                    } else {
                        console.error('❌ Error al inicializar selector de categorías');
                        // Reintentar después de un segundo
                        setTimeout(function() {
                            initCategorySelector();
                        }, 1000);
                    }
                }, 200);
            });
            
            // Función para inicializar el selector de categorías (copia completa del script original)
            function initCategorySelector() {
                console.log('=== INICIANDO initCategorySelector ===');
                
                // Verificar que los elementos existan dentro del modal
                var modal = document.getElementById('modalAddCatalog');
                var leftContainer = modal ? modal.querySelector('#example2Left') : document.getElementById('example2Left');
                var rightContainer = modal ? modal.querySelector('#example2Right') : document.getElementById('example2Right');
                var all_itemscategories = document.querySelectorAll('#modalAddCatalog .fullvendor-select-order-categories .list-group-item');
                var btn_addall = modal ? modal.querySelector('#btn_addall') : document.getElementById('btn_addall');
                var btn_addone = modal ? modal.querySelector('#btn_addone') : document.getElementById('btn_addone');
                var btn_removeone = modal ? modal.querySelector('#btn_removeone') : document.getElementById('btn_removeone');
                var btn_removeall = modal ? modal.querySelector('#btn_removeall') : document.getElementById('btn_removeall');
                
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
                    console.error('❌ Elementos del selector de categorías no encontrados en el modal');
                    return false;
                }
                
                if (all_itemscategories.length === 0) {
                    console.error('❌ No se encontraron items de categorías en el modal');
                    return false;
                }
                
                console.log('✅ Todos los elementos encontrados correctamente');
                
                // Variables globales para este modal
                var container_working = null;
                var container_past = null;
                var list_itemsfocus = [];
                
                // Funciones auxiliares
                var get_Containerselecteditems = function() { return document.querySelector('#example2Right'); };
                var get_Containernotselecteditems = function() { return document.querySelector('#example2Left'); };
                var get_selecteditems = function() { return document.querySelectorAll('#example2Right .list-group-item'); };
                var get_notselecteditems = function() { return document.querySelectorAll('#example2Left .list-group-item'); };
                
                var order_categories = function(elements) {
                    elements.forEach(function(el, index) {
                        index++;
                        var span = el.querySelector('span.css-order-item');
                        if (span) span.innerText = index;
                    });
                };
                
                var update_ItemsnotSelected = function() {
                    var listGroupItems = document.querySelectorAll('#example2Left .list-group-item');
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
                
                var removeall_or_addone = function(elements) {
                    var fragment = document.createDocumentFragment();
                    elements.forEach(function(el) {
                        if (el.dataset.movable) {
                            var itemEl = el.querySelector('input');
                            var span_order = el.querySelector('span.css-order-item');
                            if (span_order) span_order.classList.add('hidden-important');
                            el.classList.remove('active-selected-category');
                            if (itemEl) itemEl.removeAttribute('name');
                            fragment.appendChild(el);
                        }
                    });
                    leftContainer.appendChild(fragment);
                    order_categories(get_selecteditems());
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
                if (!leftContainer.sortableInstance) {
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
                
                if (!rightContainer.sortableInstance) {
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
                
                // Función para ordenar categorías
                function order_categories(elements) {
                    elements.forEach(function(el, index) {
                        index++;
                        var span = el.querySelector('span.css-order-item');
                        if (span) span.innerText = index;
                    });
                }
                
                // Función para actualizar items no seleccionados
                function update_ItemsnotSelected() {
                    var listGroupItems = document.querySelectorAll('#example2Left .list-group-item');
                    Array.from(listGroupItems).forEach(function(el) {
                        var spanElement = el.querySelector('span.css-order-item');
                        if (spanElement && !spanElement.classList.contains('hidden-important')) {
                            spanElement.classList.add('hidden-important');
                            var itemEl = el.querySelector('input');
                            if (itemEl) itemEl.removeAttribute('name');
                        }
                    });
                }
                
                // Función para agregar elementos
                function addall_or_addone(elements) {
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
                    order_categories(rightContainer.querySelectorAll('.list-group-item'));
                }
                
                // Función para remover elementos
                function removeall_or_addone(elements) {
                    var fragment = document.createDocumentFragment();
                    elements.forEach(function(el) {
                        if (el.dataset.movable) {
                            var itemEl = el.querySelector('input');
                            var span_order = el.querySelector('span.css-order-item');
                            if (span_order) span_order.classList.add('hidden-important');
                            el.classList.remove('active-selected-category');
                            if (itemEl) itemEl.removeAttribute('name');
                            fragment.appendChild(el);
                        }
                    });
                    leftContainer.appendChild(fragment);
                    order_categories(rightContainer.querySelectorAll('.list-group-item'));
                }
                
                // Variable compartida para los items enfocados (solo para este modal)
                if (!window.list_itemsfocus_modal) {
                    window.list_itemsfocus_modal = [];
                }
                var list_itemsfocus = window.list_itemsfocus_modal;
                
                // Función para eliminar de la lista
                function delete_fromlist(item) {
                    var index = list_itemsfocus.indexOf(item);
                    if (index !== -1) {
                        list_itemsfocus.splice(index, 1);
                    }
                }
                
                // Función para agregar a la lista
                function add_tolist(item) {
                    if (!list_itemsfocus.includes(item)) {
                        list_itemsfocus.push(item);
                    }
                }
                
                // Limpiar event listeners anteriores usando namespaces
                $('#modalAddCatalog').off('click.categorySelector', '#btn_addall');
                $('#modalAddCatalog').off('click.categorySelector', '#btn_addone');
                $('#modalAddCatalog').off('click.categorySelector', '#btn_removeone');
                $('#modalAddCatalog').off('click.categorySelector', '#btn_removeall');
                
                // Agregar eventos a los botones usando event delegation desde el modal
                $('#modalAddCatalog').on('click.categorySelector', '#btn_addall', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón Add All clickeado');
                    var categories_notselected = document.querySelectorAll('#modalAddCatalog #example2Left .list-group-item');
                    console.log('Categorías no seleccionadas encontradas:', categories_notselected.length);
                    addall_or_addone(Array.from(categories_notselected));
                });
                
                $('#modalAddCatalog').on('click.categorySelector', '#btn_addone', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón Add One clickeado, items seleccionados:', list_itemsfocus.length);
                    if (list_itemsfocus.length > 0) {
                        btn_addone.disabled = true;
                        btn_removeone.disabled = true;
                        addall_or_addone(list_itemsfocus);
                        list_itemsfocus.length = 0; // Limpiar array
                    }
                });
                
                $('#modalAddCatalog').on('click.categorySelector', '#btn_removeone', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón Remove One clickeado, items seleccionados:', list_itemsfocus.length);
                    if (list_itemsfocus.length > 0) {
                        removeall_or_addone(list_itemsfocus);
                        update_ItemsnotSelected();
                        list_itemsfocus.length = 0; // Limpiar array
                        btn_addone.disabled = true;
                        btn_removeone.disabled = true;
                    }
                });
                
                $('#modalAddCatalog').on('click.categorySelector', '#btn_removeall', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón Remove All clickeado');
                    var categories_selected = document.querySelectorAll('#modalAddCatalog #example2Right .list-group-item');
                    console.log('Categorías seleccionadas encontradas:', categories_selected.length);
                    removeall_or_addone(Array.from(categories_selected));
                    update_ItemsnotSelected();
                });
                
                // Agregar eventos a los items usando event delegation desde el modal
                // Esto asegura que funcione incluso si los elementos se agregan dinámicamente
                console.log('🔧 Configurando event listeners para items de categorías...');
                $('#modalAddCatalog').off('click.modalCategory', '.fullvendor-select-order-categories .list-group-item');
                $('#modalAddCatalog').on('click.modalCategory', '.fullvendor-select-order-categories .list-group-item', function(e) {
                    console.log('🖱️ CLICK DETECTADO en categoría');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var item = this;
                    console.log('📦 Item clickeado:', item);
                    console.log('📦 Item text:', item.textContent);
                    console.log('📦 Item parent:', item.parentElement);
                    console.log('📦 Item parent id:', item.parentElement ? item.parentElement.id : 'NO ID');
                    
                    // Limpiar todas las selecciones previas dentro del modal
                    var all_items = document.querySelectorAll('#modalAddCatalog .fullvendor-select-order-categories .list-group-item.active-selected-category');
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
                        var items = document.querySelectorAll('#' + container_past + ' .list-group-item.active-selected-category');
                        console.log('🔄 Cambió de contenedor, limpiando:', items.length, 'items');
                        items.forEach(function(prevItem) {
                            prevItem.classList.remove('active-selected-category');
                            delete_fromlist(prevItem);
                        });
                    }
                    
                    if (!item.getAttribute('no_removed')) {
                        if (container_working == 'example2Left') {
                            console.log('✅ Habilitando botón agregar uno');
                            btn_addone.disabled = false;
                            btn_removeone.disabled = true;
                        } else if (container_working == 'example2Right') {
                            console.log('✅ Habilitando botón remover uno');
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
                        console.log('✅ Categoría SELECCIONADA, total seleccionadas:', list_itemsfocus.length);
                        console.log('✅ Clases del item después de seleccionar:', item.className);
                    } else {
                        item.classList.remove('active-selected-category');
                        delete_fromlist(item);
                        console.log('❌ Categoría DESELECCIONADA, total seleccionadas:', list_itemsfocus.length);
                        console.log('❌ Clases del item después de deseleccionar:', item.className);
                        
                        // Si no hay items seleccionados, deshabilitar botones
                        if (list_itemsfocus.length === 0) {
                            btn_addone.disabled = true;
                            btn_removeone.disabled = true;
                        }
                    }
                });
                
                console.log('✅ Event listeners configurados correctamente');
                
                // Verificar que los listeners estén funcionando
                var testItems = document.querySelectorAll('#modalAddCatalog .fullvendor-select-order-categories .list-group-item');
                console.log('🧪 Items disponibles para click:', testItems.length);
                testItems.forEach(function(item, index) {
                    console.log('  Item ' + index + ':', item.textContent.trim(), '| Parent:', item.parentElement ? item.parentElement.id : 'NO PARENT');
                });
            }
            
        });
        
        // Limpiar cuando el modal se oculte
        $('#modalAddCatalog').on('hidden.bs.modal', function() {
            // Limpiar selecciones
            $('.fullvendor-select-order-categories .list-group-item').removeClass('active-selected-category');
        });
        
        // Manejar el envío del formulario
        var catalogForm = document.getElementById('catalogAdd');
        if (catalogForm) {
            catalogForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('📤 Enviando formulario...');
                
                const button = e.submitter || catalogForm.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    const textBefore = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + textBefore;
                    
                    // Restaurar botón en caso de error
                    setTimeout(function() {
                        button.disabled = false;
                        button.innerHTML = textBefore;
                    }, 10000);
                }
                
                const datos = new FormData(catalogForm);
                
                // Log para depuración
                console.log('📋 Datos del formulario:');
                for (let pair of datos.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                fetch(catalogForm.action, {
                    method: 'POST',
                    body: datos
                })
                .then(response => {
                    console.log('📥 Respuesta recibida:', response.status, response.statusText);
                    if (!response.ok) {
                        // Si la respuesta no es OK, intentar leer el texto del error
                        return response.text().then(text => {
                            console.error('❌ Error del servidor:', text);
                            throw new Error('Error del servidor: ' + response.status + ' - ' + text.substring(0, 200));
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('✅ Datos recibidos:', data);
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = button.getAttribute('data-original-text') || '<?= $lang_labels['save'] ?? 'Guardar' ?>';
                    }
                    
                    if (!data.success) {
                        if (typeof alert_func !== 'undefined') {
                            alert_func([data.msg || 'Error al guardar', 'error']);
                        } else {
                            alert(data.msg || 'Error al guardar');
                        }
                        return;
                    }

                    // Mostrar mensaje de éxito
                    var successMsg = data.msg || data.message || 'Catálogo creado correctamente';
                    console.log('🎉 Éxito al crear catálogo:', successMsg);

                    if (typeof alert_func !== 'undefined') {
                        // Usar helper global si existe (muestra toast/alert estilizado)
                        alert_func([successMsg, 'success']);
                    } else if (typeof swal !== 'undefined') {
                        // Fallback a SweetAlert si está disponible
                        swal({
                            title: successMsg,
                            icon: 'success'
                        });
                    } else {
                        // Fallback básico
                        alert(successMsg);
                    }

                    // Cerrar el modal
                    $('#modalAddCatalog').modal('hide');
                    
                    // Redirigir si hay URL
                    if (data.urlredirect) {
                        window.location.href = data.urlredirect;
                    } else {
                        // Pequeño delay para que el usuario vea el mensaje y luego recargar la página
                        setTimeout(function() {
                            window.location.reload();
                        }, 800);
                    }
                })
                .catch(error => {
                    console.error('❌ Error al enviar formulario:', error);
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = button.getAttribute('data-original-text') || '<?= $lang_labels['save'] ?? 'Guardar' ?>';
                    }
                    
                    let errorMsg = 'Error al guardar el catálogo. ';
                    if (error.message) {
                        errorMsg += error.message;
                    } else {
                        errorMsg += 'Por favor, verifica la consola para más detalles.';
                    }
                    
                    if (typeof alert_func !== 'undefined') {
                        alert_func([errorMsg, 'error']);
                    } else {
                        alert(errorMsg);
                    }
                });
            });
        }
    });
})();
</script>

