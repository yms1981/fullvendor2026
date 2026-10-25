<?php
/**
 * Importación masiva de órdenes desde JSON (formato API order/import / import-bulk).
 *
 * @var array $pageTexts
 * @var string|null $format_currency
 * @var object|array|null $format_numeric
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    $v = $pageTexts[$key] ?? $fallback;

    return is_string($v) ? $v : $fallback;
};
$previewUrl = company_url('orders/importJsonPreview');
$confirmUrl = company_url('orders/importJsonConfirm');
$sampleUrl = company_url('orders/importJsonSample');
?>
<div class="modal fade" id="modal_import_orders_json" tabindex="-1" role="dialog" aria-labelledby="modal_import_orders_json_title" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_import_orders_json_title">
                    <i class="mdi mdi-code-json" aria-hidden="true"></i>
                    <?= htmlspecialchars($t('import_json_title', 'Importar órdenes JSON'), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($t('cancelar', 'Cancelar'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3"><?= htmlspecialchars($t('import_json_subtitle', ''), ENT_QUOTES, 'UTF-8') ?></p>

                <div id="fv_json_import_step_upload">
                    <div class="d-flex flex-wrap align-items-center mb-3" style="gap:0.75rem;">
                        <a href="<?= htmlspecialchars($sampleUrl, ENT_QUOTES, 'UTF-8') ?>"
                           class="btn btn-outline-secondary btn-sm"
                           download="orders-import-example.json">
                            <i class="mdi mdi-download" aria-hidden="true"></i>
                            <?= htmlspecialchars($t('import_json_download_sample', 'Descargar ejemplo JSON'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="fv_json_import_file" accept=".json,application/json">
                        <label class="custom-file-label" for="fv_json_import_file" id="fv_json_import_file_label">
                            <?= htmlspecialchars($t('import_json_choose_file', 'Seleccione un archivo .json'), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" id="fv_json_import_btn_preview" disabled>
                            <i class="mdi mdi-eye" aria-hidden="true"></i>
                            <?= htmlspecialchars($t('import_json_preview_btn', 'Vista previa'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </div>

                <div id="fv_json_import_step_preview" class="d-none">
                    <div class="alert alert-info py-2" id="fv_json_import_summary"></div>
                    <div class="table-responsive" style="max-height:420px;overflow:auto;">
                        <table class="table table-sm table-bordered table-hover mb-0" id="fv_json_import_preview_table">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th><?= htmlspecialchars($t('order_number', 'N° Orden'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars($t('import_json_col_iderp', 'IDERP'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars($t('customer', 'Cliente'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars($t('import_json_col_items', 'Ítems'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars($t('total', 'Total'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars($t('import_json_col_status', 'Estado preview'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div id="fv_json_import_loading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0" id="fv_json_import_loading_text">
                        <?= htmlspecialchars($t('import_json_loading', 'Procesando…'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

                <div id="fv_json_import_result" class="d-none mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal" id="fv_json_import_btn_close">
                    <?= htmlspecialchars($t('cancelar', 'Cancelar'), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-secondary d-none" id="fv_json_import_btn_back">
                    <?= htmlspecialchars($t('import_json_back', 'Volver'), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-success d-none" id="fv_json_import_btn_confirm" disabled>
                    <i class="mdi mdi-upload" aria-hidden="true"></i>
                    <?= htmlspecialchars($t('import_json_confirm_btn', 'Importar órdenes'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var previewUrl = <?= json_encode($previewUrl, JSON_UNESCAPED_UNICODE) ?>;
    var confirmUrl = <?= json_encode($confirmUrl, JSON_UNESCAPED_UNICODE) ?>;
    var texts = {
        chooseFile: <?= json_encode($t('import_json_choose_file', 'Seleccione un archivo .json'), JSON_UNESCAPED_UNICODE) ?>,
        loadingPreview: <?= json_encode($t('import_json_loading_preview', 'Generando vista previa…'), JSON_UNESCAPED_UNICODE) ?>,
        loadingImport: <?= json_encode($t('import_json_loading_import', 'Importando órdenes…'), JSON_UNESCAPED_UNICODE) ?>,
        summary: <?= json_encode($t('import_json_summary', 'Órdenes: %total%. Listas: %ok%. Con avisos: %warn%. Inválidas: %err%.'), JSON_UNESCAPED_UNICODE) ?>,
        ok: <?= json_encode($t('import_json_row_ok', 'Lista'), JSON_UNESCAPED_UNICODE) ?>,
        warn: <?= json_encode($t('import_json_row_warn', 'Aviso'), JSON_UNESCAPED_UNICODE) ?>,
        err: <?= json_encode($t('import_json_row_err', 'Inválida'), JSON_UNESCAPED_UNICODE) ?>,
        importDone: <?= json_encode($t('import_json_done', 'Importación finalizada.'), JSON_UNESCAPED_UNICODE) ?>,
        connectionError: <?= json_encode($t('add_order_connection_error', 'Error de conexión'), JSON_UNESCAPED_UNICODE) ?>,
        mustSelect: <?= json_encode($t('import_json_must_select', 'Seleccione un archivo JSON.'), JSON_UNESCAPED_UNICODE) ?>,
        noValid: <?= json_encode($t('import_json_no_valid', 'No hay órdenes válidas para importar.'), JSON_UNESCAPED_UNICODE) ?>
    };
    var currencySymbol = <?= json_encode(is_string($format_currency ?? null) ? $format_currency : '$', JSON_UNESCAPED_UNICODE) ?>;
    var formatNumeric = <?= json_encode($format_numeric ?? ['digits' => 2, 'radixPoint' => '.', 'groupSeparator' => ','], JSON_UNESCAPED_UNICODE) ?>;

    var selectedFile = null;
    var previewPayload = null;

    function $(sel) { return document.querySelector(sel); }
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function formatMoney(n) {
        var digits = parseInt((formatNumeric && (formatNumeric.digits || formatNumeric.Digits)) || 2, 10);
        var radix = (formatNumeric && (formatNumeric.radixPoint || formatNumeric.radix_point)) || '.';
        var group = (formatNumeric && (formatNumeric.groupSeparator || formatNumeric.group_separator)) || '';
        if (!group) group = radix === ',' ? '.' : ',';
        var num = Number(n || 0);
        var parts = num.toFixed(digits).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, group);
        var out = parts.join(radix);
        return (currencySymbol ? currencySymbol + ' ' : '') + out;
    }
    function setLoading(on, text) {
        var box = $('#fv_json_import_loading');
        var txt = $('#fv_json_import_loading_text');
        if (txt && text) txt.textContent = text;
        if (box) box.classList.toggle('d-none', !on);
    }
    function showStep(step) {
        var up = $('#fv_json_import_step_upload');
        var pr = $('#fv_json_import_step_preview');
        var res = $('#fv_json_import_result');
        var btnBack = $('#fv_json_import_btn_back');
        var btnConfirm = $('#fv_json_import_btn_confirm');
        if (up) up.classList.toggle('d-none', step !== 'upload');
        if (pr) pr.classList.toggle('d-none', step !== 'preview');
        if (res) {
            res.classList.toggle('d-none', step !== 'result');
            if (step !== 'result') res.innerHTML = '';
        }
        if (btnBack) btnBack.classList.toggle('d-none', step === 'upload');
        if (btnConfirm) {
            btnConfirm.classList.toggle('d-none', step !== 'preview');
        }
    }
    function fillSummary(summary) {
        var el = $('#fv_json_import_summary');
        if (!el) return;
        var msg = texts.summary
            .replace('%total%', summary.total || 0)
            .replace('%ok%', summary.ok || 0)
            .replace('%warn%', summary.warn || 0)
            .replace('%err%', summary.error || 0);
        el.textContent = msg;
    }
    function fillTable(rows) {
        var tbody = document.querySelector('#fv_json_import_preview_table tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        (rows || []).forEach(function (row, idx) {
            var st = row.status || 'ok';
            var badge = st === 'error' ? 'danger' : (st === 'warn' ? 'warning' : 'success');
            var label = st === 'error' ? texts.err : (st === 'warn' ? texts.warn : texts.ok);
            var tr = document.createElement('tr');
            if (st === 'error') tr.classList.add('table-danger');
            else if (st === 'warn') tr.classList.add('table-warning');
            tr.innerHTML =
                '<td>' + esc(idx + 1) + '</td>' +
                '<td>' + esc(row.norden || '') + '</td>' +
                '<td>' + esc(row.iderp || '') + '</td>' +
                '<td>' + esc(row.customer || '') + '</td>' +
                '<td class="text-center">' + esc(row.items_count || 0) + '</td>' +
                '<td class="text-right">' + esc(formatMoney(row.total || 0)) + '</td>' +
                '<td><span class="badge badge-' + badge + '">' + esc(label) + '</span>' +
                (row.message ? '<div class="small text-muted mt-1">' + esc(row.message) + '</div>' : '') +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function bind() {
        var fileInput = $('#fv_json_import_file');
        var label = $('#fv_json_import_file_label');
        var btnPreview = $('#fv_json_import_btn_preview');
        var btnConfirm = $('#fv_json_import_btn_confirm');
        var btnBack = $('#fv_json_import_btn_back');
        var modal = $('#modal_import_orders_json');

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                selectedFile = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (label) label.textContent = selectedFile ? selectedFile.name : texts.chooseFile;
                if (btnPreview) btnPreview.disabled = !selectedFile;
                previewPayload = null;
                if (btnConfirm) btnConfirm.disabled = true;
            });
        }
        if (btnPreview) {
            btnPreview.addEventListener('click', function () {
                if (!selectedFile) {
                    alert(texts.mustSelect);
                    return;
                }
                var fd = new FormData();
                fd.append('file_json', selectedFile);
                setLoading(true, texts.loadingPreview);
                showStep('upload');
                fetch(previewUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        setLoading(false);
                        if (!data || !data.success) {
                            alert((data && data.message) || texts.connectionError);
                            return;
                        }
                        previewPayload = data;
                        fillSummary(data.summary || {});
                        fillTable(data.rows || []);
                        showStep('preview');
                        if (btnConfirm) {
                            btnConfirm.disabled = !(data.summary && data.summary.importable > 0);
                        }
                    })
                    .catch(function () {
                        setLoading(false);
                        alert(texts.connectionError);
                    });
            });
        }
        if (btnBack) {
            btnBack.addEventListener('click', function () {
                showStep('upload');
            });
        }
        if (btnConfirm) {
            btnConfirm.addEventListener('click', function () {
                if (!selectedFile) {
                    alert(texts.mustSelect);
                    return;
                }
                if (!previewPayload || !previewPayload.summary || !(previewPayload.summary.importable > 0)) {
                    alert(texts.noValid);
                    return;
                }
                var fd = new FormData();
                fd.append('file_json', selectedFile);
                setLoading(true, texts.loadingImport);
                btnConfirm.disabled = true;
                fetch(confirmUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        setLoading(false);
                        var box = $('#fv_json_import_result');
                        if (box) {
                            var s = (data && data.summary) || {};
                            box.innerHTML =
                                '<div class="alert alert-' + ((data && data.success) ? 'success' : 'warning') + '">' +
                                '<strong>' + esc((data && data.message) || texts.importDone) + '</strong>' +
                                '<div class="mt-2 small">' +
                                esc('created=' + (s.created || 0) + ', updated=' + (s.updated || 0) +
                                    ', skipped=' + (s.skipped || 0) + ', failed=' + (s.failed || 0)) +
                                '</div></div>';
                        }
                        showStep('result');
                        if (btnConfirm) btnConfirm.classList.add('d-none');
                        if (data && data.success && data.summary && ((data.summary.created || 0) + (data.summary.updated || 0)) > 0) {
                            setTimeout(function () { window.location.reload(); }, 1500);
                        }
                    })
                    .catch(function () {
                        setLoading(false);
                        btnConfirm.disabled = false;
                        alert(texts.connectionError);
                    });
            });
        }
        if (modal && typeof jQuery !== 'undefined') {
            jQuery(modal).on('hidden.bs.modal', function () {
                selectedFile = null;
                previewPayload = null;
                if (fileInput) fileInput.value = '';
                if (label) label.textContent = texts.chooseFile;
                if (btnPreview) btnPreview.disabled = true;
                if (btnConfirm) btnConfirm.disabled = true;
                showStep('upload');
                setLoading(false);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
</script>
