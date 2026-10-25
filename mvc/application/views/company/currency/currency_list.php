<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h3 class="page-title mb-0"><?= htmlspecialchars((string) ($title ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12 fv-config-crud-toolbar fv-config-crud-toolbar--page">
                            <?php
                            $langCurrency = is_array($lang_currency ?? null) ? $lang_currency : [];
                            $exportPdfLabel = (string) ($langCurrency['btn_export_pdf'] ?? '');
                            ?>
                            <a href="<?= company_url('currencies/listPdf') ?>" class="btn fv-config-crud-btn btn-success btn-icon-text" title="<?= htmlspecialchars($exportPdfLabel, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-file-pdf-box" aria-hidden="true"></i>
                                <span class="fv-config-crud-btn__label"><?= htmlspecialchars($exportPdfLabel, ENT_QUOTES, 'UTF-8') ?></span>
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
                                            <th class="fuente_16px text-center fv-config-crud-order-column fv-dt-no-sort" style="width: 60px;" scope="col">#</th>
                                            <th><?= $lang_unit['continent'] ?></th>
                                            <th><?= $lang_unit['country'] ?></th>
                                            <th><?= $lang_unit['currency'] ?></th>
                                            <th><?= $lang_unit['code'] ?></th>
                                            <th><?= $lang_unit['symbol'] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $orderNumber = 1; ?>
                                        <tr>
                                            <td class="fuente_16px text-center fv-config-crud-order-cell" style="width: 60px; vertical-align: middle;" data-order="<?= (int) $orderNumber ?>">
                                                <?php $this->load->view('components/crud/config_crud_order_avatar', [
                                                    'orderNumber' => $orderNumber,
                                                    'isDefaultRow' => true,
                                                ]); ?>
                                            </td>
                                            <td><?= htmlspecialchars((string) ($currency->GroupID ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php $this->load->view('company/currency/list/country_cell', [
                                                    'countryName' => (string) ($currency->Contry ?? ''),
                                                    'sortname' => (string) ($currency->country_sortname ?? ''),
                                                ]); ?>
                                            </td>
                                            <td><?= htmlspecialchars((string) ($currency->Name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($currency->Code ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><strong><?= htmlspecialchars((string) ($currency->Symbol ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        </tr>
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

<?php
    $this->load->view('components/crud/config_crud_datagrid_init', ['langDatatable' => $lang_datatable ?? []]);
?>
