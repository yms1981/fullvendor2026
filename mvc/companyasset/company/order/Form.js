/**
 * Formulario crear/editar orden: modal de cliente + card.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var FvOrderForm = {
        allCustomers: [],
        searchTimer: null,

        getConfig: function () {
            return window.fvOrderFormConfig || {};
        },

        getLang: function (key, fallback) {
            var L = this.getConfig().lang || {};
            return (L[key] && String(L[key]).trim() !== '') ? L[key] : (fallback || '');
        },

        escapeHtml: function (value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },

        getCountryData: function (cus) {
            var map = this.getConfig().countryDataMap || {};
            var countryName = String(cus.commercial_country || cus.dispatch_country || '').trim();
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

        formatPhone: function (raw, countryData) {
            var phone = String(raw || '').trim();
            if (!phone) {
                return '';
            }
            var mask = (countryData && countryData.phonemask) ? countryData.phonemask : '(###) ### ####';
            var digits = phone.replace(/\D/g, '');
            var result = '';
            var di = 0;
            for (var i = 0; i < mask.length && di < digits.length; i++) {
                if (mask[i] === '#') {
                    result += digits[di++];
                } else {
                    result += mask[i];
                }
            }
            if (di < digits.length) {
                result += digits.substring(di);
            }
            if (countryData && countryData.phonecode) {
                return '+' + countryData.phonecode + ' ' + result;
            }
            return result;
        },

        buildAddress: function (cus) {
            var segments = [];
            var push = function (val) {
                var t = String(val || '').trim();
                if (t) {
                    segments.push(t.toUpperCase());
                }
            };
            ['commercial_address', 'commercial_state', 'commercial_city', 'commercial_zone', 'commercial_zip_code', 'commercial_country'].forEach(function (k) {
                push(cus[k]);
            });
            if (!segments.length) {
                ['dispatch_address', 'dispatch_state', 'dispatch_city', 'dispatch_zone', 'dispatch_zip_code', 'dispatch_country'].forEach(function (k) {
                    push(cus[k]);
                });
            }
            if (!segments.length && cus.dispatch_delivery_address) {
                push(cus.dispatch_delivery_address);
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

        renderCardItem: function (icon, text, extraClass) {
            if (!text) {
                return '';
            }
            var cls = 'fv-order-customer-card__item' + (extraClass ? ' ' + extraClass : '');
            var iconCls = String(icon || '').indexOf('fa-') === 0 ? icon : ('mdi ' + icon);
            return '<div class="' + cls + '"><i class="' + iconCls + '" aria-hidden="true"></i><span>' + this.escapeHtml(text) + '</span></div>';
        },

        renderCustomerCard: function (cus) {
            if (!cus || !cus.customer_id) {
                return '<div class="fv-order-customer-card fv-order-customer-card--empty">' +
                    '<i class="mdi mdi-account-search-outline"></i>' +
                    '<span>' + this.escapeHtml(this.getLang('debe_elegir_cliente', '')) + '</span></div>';
            }

            var business = String(cus.business_name || '').trim();
            var contact = String(cus.name || '').trim();
            var title = business || contact;
            var countryData = this.getCountryData(cus);
            var flagsBase = this.getConfig().flagsBaseUrl || '/images/flags/';
            var flag = countryData.sortname ? flagsBase + countryData.sortname + '.png' : '';
            var initials = this.getInitials(business, contact);
            var email = String(cus.email || '').trim();
            var phone = this.formatPhone(cus.phone, countryData);
            var cell = this.formatPhone(cus.cell_phone, countryData);
            var taxId = String(cus.tax_id || '').trim();
            var group = String(cus.group_price || '').trim();
            var address = this.buildAddress(cus);

            var html = '<div class="fv-order-customer-card"><div class="fv-order-customer-card__head">';
            html += '<div class="fv-order-customer-card__avatar">';
            if (flag) {
                html += '<img src="' + this.escapeHtml(flag) + '" alt="" class="fv-order-customer-card__flag" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">';
            }
            html += '<span class="fv-order-customer-card__initials"' + (flag ? ' style="display:none"' : '') + '>' + this.escapeHtml(initials) + '</span></div>';
            html += '<div class="fv-order-customer-card__title-wrap"><h4 class="fv-order-customer-card__title">' + this.escapeHtml(title) + '</h4>';
            if (contact && contact !== business) {
                html += '<p class="fv-order-customer-card__subtitle"><i class="mdi mdi-account-outline"></i> ' + this.escapeHtml(contact) + '</p>';
            }
            html += '</div></div><div class="fv-order-customer-card__body">';
            if (address) {
                html += '<div class="fv-order-customer-card__item fv-order-customer-card__item--address"><i class="mdi mdi-map-marker-outline"></i><span>' + this.escapeHtml(address) + '</span></div>';
            }
            if (phone || (cell && cell !== phone)) {
                html += '<div class="fv-order-customer-card__phones">';
                html += this.renderCardItem('mdi-phone-outline', phone);
                if (cell && cell !== phone) {
                    html += this.renderCardItem('mdi-cellphone', cell);
                }
                html += '</div>';
            }
            html += this.renderCardItem('fa-solid fa-envelope', email, 'fv-order-customer-card__item--email');
            if (taxId || (group && group !== '-')) {
                html += '<div class="fv-order-customer-card__meta">';
                html += this.renderCardItem('mdi-card-account-details-outline', taxId);
                if (group && group !== '-') {
                    html += this.renderCardItem('mdi-account-group-outline', group);
                }
                html += '</div>';
            }
            html += '</div></div>';
            return html;
        },

        updateChangeCustomerButton: function () {
            var hasCustomer = String($('#selectCustomer').val() || '').trim() !== '';
            var label = hasCustomer
                ? this.getLang('order_change_customer', '')
                : this.getLang('customer_order', '');
            $('#fv_order_btn_change_customer_label').text(label);
        },

        applyCustomerToForm: function (payload, options) {
            options = options || {};
            if (!payload || payload.error) {
                return;
            }
            var preserveSeller = !!options.preserveSeller;
            var preserveDiscount = !!options.preserveDiscount || this.getConfig().mode === 'edit';

            $('#selectCustomer').val(payload.customer_id || '');
            $('#fv_order_customer_card').html(this.renderCustomerCard(payload));
            $('#customer_email').val(payload.email || '');
            $('#group_price').val(payload.group_price || '-');
            this.updateChangeCustomerButton();

            if (!preserveDiscount) {
                $('#discountTypeSelect').val(payload.discount_type || '1');
                $('#input_discount').val(payload.discount != null ? payload.discount : '');
            }

            if (!preserveSeller && $('#picker_seller').length && payload.seller_id) {
                $('#picker_seller').selectpicker('val', String(payload.seller_id));
            }

            if (typeof window.actualizarCamposFormularioOrden === 'function') {
                window.actualizarCamposFormularioOrden(payload, {
                    preserveSeller: preserveSeller,
                    preserveDiscount: preserveDiscount
                });
            } else if (typeof cambiarSimboloDescuentoEnGrilla === 'function') {
                cambiarSimboloDescuentoEnGrilla();
            }
            if (typeof window.calcularTotales === 'function') {
                window.calcularTotales();
            }
        },

        fetchCustomerFormData: function (customerId, options) {
            var self = this;
            var url = this.getConfig().customerFormDataUrl;
            if (!url || !customerId) {
                $('#fv_order_customer_card').html(self.renderCustomerCard(null));
                self.updateChangeCustomerButton();
                return;
            }
            $.get(url, { customer_id: customerId })
                .done(function (res) {
                    if (typeof res === 'string') {
                        try { res = JSON.parse(res); } catch (e) { res = null; }
                    }
                    self.applyCustomerToForm(res, options);
                })
                .fail(function () {
                    var cfg = self.getConfig();
                    if (cfg.initialCustomer && String(cfg.initialCustomer.customer_id) === String(customerId)) {
                        self.applyCustomerToForm(cfg.initialCustomer, options);
                    }
                });
        },

        getModal: function () {
            return $('#modal_order_change_customer');
        },

        getPickerList: function () {
            return this.getModal().find('#fv_order_customer_picker_list');
        },

        showPickerLoading: function () {
            this.getPickerList().html(
                '<div class="fv-customer-picker__loading">' +
                '<div class="fv-customer-picker__spinner" role="status" aria-hidden="true"></div>' +
                '<span>' + this.escapeHtml(this.getLang('loading', '')) + '</span>' +
                '</div>'
            );
        },

        showPickerEmpty: function () {
            this.getPickerList().html(
                '<div class="fv-customer-picker__empty">' +
                '<i class="mdi mdi-account-search-outline" style="font-size:2rem;color:#cbd5e1;" aria-hidden="true"></i>' +
                '<span>' + this.escapeHtml(this.getLang('empty', '')) + '</span>' +
                '</div>'
            );
        },

        renderPickerAvatar: function (countryData, business, contact) {
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

        renderPickerRow: function (iconClass, text) {
            if (!text) {
                return '';
            }
            return '<div class="fv-customer-picker__row">' +
                '<i class="mdi ' + iconClass + '" aria-hidden="true"></i>' +
                '<span>' + this.escapeHtml(text) + '</span>' +
                '</div>';
        },

        buildPickerItemHtml: function (cus) {
            var business = String(cus.business_name || '').trim();
            var contact = String(cus.name || '').trim();
            var title = business || contact || String(cus.customer_name || '').trim();
            var countryData = this.getCountryData(cus);
            var email = String(cus.email || '').trim();
            var phone = this.formatPhone(cus.phone, countryData);
            var cell = this.formatPhone(cus.cell_phone, countryData);
            var taxId = String(cus.tax_id || '').trim();
            var address = this.buildAddress(cus);
            var group = String(cus.group_price || '').trim();
            if (!group && cus.customer_group && cus.customer_group.name) {
                group = String(cus.customer_group.name).trim();
            }

            var html = '<button type="button" class="fv-customer-picker__item" data-customer-id="' + this.escapeHtml(cus.customer_id) + '">';
            html += this.renderPickerAvatar(countryData, business, contact);
            html += '<div class="fv-customer-picker__body">';
            html += '<div class="fv-customer-picker__name">' + this.escapeHtml(title) + '</div>';
            if (business && contact && contact !== business) {
                html += this.renderPickerRow('mdi-account-outline', contact);
            }
            html += this.renderPickerRow('mdi-email-outline', email);
            html += this.renderPickerRow('mdi-phone-outline', phone);
            if (cell && cell !== phone) {
                html += this.renderPickerRow('mdi-cellphone', cell);
            }
            html += this.renderPickerRow('mdi-card-account-details-outline', taxId);
            if (group && group !== '-') {
                html += this.renderPickerRow('mdi-account-group-outline', group);
            }
            html += this.renderPickerRow('mdi-map-marker-outline', address);
            html += '</div></button>';
            return html;
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

        renderPickerList: function (term) {
            var self = this;
            var list = this.filterCustomers(term);
            var $list = this.getPickerList();
            var currentId = String($('#selectCustomer').val() || '');
            $list.empty();

            if (!list.length) {
                self.showPickerEmpty();
                return;
            }

            list.forEach(function (cus) {
                if (!cus.customer_id && cus.customer_id !== 0) {
                    return;
                }
                $list.append(self.buildPickerItemHtml(cus));
            });

            if (currentId) {
                $list.find('.fv-customer-picker__item[data-customer-id="' + currentId + '"]').addClass('is-selected');
            }
        },

        loadCustomersForModal: function () {
            var self = this;
            var searchUrl = this.getConfig().searchUrl || this.getModal().find('#fv_order_customer_search').data('search-url');
            if (!searchUrl) {
                self.showPickerEmpty();
                return;
            }
            self.showPickerLoading();
            $.get(searchUrl, { term: '' })
                .done(function (res) {
                    if (typeof res === 'string') {
                        try { res = JSON.parse(res); } catch (e) { res = []; }
                    }
                    self.allCustomers = Array.isArray(res) ? res : (res && res.data ? res.data : []);
                    self.renderPickerList('');
                })
                .fail(function () {
                    self.allCustomers = [];
                    self.showPickerEmpty();
                });
        },

        openCustomerModal: function () {
            var $modal = this.getModal();
            if (!$modal.length) {
                return;
            }
            $modal.find('#fv_order_customer_search').val('');
            this.loadCustomersForModal();
            $modal.modal('show');
        },

        selectCustomerFromModal: function (customerId) {
            var self = this;
            if (!customerId) {
                return;
            }
            $('#selectCustomer').val(String(customerId));
            self.getModal().modal('hide');
            self.fetchCustomerFormData(customerId, { preserveSeller: true });
        },

        initCustomerModal: function () {
            var self = this;
            var $modal = self.getModal();
            if (!$modal.length) {
                return;
            }
            if ($modal.data('fvOrderCustomerModalBound')) {
                return;
            }
            $modal.data('fvOrderCustomerModalBound', true);

            $(document).off('click.fvOrderCustomer', '#fv_order_btn_change_customer').on('click.fvOrderCustomer', '#fv_order_btn_change_customer', function (e) {
                e.preventDefault();
                self.openCustomerModal();
            });

            $modal.on('shown.bs.modal', function () {
                $modal.find('#fv_order_customer_search').trigger('focus');
            });

            $modal.on('input', '#fv_order_customer_search', function () {
                var term = $(this).val();
                clearTimeout(self.searchTimer);
                self.searchTimer = setTimeout(function () {
                    self.renderPickerList(term);
                }, 180);
            });

            $modal.on('click', '.fv-customer-picker__item', function () {
                var customerId = $(this).data('customer-id');
                self.selectCustomerFromModal(customerId);
            });
        },

        bindFabActions: function () {
            $(document).off('click.fvOrderFab', '#fv_order_fab_add_product').on('click.fvOrderFab', '#fv_order_fab_add_product', function (e) {
                e.preventDefault();
                var $modal = $('#add-new');
                if ($modal.length && typeof $modal.modal === 'function') {
                    $modal.modal({ backdrop: 'static', keyboard: false, show: true });
                }
            });
        },

        init: function () {
            if (!$('#selectCustomer').length) {
                return;
            }
            var self = this;
            var cfg = this.getConfig();

            this.initCustomerModal();
            this.bindFabActions();
            this.updateChangeCustomerButton();

            var currentId = $('#selectCustomer').val();
            if (currentId) {
                if (cfg.mode === 'edit' && cfg.initialCustomer && String(cfg.initialCustomer.customer_id) === String(currentId)) {
                    $('#fv_order_customer_card').html(this.renderCustomerCard(cfg.initialCustomer));
                } else if (cfg.initialCustomer && String(cfg.initialCustomer.customer_id) === String(currentId)) {
                    this.applyCustomerToForm(cfg.initialCustomer);
                } else {
                    this.fetchCustomerFormData(currentId);
                }
            }
        }
    };

    window.FvOrderForm = FvOrderForm;

    function boot() {
        FvOrderForm.init();
    }

    if (document.readyState === 'loading') {
        $(document).ready(boot);
    } else {
        boot();
    }
})(window, window.jQuery);
