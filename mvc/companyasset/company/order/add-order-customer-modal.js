/**
 * Modal de nueva orden / nota de crédito: listbox, opciones de origen y botón Crear.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var FvAddOrderCustomerModal = {
        allCustomers: [],
        searchTimer: null,
        isSubmitting: false,
        selectedCustomerId: '',
        csvPreviewCanContinue: false,
        csvPreviewLoading: false,

        getLang: function (key, fallback) {
            var L = window.fvAddOrderModalLang || {};
            return (L[key] && String(L[key]).trim() !== '') ? L[key] : fallback;
        },

        getModal: function () {
            return $('#modal_catalogorder');
        },

        getForm: function () {
            return document.getElementById('form-nueva-ordern-catalogo');
        },

        isCreditNote: function () {
            var el = document.getElementById('tipo_d_addorder');
            return el && el.value === 'C';
        },

        updateModalTitle: function () {
            var $modal = this.getModal();
            var title = this.isCreditNote()
                ? this.getLang('title_credit', 'Nueva Nota de Crédito')
                : this.getLang('title_order', 'Agregar Nueva Orden');
            $modal.find('.fv-customer-picker-modal__title-text').text(title);
        },

        configureCreditNoteMode: function () {
            var $modal = this.getModal();
            var isCredit = this.isCreditNote();
            var $catalogRow = $modal.find('#addneworder_row_catalog');
            var $typeCatalog = $modal.find('#type_catalog');
            var $typeGrid = $modal.find('#type_grid');

            if (isCredit) {
                $catalogRow.hide().find('input').prop('disabled', true);
                if ($typeGrid.length) {
                    $typeGrid.prop('checked', true);
                }
            } else {
                $catalogRow.show().find('input').prop('disabled', false);
                if ($typeCatalog.length && !$modal.find('input[name="order_source"]:checked').length) {
                    $typeCatalog.prop('checked', true);
                }
            }
            this.syncOrderSourceHidden();
        },

        syncOrderSourceHidden: function () {
            var form = this.getForm();
            if (!form) {
                return;
            }
            var source = (form.querySelector('input[name="order_source"]:checked') || {}).value || '';
            var hCatalog = form.querySelector('#hidden_type_catalog');
            var hGrid = form.querySelector('#hidden_type_grid');
            var hFile = form.querySelector('#hidden_type_file');
            if (hCatalog) hCatalog.value = source === 'catalog' ? 'catalog' : '';
            if (hGrid) hGrid.value = source === 'grilla' ? 'grilla' : '';
            if (hFile) hFile.value = source === 'csv' ? 'csv' : '';

            var csvBlock = document.getElementById('collapseCsvFile');
            if (csvBlock && typeof $ !== 'undefined') {
                $(csvBlock).collapse(source === 'csv' ? 'show' : 'hide');
            }
            if (source !== 'csv') {
                this.clearImportPreview();
            }
            this.updateCreateButton();
        },

        getSelectedOrderSource: function () {
            var form = this.getForm();
            if (!form) {
                return '';
            }
            var checked = form.querySelector('input[name="order_source"]:checked');
            return checked ? checked.value : '';
        },

        hasCsvFile: function () {
            var input = document.getElementById('validatedCustomFile');
            return !!(input && input.files && input.files.length > 0);
        },

        importPreviewValid: function () {
            return !!this.csvPreviewCanContinue;
        },

        updateCreateButton: function () {
            var $btn = $('#addorder_btn_create');
            if (!$btn.length) {
                return;
            }
            var hasCustomer = String(this.selectedCustomerId || '').trim() !== '';
            var source = this.getSelectedOrderSource();
            var csvOk = source !== 'csv' || (this.hasCsvFile() && this.importPreviewValid());
            var enabled = hasCustomer && source !== '' && csvOk && !this.isSubmitting && !this.csvPreviewLoading;
            $btn.prop('disabled', !enabled);
        },

        clearImportPreview: function () {
            this.csvPreviewCanContinue = false;
            this.csvPreviewLoading = false;
            var $box = $('#fv_order_import_preview');
            if ($box.length) {
                $box.attr('hidden', true);
                $('#fv_order_import_preview_body').empty();
                $('#fv_order_import_preview_summary').text('');
                $('#fv_order_import_preview_msg').text('').removeClass('fv-order-import-preview__msg--error fv-order-import-preview__msg--ok');
            }
            this.updateCreateButton();
        },

        renderImportPreview: function (payload) {
            var self = this;
            var $box = $('#fv_order_import_preview');
            var $body = $('#fv_order_import_preview_body');
            var $msg = $('#fv_order_import_preview_msg');
            var $summary = $('#fv_order_import_preview_summary');
            var $title = $('#fv_order_import_preview_title');
            if (!$box.length || !$body.length) {
                return;
            }

            $box.removeAttr('hidden');
            $title.text(self.getLang('preview_title', ''));
            $body.empty();
            $msg.removeClass('fv-order-import-preview__msg--error fv-order-import-preview__msg--ok');

            var rows = (payload && payload.rows) ? payload.rows : [];
            var summary = (payload && payload.summary) ? payload.summary : { total: 0, valid: 0, invalid: 0 };
            var summaryTpl = self.getLang('summary_tpl', '%d / %d');
            try {
                $summary.text(summaryTpl.replace('%d', String(summary.valid || 0)).replace('%d', String(summary.total || 0)));
            } catch (e) {
                $summary.text((summary.valid || 0) + ' / ' + (summary.total || 0));
            }

            rows.forEach(function (row) {
                var ok = !!row.ok;
                var statusText = ok
                    ? self.getLang('status_ok', 'OK')
                    : (row.error || '');
                var name = ok ? (row.resolved_name || row.product_name || '') : (row.product_name || '');
                var price = ok ? (row.resolved_price || row.sale_price || '') : (row.sale_price || '');
                var tr = $(
                    '<tr class="' + (ok ? 'fv-order-import-preview__row--ok' : 'fv-order-import-preview__row--err') + '">' +
                    '<td>' + self.escapeHtml(String(row.line || '')) + '</td>' +
                    '<td>' + self.escapeHtml(String(row.sku || '')) + '</td>' +
                    '<td>' + self.escapeHtml(String(name)) + '</td>' +
                    '<td>' + self.escapeHtml(String(row.qty || '')) + '</td>' +
                    '<td>' + self.escapeHtml(String(price)) + '</td>' +
                    '<td><span class="fv-order-import-preview__badge ' +
                    (ok ? 'fv-order-import-preview__badge--ok' : 'fv-order-import-preview__badge--err') +
                    '">' + self.escapeHtml(statusText) + '</span></td>' +
                    '</tr>'
                );
                $body.append(tr);
            });

            var canContinue = !!(payload && payload.can_continue);
            self.csvPreviewCanContinue = canContinue;
            var message = (payload && payload.message) ? payload.message : '';
            if (!payload || payload.success === false) {
                message = (payload && payload.message) ? payload.message : self.getLang('connection_error', '');
                canContinue = false;
                self.csvPreviewCanContinue = false;
            }
            $msg.text(message);
            $msg.addClass(canContinue ? 'fv-order-import-preview__msg--ok' : 'fv-order-import-preview__msg--error');
            self.updateCreateButton();
        },

        requestImportPreview: function (fileInput) {
            var self = this;
            if (!fileInput || !fileInput.files || !fileInput.files.length) {
                self.clearImportPreview();
                return;
            }

            var cfg = window.fvAddOrderModalConfig || {};
            var url = cfg.importPreviewUrl || '';
            if (!url) {
                self.csvPreviewCanContinue = false;
                self.updateCreateButton();
                return;
            }

            var formData = new FormData();
            formData.append('file_csv', fileInput.files[0]);

            self.csvPreviewLoading = true;
            self.csvPreviewCanContinue = false;
            self.updateCreateButton();

            var $box = $('#fv_order_import_preview');
            var $msg = $('#fv_order_import_preview_msg');
            if ($box.length) {
                $box.removeAttr('hidden');
                $('#fv_order_import_preview_title').text(self.getLang('preview_title', ''));
                $('#fv_order_import_preview_body').empty();
                $('#fv_order_import_preview_summary').text('');
                $msg.removeClass('fv-order-import-preview__msg--error fv-order-import-preview__msg--ok')
                    .text(self.getLang('preview_validating', ''));
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (data) {
                self.renderImportPreview(data || {});
            }).fail(function (xhr) {
                var msg = self.getLang('connection_error', '');
                try {
                    var j = xhr.responseJSON || (xhr.responseText ? JSON.parse(xhr.responseText) : null);
                    if (j && (j.message || j.error)) {
                        msg = j.message || j.error;
                    }
                } catch (e) {}
                self.renderImportPreview({
                    success: false,
                    can_continue: false,
                    message: msg,
                    rows: [],
                    summary: { total: 0, valid: 0, invalid: 0 }
                });
            }).always(function () {
                self.csvPreviewLoading = false;
                self.updateCreateButton();
            });
        },

        reset: function () {
            this.allCustomers = [];
            this.isSubmitting = false;
            this.selectedCustomerId = '';
            this.csvPreviewCanContinue = false;
            this.csvPreviewLoading = false;
            var $modal = this.getModal();
            $modal.find('#addorder_customer_id').val('');
            $modal.find('#fv_customer_search').val('');
            $modal.find('.fv-customer-picker__item').removeClass('is-selected');
            $modal.find('.fv-customer-picker__list').empty().attr('tabindex', '0');
            var fileInput = document.getElementById('validatedCustomFile');
            if (fileInput) {
                fileInput.value = '';
                var fileLabel = $modal.find('label.custom-file-label[for="validatedCustomFile"]');
                if (fileLabel.length && fileLabel.data('default-label')) {
                    fileLabel.text(fileLabel.data('default-label'));
                }
            }
            this.clearImportPreview();

            var preferFile = !!window.fvOrderPreferFileImport;
            window.fvOrderPreferFileImport = false;
            var $fileRadio = $modal.find('#type_file');
            var $catalogRadio = $modal.find('#type_catalog');
            if (preferFile && $fileRadio.length) {
                $fileRadio.prop('checked', true);
            } else if ($catalogRadio.length) {
                $catalogRadio.prop('checked', true);
            }

            if ($('#collapseCsvFile').length && typeof $.fn.collapse === 'function') {
                $('#collapseCsvFile').collapse(preferFile ? 'show' : 'hide');
            }
            this.syncOrderSourceHidden();
            this.updateModalTitle();
            this.configureCreditNoteMode();
            this.updateCreateButton();
            this.showLoading();
        },

        showLoading: function () {
            var $list = this.getModal().find('.fv-customer-picker__list');
            $list.html(
                '<div class="fv-customer-picker__loading">' +
                '<div class="fv-customer-picker__spinner" role="status" aria-hidden="true"></div>' +
                '<span>' + this.escapeHtml(this.getLang('loading', 'Cargando clientes...')) + '</span>' +
                '</div>'
            );
        },

        showEmpty: function () {
            var $list = this.getModal().find('.fv-customer-picker__list');
            $list.html(
                '<div class="fv-customer-picker__empty">' +
                '<i class="mdi mdi-account-search-outline" style="font-size:2rem;color:#cbd5e1;" aria-hidden="true"></i>' +
                '<span>' + this.escapeHtml(this.getLang('empty', 'Sin resultados')) + '</span>' +
                '</div>'
            );
        },

        escapeHtml: function (value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },

        getConfig: function () {
            return window.fvAddOrderModalConfig || {};
        },

        getCountryData: function (cus) {
            var map = this.getConfig().countryDataMap || {};
            var countryName = String(
                cus.commercial_country || cus.dispatch_country || ''
            ).trim();
            if (!countryName || !map[countryName]) {
                return { name: countryName, sortname: '', phonecode: '', phonemask: '' };
            }
            var data = map[countryName];
            return {
                name: countryName,
                sortname: String(data.sortname || '').toLowerCase(),
                phonecode: data.phonecode || '',
                phonemask: data.phonemask || ''
            };
        },

        applyPhoneMask: function (phoneNumber, mask) {
            if (!phoneNumber || !mask) {
                return phoneNumber || '';
            }
            var digits = String(phoneNumber).replace(/\D/g, '');
            if (!digits) {
                return phoneNumber;
            }
            var result = '';
            var digitIndex = 0;
            for (var i = 0; i < mask.length && digitIndex < digits.length; i++) {
                if (mask[i] === '#') {
                    result += digits[digitIndex++];
                } else {
                    result += mask[i];
                }
            }
            if (digitIndex < digits.length) {
                result += digits.substring(digitIndex);
            }
            return result;
        },

        formatPhone: function (raw, countryData) {
            var phone = String(raw || '').trim();
            if (!phone) {
                return '';
            }
            var mask = (countryData && countryData.phonemask) ? countryData.phonemask : '(###) ### ####';
            var formatted = this.applyPhoneMask(phone, mask);
            if (countryData && countryData.phonecode) {
                return '+' + countryData.phonecode + ' ' + formatted;
            }
            return formatted;
        },

        buildAddress: function (cus) {
            var segments = [];
            var pushSeg = function (val) {
                var t = String(val || '').trim();
                if (t) {
                    segments.push(t.toUpperCase());
                }
            };
            [
                cus.commercial_address,
                cus.commercial_state,
                cus.commercial_city,
                cus.commercial_zone,
                cus.commercial_zip_code,
                cus.commercial_country
            ].forEach(pushSeg);
            if (!segments.length) {
                [
                    cus.dispatch_address,
                    cus.dispatch_state,
                    cus.dispatch_city,
                    cus.dispatch_zone,
                    cus.dispatch_zip_code,
                    cus.dispatch_country
                ].forEach(pushSeg);
            }
            if (!segments.length) {
                var legacy = String(cus.dispatch_delivery_address || '').trim();
                if (legacy) {
                    segments.push(legacy.toUpperCase());
                }
            }
            return segments.join(', ');
        },

        getInitials: function (business, contact) {
            var base = (business || contact || '?').trim();
            var parts = base.split(/\s+/).filter(Boolean);
            if (!parts.length) {
                return '?';
            }
            if (parts.length === 1) {
                return parts[0].substring(0, 2).toUpperCase();
            }
            return (parts[0][0] + parts[1][0]).toUpperCase();
        },

        renderAvatar: function (countryData, business, contact) {
            var flagsBase = this.getConfig().flagsBaseUrl || '/images/flags/';
            var sortname = countryData.sortname || '';
            var countryName = countryData.name || '';
            var initials = this.getInitials(business, contact);
            var html = '<div class="fv-customer-picker__avatar" title="' + this.escapeHtml(countryName) + '">';
            if (sortname) {
                html += '<img src="' + this.escapeHtml(flagsBase + sortname + '.png') + '" class="fv-customer-picker__flag" alt="' + this.escapeHtml(countryName) + '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'" />';
            }
            html += '<span class="fv-customer-picker__avatar-fallback"' + (sortname ? ' style="display:none"' : '') + '>' + this.escapeHtml(initials) + '</span>';
            html += '</div>';
            return html;
        },

        renderDataRow: function (iconClass, text) {
            if (!text) {
                return '';
            }
            return '<div class="fv-customer-picker__row">' +
                '<i class="mdi ' + iconClass + '" aria-hidden="true"></i>' +
                '<span>' + this.escapeHtml(text) + '</span>' +
                '</div>';
        },

        buildCustomerCardHtml: function (cus) {
            var business = String(cus.business_name || '').trim();
            var contact = String(cus.name || '').trim();
            var title = business || contact || String(cus.customer_name || '').trim();
            var countryData = this.getCountryData(cus);
            var email = String(cus.email || '').trim();
            var phone = this.formatPhone(cus.phone, countryData);
            var cell = this.formatPhone(cus.cell_phone, countryData);
            var taxId = String(cus.tax_id || '').trim();
            var address = this.buildAddress(cus);

            var html = '<button type="button" class="fv-customer-picker__item" data-customer-id="' + this.escapeHtml(cus.customer_id) + '">';
            html += this.renderAvatar(countryData, business, contact);
            html += '<div class="fv-customer-picker__body">';
            html += '<div class="fv-customer-picker__name">' + this.escapeHtml(title) + '</div>';
            if (business && contact && contact !== business) {
                html += this.renderDataRow('mdi-account-outline', contact);
            }
            html += this.renderDataRow('mdi-email-outline', email);
            html += this.renderDataRow('mdi-phone-outline', phone);
            if (cell && cell !== phone) {
                html += this.renderDataRow('mdi-cellphone', cell);
            }
            html += this.renderDataRow('mdi-map-marker-outline', address);
            html += this.renderDataRow('mdi-card-account-details-outline', taxId);
            html += '</div></button>';
            return html;
        },

        loadCustomers: function () {
            var self = this;
            var searchUrl = this.getModal().find('#fv_customer_search').data('search-url');
            if (!searchUrl) {
                self.showEmpty();
                return;
            }
            $.get(searchUrl, { term: '' })
                .done(function (res) {
                    if (typeof res === 'string') {
                        try { res = JSON.parse(res); } catch (e) { res = []; }
                    }
                    self.allCustomers = Array.isArray(res) ? res : (res && res.data ? res.data : []);
                    self.renderList('');
                })
                .fail(function () {
                    self.allCustomers = [];
                    self.showEmpty();
                });
        },

        filterCustomers: function (term) {
            var needle = String(term || '').trim().toLowerCase();
            if (!needle) {
                return this.allCustomers.slice();
            }
            return this.allCustomers.filter(function (cus) {
                var haystack = [
                    cus.business_name,
                    cus.name,
                    cus.customer_name,
                    cus.email,
                    cus.tax_id,
                    cus.phone,
                    cus.cell_phone,
                    cus.commercial_address,
                    cus.commercial_city,
                    cus.commercial_state,
                    cus.commercial_country,
                    cus.dispatch_address,
                    cus.dispatch_city,
                    cus.dispatch_country,
                    cus.customer_id
                ].filter(Boolean).join(' ').toLowerCase();
                return haystack.indexOf(needle) !== -1;
            });
        },

        selectCustomer: function (customerId, $item) {
            this.selectedCustomerId = String(customerId || '');
            var $modal = this.getModal();
            $modal.find('#addorder_customer_id').val(this.selectedCustomerId);
            $modal.find('.fv-customer-picker__item').removeClass('is-selected');
            if ($item && $item.length) {
                $item.addClass('is-selected');
            }
            this.updateCreateButton();
        },

        renderList: function (term) {
            var self = this;
            var list = this.filterCustomers(term);
            var $list = this.getModal().find('.fv-customer-picker__list');
            var previousId = self.selectedCustomerId;
            $list.empty();

            if (!list.length) {
                self.showEmpty();
                return;
            }

            list.forEach(function (cus) {
                if (!cus.customer_id && cus.customer_id !== 0) {
                    return;
                }
                $list.append(self.buildCustomerCardHtml(cus));
            });

            if (previousId) {
                var $selected = $list.find('.fv-customer-picker__item[data-customer-id="' + previousId + '"]');
                if ($selected.length) {
                    $selected.addClass('is-selected');
                } else {
                    self.selectedCustomerId = '';
                    self.getModal().find('#addorder_customer_id').val('');
                    self.updateCreateButton();
                }
            }
        },

        submitOrder: function () {
            var self = this;
            if (self.isSubmitting) {
                return;
            }
            var form = self.getForm();
            if (!form) {
                return;
            }
            if (!self.selectedCustomerId) {
                if (typeof swal === 'function') {
                    swal({ title: self.getLang('select_customer', 'Debe elegir un cliente.'), icon: 'warning' });
                }
                return;
            }
            var source = self.getSelectedOrderSource();
            if (!source) {
                if (typeof swal === 'function') {
                    swal({ title: self.getLang('select_source', 'Seleccione tipo de orden.'), icon: 'warning' });
                }
                return;
            }
            if (source === 'csv' && !self.hasCsvFile()) {
                if (typeof swal === 'function') {
                    swal({ title: self.getLang('select_csv', 'Seleccione un archivo CSV.'), icon: 'warning' });
                }
                return;
            }
            if (source === 'csv' && !self.importPreviewValid()) {
                if (typeof swal === 'function') {
                    swal({
                        title: self.getLang('preview_has_errors', ''),
                        icon: 'warning'
                    });
                }
                return;
            }
            if (form.getAttribute('data-plan-limit-blocked') === '1') {
                var blockedMsg = form.getAttribute('data-plan-limit-message') || '';
                if (typeof swal === 'function') {
                    swal({ title: blockedMsg, icon: 'warning' });
                } else if (blockedMsg) {
                    alert(blockedMsg);
                }
                return;
            }

            var tipoDEl = document.getElementById('tipo_d_addorder');
            if (tipoDEl) {
                tipoDEl.value = self.isCreditNote() ? 'C' : 'D';
            }
            self.syncOrderSourceHidden();
            form.querySelector('#addorder_customer_id').value = self.selectedCustomerId;
            self.isSubmitting = true;
            self.updateCreateButton();

            if (typeof showOrderActionLoader === 'function') {
                showOrderActionLoader(self.getLang('processing', 'Procesando...'));
            }

            var formData = new FormData(form);
            $.ajax({
                url: form.getAttribute('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (data) {
                if (data.error) {
                    if (typeof swal === 'function') {
                        swal({ title: (data.error || 'Error'), icon: 'error' });
                    } else {
                        alert(data.error);
                    }
                    return;
                }
                if (data.url_redirect) {
                    var go = function () {
                        if (data.other_window) {
                            window.open(data.url_redirect, '_blank');
                        } else {
                            window.location.href = data.url_redirect;
                        }
                        self.getModal().modal('hide');
                    };
                    if (typeof swal === 'function') {
                        swal({ title: (data.msg || ''), icon: 'success', timer: 1200, buttons: false }).then(go);
                    } else {
                        go();
                    }
                    return;
                }
                if (data.msg && typeof swal === 'function') {
                    swal({ title: data.msg, icon: 'success' });
                }
            }).fail(function (xhr) {
                var msg = self.getLang('connection_error', 'Error de conexión');
                try {
                    var j = xhr.responseJSON || (xhr.responseText ? JSON.parse(xhr.responseText) : null);
                    if (j && (j.error || j.message)) {
                        msg = j.error || j.message;
                    }
                } catch (e) {}
                if (typeof swal === 'function') {
                    swal({ title: msg, icon: 'error' });
                } else {
                    alert(msg);
                }
            }).always(function () {
                self.isSubmitting = false;
                self.updateCreateButton();
                if (typeof hideOrderActionLoader === 'function') {
                    hideOrderActionLoader();
                }
            });
        },

        updateCsvFileLabel: function (input) {
            var $label = this.getModal().find('label.custom-file-label[for="validatedCustomFile"]');
            if (!$label.length || !input) {
                return;
            }
            if (!$label.data('default-label')) {
                $label.data('default-label', $label.text());
            }
            var fileName = input.files && input.files.length ? input.files[0].name : $label.data('default-label');
            $label.text(fileName);
            this.updateCreateButton();
        },

        bindEvents: function () {
            var self = this;
            var $modal = self.getModal();
            if (!$modal.length || $modal.data('fvCustomerPickerBound')) {
                return;
            }
            $modal.data('fvCustomerPickerBound', true);

            var $fileLabel = $modal.find('label.custom-file-label[for="validatedCustomFile"]');
            if ($fileLabel.length) {
                $fileLabel.data('default-label', $fileLabel.text());
            }

            $modal.on('show.bs.modal', function () {
                self.reset();
            });

            $modal.on('shown.bs.modal', function () {
                self.loadCustomers();
                $modal.find('#fv_customer_search').trigger('focus');
            });

            $modal.on('input', '#fv_customer_search', function () {
                if (self.searchTimer) {
                    clearTimeout(self.searchTimer);
                }
                var term = $(this).val();
                self.searchTimer = setTimeout(function () {
                    self.renderList(term);
                }, 150);
            });

            $modal.on('click', '.fv-customer-picker__item', function () {
                var customerId = $(this).data('customer-id');
                if (!customerId) {
                    return;
                }
                self.selectCustomer(customerId, $(this));
            });

            $modal.on('change', 'input[name="order_source"]', function () {
                self.syncOrderSourceHidden();
            });

            $modal.on('change', '#validatedCustomFile', function () {
                self.updateCsvFileLabel(this);
                self.requestImportPreview(this);
            });

            $('#addorder_btn_create').on('click', function () {
                self.submitOrder();
            });

            var form = self.getForm();
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    self.submitOrder();
                });
            }
        },

        init: function () {
            this.bindEvents();
        }
    };

    $(function () {
        FvAddOrderCustomerModal.init();
    });

    window.FvAddOrderCustomerModal = FvAddOrderCustomerModal;
}(window, window.jQuery));
