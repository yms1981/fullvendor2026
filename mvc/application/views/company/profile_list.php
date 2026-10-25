<?php 
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
  $url = "https://";   
  else  
  $url = "http://";  
    $url.= $_SERVER['HTTP_HOST']; 
    
    $url.= $_SERVER['REQUEST_URI']; 
  ?>
<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h3 class="page-title mb-0"><?= htmlspecialchars((string) ($lang_vendor['pageTitle'] ?? ($lang_vendor['list_profile_vendor'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h3>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12 fv-config-crud-toolbar fv-config-crud-toolbar--page">
                            <button type="button" class="btn fv-config-crud-btn btn-success btn-icon-text" data-toggle="modal" data-target="#modal_add_profile">
                                <i class="mdi mdi-plus-circle" aria-hidden="true"></i>
                                <span class="fv-config-crud-btn__label"><?= htmlspecialchars((string) ($lang_vendor['add_new_profile_vendor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                            <a href="<?= company_url('sellerprofile/csvImport') ?>" class="btn fv-config-crud-btn btn-success btn-icon-text" title="<?= htmlspecialchars((string) ($lang_vendor['import'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-import" aria-hidden="true"></i>
                                <span class="fv-config-crud-btn__label"><?= htmlspecialchars((string) ($lang_vendor['import'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                            <a href="<?= company_url('sellerprofile/export') ?>" class="btn fv-config-crud-btn btn-success btn-icon-text" title="<?= htmlspecialchars((string) ($lang_vendor['export'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-export" aria-hidden="true"></i>
                                <span class="fv-config-crud-btn__label"><?= htmlspecialchars((string) ($lang_vendor['export'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                            <a href="<?= import_template_download_url('sellerprofilescsv.xlsx') ?>" download class="btn fv-config-crud-btn btn-success btn-icon-text" title="<?= htmlspecialchars((string) ($lang_vendor['btn_download_template'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-file-document-outline" aria-hidden="true"></i>
                                <span class="fv-config-crud-btn__label"><?= htmlspecialchars((string) ($lang_vendor['btn_download_template'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                            <a href="<?= company_url('sellerprofile/listPdf') ?>" class="btn fv-config-crud-btn btn-success btn-icon-text" title="<?= htmlspecialchars((string) ($lang_vendor['btn_pdf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-file-pdf-box" aria-hidden="true"></i>
                                <span class="fv-config-crud-btn__label"><?= htmlspecialchars((string) ($lang_vendor['btn_pdf'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                        </div>
                    </div>
                    <div class="row fv-config-crud-datagrid-page">
                        <div class="col-12">
                            <div class="fv-modern-grid fv-config-crud-datagrid-wrap fv-config-crud-datagrid-wrap--active">
                                <div class="fv-modern-grid__shell">
                                <div class="fv-modern-grid__scroll">
                                <table id="infoTable" class="table fv-modern-grid__table fv-config-crud-datagrid w-100">
                                    <thead>
                                        <tr>
                                            <th width="20" class="fv-dt-no-sort">#</th>
                                            <th><?= $lang_vendor['name_profile'] ?></th>
                                            <th><?= $lang_vendor['order_discount'] ?></th>
                                            <th><?= $lang_vendor['order_net_discount'] ?></th>
                                            <th><?= $lang_vendor['change_price'] ?></th>
                                            <th><?= $lang_vendor['send_catalog'] ?></th>
                                            <th><?= $lang_vendor['create_customer'] ?></th>
                                            <th><?= $lang_vendor['created'] ?></th>
                                            <th width="60" class="fv-dt-no-sort fv-crud-actions-column"><?= htmlspecialchars((string) ($lang_vendor['actions'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; ?>
                                        <?php foreach ($list as $key => $v): ?>
                                        <tr class="notranslate">
                                            <td><?= $i ?></td>
                                            <td><?= @$v->profile_name ?> </td>
                                            <td><?= $v->order_discount == "1" ? $lang_vendor['si']:'No' ?></td>
                                            <td><?= $v->can_change_price == "1" ? $lang_vendor['si']:'No' ?></td>
                                            <td><?= $v->can_send_catalog == "1" ? $lang_vendor['si']:'No' ?></td>
                                            <td><?= $v->can_send_catalog == "1" ? $lang_vendor['si']:'No' ?></td>
                                            <td><?= $v->can_create_customer == "1" ? $lang_vendor['si']:'No' ?></td>
                                            <td><?= fv_format_display_date(@$v->created) ?></td>
                                            <td class="fv-modern-grid__actions">
                                                <div class="fv-modern-grid__actions-inner fv-crud-row-actions">
                                                    <button type="button" 
                                                        class="btn btn-success btn-sm fv-crud-btn fv-crud-btn--row" 
                                                        data-toggle="modal" 
                                                        data-target="#modal_edit_profile"
                                                        data-profile-id="<?= $v->profile_id ?>"
                                                        title="<?= htmlspecialchars((string) ($lang_toltip['edit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    <a href="<?= company_url('userprofile/delete/'. $v->profile_id) ?>"
                                                        data-msg = "<?= $lang_vendor['delete_profile_seller'] . ' ' . @$v->profile_name . '?' ?>"
                                                        class="btn btn-danger btn-sm fv-crud-btn fv-crud-btn--row delete_anygeneral"
                                                        title="<?= htmlspecialchars((string) ($lang_toltip['delete'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="mdi mdi-delete"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $i++; ?>
                                        <?php endforeach ?>

                                    </tbody>
                                </table>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edición -->
<div class="modal fade" id="modal_edit_profile" tabindex="-1" role="dialog" aria-labelledby="modal_edit_profileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_edit_profileLabel"><?= $lang_vendor['edit_profile_vendor'] ?? 'Editar Perfil de Vendedor' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal_edit_profile_body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Agregar -->
<div class="modal fade" id="modal_add_profile" tabindex="-1" role="dialog" aria-labelledby="modal_add_profileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_add_profileLabel"><?= $lang_vendor['add_new_profile_vendor'] ?? 'Agregar Nuevo Perfil de Vendedor' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal_add_profile_body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handlers del modal de agregar - ejecutar inmediatamente cuando jQuery esté disponible
(function() {
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined') {
            callback();
        } else {
            setTimeout(function() { waitForJQuery(callback); }, 100);
        }
    }
    
    waitForJQuery(function() {
        $(document).ready(function() {
            // Cuando se abre el modal de agregar
            $('#modal_add_profile').on('show.bs.modal', function (event) {
                const modalBody = $('#modal_add_profile_body');
                
                // Mostrar spinner mientras carga
                modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
                
                // Cargar el formulario de agregar
                // Siempre usar sellerprofile ya que userprofile ya no existe
                var urlBase = (typeof options !== 'undefined' && options.url) ? options.url : '<?= rtrim(company_url(), '/') ?>';
                $.ajax({
                    url: urlBase + '/sellerprofile/getAddForm',
                    type: 'GET',
                    dataType: 'html',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        modalBody.html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al cargar formulario de agregar:', error);
                        modalBody.html('<div class="alert alert-danger">Error al cargar el formulario de agregar</div>');
                    }
                });
            });

            // Cuando se cierra el modal de agregar, limpiar el contenido
            $('#modal_add_profile').on('hidden.bs.modal', function () {
                $('#modal_add_profile_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
            });
            
            // Manejar el envío del formulario de agregar desde el modal
            $(document).on('submit', '#addProfileForm', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = form.serialize();
                const formAction = form.attr('action');
                
                $.ajax({
                    url: formAction,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success || response.redirect) {
                            // Cerrar el modal
                            $('#modal_add_profile').modal('hide');
                            // Recargar la página para ver los cambios
                            location.reload();
                        } else {
                            alert('Error al crear el perfil');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Si el servidor devuelve HTML (redirect), recargar la página
                        if (xhr.responseText && xhr.responseText.indexOf('<!DOCTYPE') !== -1) {
                            $('#modal_add_profile').modal('hide');
                            location.reload();
                        } else {
                            console.error('Error al crear:', error);
                            alert('Error al crear el perfil');
                        }
                    }
                });
            });
        });
    });
})();

<?php $this->load->view('components/crud/config_crud_datagrid_init', ['langDatatable' => $lang_datatable ?? []]); ?>

// Esperar a que jQuery esté disponible para modales
(function() {
    function waitForJq(callback, maxAttempts) {
        maxAttempts = maxAttempts || 100;
        var attempts = 0;
        function check() {
            attempts++;
            if (typeof jQuery !== 'undefined') {
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            }
        }
        check();
    }

    waitForJq(function() {
        $(document).ready(function() {
            // Cuando se abre el modal de edición
            $('#modal_edit_profile').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                const profileId = button.data('profile-id');
                const modalBody = $('#modal_edit_profile_body');
                
                // Mostrar spinner mientras carga
                modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
                
                // Cargar el formulario de edición
                // Siempre usar sellerprofile ya que userprofile ya no existe
                var urlBase = (typeof options !== 'undefined' && options.url) ? options.url : '<?= rtrim(company_url(), '/') ?>';
                $.ajax({
                    url: urlBase + '/sellerprofile/getEditForm/' + profileId,
                    type: 'GET',
                    dataType: 'html',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        modalBody.html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al cargar formulario de edición:', error);
                        modalBody.html('<div class="alert alert-danger">Error al cargar el formulario de edición</div>');
                    }
                });
            });

            // Cuando se cierra el modal de edición, limpiar el contenido
            $('#modal_edit_profile').on('hidden.bs.modal', function () {
                $('#modal_edit_profile_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
            });

            // Manejar el envío del formulario de agregar desde el modal
            $(document).on('submit', '#addProfileForm', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = form.serialize();
                const formAction = form.attr('action');
                
                $.ajax({
                    url: formAction,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success || response.redirect) {
                            // Cerrar el modal
                            $('#modal_add_profile').modal('hide');
                            // Recargar la página para ver los cambios
                            location.reload();
                        } else {
                            alert('Error al crear el perfil');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Si el servidor devuelve HTML (redirect), recargar la página
                        if (xhr.responseText && xhr.responseText.indexOf('<!DOCTYPE') !== -1) {
                            $('#modal_add_profile').modal('hide');
                            location.reload();
                        } else {
                            console.error('Error al crear:', error);
                            alert('Error al crear el perfil');
                        }
                    }
                });
            });

            // Manejar el envío del formulario de edición desde el modal
            $(document).on('submit', '#editProfileForm', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = form.serialize();
                const formAction = form.attr('action');
                
                $.ajax({
                    url: formAction,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success || response.redirect) {
                            // Cerrar el modal
                            $('#modal_edit_profile').modal('hide');
                            // Recargar la página para ver los cambios
                            location.reload();
                        } else {
                            alert('Error al actualizar el perfil');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Si el servidor devuelve HTML (redirect), recargar la página
                        if (xhr.responseText && xhr.responseText.indexOf('<!DOCTYPE') !== -1) {
                            $('#modal_edit_profile').modal('hide');
                            location.reload();
                        } else {
                            console.error('Error al actualizar:', error);
                            alert('Error al actualizar el perfil');
                        }
                    }
                });
            });
        }); // Cierra $(document).ready
    }); // Cierra waitForDataTable callback
})(); // Cierra función anónima auto-ejecutable
</script>